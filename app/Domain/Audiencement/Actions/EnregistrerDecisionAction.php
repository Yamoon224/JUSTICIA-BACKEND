<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Audit\AuditService;
use App\Domain\Personnes\Models\Personne;
use App\Domain\Support\ResolveurDelaiLegal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Décision par prévenu (§6.7) : condamnation, relaxe, acquittement,
 * dispense de peine — toujours une décision humaine, jamais déduite par le
 * système (§3). Répercute immédiatement le statut sur le pivot
 * affaire_personne (§6.2, §3 : présomption d'innocence) : une personne
 * relaxée ou acquittée ne doit jamais apparaître comme condamnée, et
 * réciproquement.
 *
 * Une seule décision par personne et par dossier, sauf si la précédente a
 * été rouverte par un recours recevable et résolu (§6.8) : c'est le seul
 * cas où une nouvelle décision sur le même dossier est légitime — la
 * juridiction saisie en appel/cassation rend sa décision comme n'importe
 * quel jugement (IntegrerDecisionRecoursAction, §14 : « appel avec
 * infirmation → mise à jour exécution/casier »). Un doublon accidentel
 * (sans recours entre les deux) reste rejeté.
 */
class EnregistrerDecisionAction
{
    private const DECISIONS_VALIDES = ['condamnation', 'relaxe', 'acquittement', 'dispense_de_peine'];

    /**
     * @var array<string, string> decision => statut sur affaire_personne
     *
     * La dispense de peine reste juridiquement une reconnaissance de
     * culpabilité (la peine seule est écartée) : elle porte donc le même
     * statut qu'une condamnation.
     */
    private const STATUTS_PERSONNE = [
        'condamnation' => 'condamne',
        'dispense_de_peine' => 'condamne',
        'relaxe' => 'relaxe',
        'acquittement' => 'acquitte',
    ];

    private const JOURS_RECOURS_DEFAUT = 10;

    public function __construct(
        private readonly AuditService $audit,
        private readonly ResolveurDelaiLegal $delais,
    ) {}

    public function executer(
        DossierAudiencement $dossier,
        Personne $personne,
        string $decision,
        ?string $peinePrincipale,
        bool $sursis,
        ?string $interetsCivils,
        User $acteur,
    ): Decision {
        if (! $dossier->estEnrole()) {
            throw new InvalidArgumentException('Seule une affaire enrôlée peut recevoir une décision.');
        }

        if (! in_array($decision, self::DECISIONS_VALIDES, true)) {
            throw new InvalidArgumentException("Décision inconnue : {$decision}.");
        }

        $precedente = $dossier->decisions()->where('personne_id', $personne->id)->latest('id')->first();
        if ($precedente !== null && ! $precedente->recours()->where('recevable', true)->whereNotNull('decision_recours')->exists()) {
            throw new InvalidArgumentException(
                'Une décision a déjà été rendue pour cette personne sur ce dossier ; seule '.
                "l'issue d'un recours recevable peut en justifier une nouvelle."
            );
        }

        $joursRecours = $this->delais->dureeJours('recours_jugement', $dossier->affaire) ?? self::JOURS_RECOURS_DEFAUT;
        $maintenant = now();

        $enregistree = DB::transaction(function () use ($dossier, $personne, $decision, $peinePrincipale, $sursis, $interetsCivils, $acteur, $joursRecours, $maintenant) {
            $ligne = $dossier->decisions()->create([
                'personne_id' => $personne->id,
                'decision' => $decision,
                'peine_principale' => $peinePrincipale,
                'sursis' => $sursis,
                'interets_civils' => $interetsCivils,
                'rendue_at' => $maintenant,
                'delai_recours_jours' => $joursRecours,
                'delai_recours_expire_at' => $maintenant->clone()->addDays($joursRecours),
                'rendue_par' => $acteur->id,
            ]);

            $dossier->affaire->personnes()->attach($personne->id, [
                'statut' => self::STATUTS_PERSONNE[$decision],
                'depuis' => $maintenant,
            ]);

            $dossier->update(['statut' => 'jugee']);
            $dossier->affaire->update(['statut' => 'jugee']);

            return $ligne;
        });

        $this->audit->consigner('audiencement.decision', auditable: $enregistree, acteur: $acteur, payloadSupplementaire: [
            'personne_id' => $personne->id,
            'decision' => $decision,
        ]);

        return $enregistree;
    }
}
