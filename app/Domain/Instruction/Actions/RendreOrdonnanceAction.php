<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audiencement\Actions\OuvrirDossierAudiencementAction;
use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Support\ResolveurDelaiLegal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ordonnance de règlement (§6.6) : clôture l'information par un renvoi
 * (vers le jugement, Phase 5) ou un non-lieu — la seule décision qui met
 * fin au dossier d'instruction. Le délai de recours est résolu depuis le
 * référentiel `delais_legaux`, jamais codé en dur (§6.7 : « suivi des
 * délais de recours par partie »).
 *
 * Un non-lieu ne doit jamais laisser de trace indue au casier judiciaire
 * (§3, §14) : l'affaire passe à `cloturee`, un statut neutre, distinct
 * d'une condamnation.
 */
class RendreOrdonnanceAction
{
    private const JOURS_RECOURS_DEFAUT = 10;

    /**
     * @var array<string, string> ordonnance => nouveau statut de l'affaire
     */
    private const TRANSITIONS_AFFAIRE = [
        'renvoi' => 'audiencee',
        'non_lieu' => 'cloturee',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly ResolveurDelaiLegal $delais,
        private readonly OuvrirDossierAudiencementAction $ouvrirAudiencement,
    ) {}

    public function executer(DossierInstruction $dossier, string $ordonnance, User $juge): DossierInstruction
    {
        if (! $dossier->estEnCours()) {
            throw new InvalidArgumentException('Ce dossier d\'information est déjà clôturé.');
        }

        if (! array_key_exists($ordonnance, self::TRANSITIONS_AFFAIRE)) {
            throw new InvalidArgumentException("Ordonnance inconnue : {$ordonnance}.");
        }

        $joursRecours = $this->delais->dureeJours('ordonnance_reglement', $dossier->affaire) ?? self::JOURS_RECOURS_DEFAUT;
        $maintenant = now();

        DB::transaction(function () use ($dossier, $ordonnance, $juge, $joursRecours, $maintenant) {
            $dossier->update([
                'statut' => 'cloture',
                'ordonnance' => $ordonnance,
                'ordonnance_at' => $maintenant,
                'ordonnance_par' => $juge->id,
                'delai_recours_expire_at' => $maintenant->clone()->addDays($joursRecours),
            ]);

            $dossier->affaire->update(['statut' => self::TRANSITIONS_AFFAIRE[$ordonnance]]);

            if ($ordonnance === 'renvoi') {
                $this->ouvrirAudiencement->executer($dossier->affaire);
            }
        });

        $this->audit->consigner('instruction.ordonnance', auditable: $dossier, acteur: $juge, payloadSupplementaire: [
            'ordonnance' => $ordonnance,
            'delai_recours_jours' => $joursRecours,
        ]);

        return $dossier->refresh();
    }
}
