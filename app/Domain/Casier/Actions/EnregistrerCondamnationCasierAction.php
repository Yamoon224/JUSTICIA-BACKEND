<?php

namespace App\Domain\Casier\Actions;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audit\AuditService;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Contracts\Horodatable;
use App\Models\User;
use InvalidArgumentException;

/**
 * Inscription automatique d'une condamnation au casier judiciaire (§6.10),
 * déclenchée par la mise à exécution d'une décision définitive
 * (App\Domain\Execution\Actions\MettreAExecutionAction) — jamais une saisie
 * manuelle. Capture un instantané des informations d'affichage (affaire,
 * juridiction, infraction) pour que le bulletin reste stable et lisible
 * indépendamment de l'évolution ultérieure du dossier source.
 */
class EnregistrerCondamnationCasierAction
{
    /**
     * Ordre de gravité décroissant — la plus grave des infractions
     * retenues sur l'affaire détermine la catégorie inscrite au casier,
     * même principe que App\Domain\Support\ResolveurDelaiLegal.
     *
     * @var list<string>
     */
    private const ORDRE_GRAVITE = ['crime', 'delit', 'contravention'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Decision $decision, User $acteur): Condamnation
    {
        if ($decision->decision !== 'condamnation') {
            throw new InvalidArgumentException('Seule une décision de condamnation peut être inscrite au casier.');
        }

        if (Condamnation::query()->where('decision_id', $decision->id)->exists()) {
            throw new InvalidArgumentException('Cette décision est déjà inscrite au casier.');
        }

        $decision->loadMissing(['dossierAudiencement.affaire.infractions', 'dossierAudiencement.juridiction']);
        $affaire = $decision->dossierAudiencement->affaire;
        $infractions = $affaire->infractions;

        $categorie = collect(self::ORDRE_GRAVITE)->first(
            fn (string $c) => $infractions->contains('categorie', $c),
        ) ?? 'delit';

        $infractionRetenue = $infractions->firstWhere('categorie', $categorie);

        $condamnation = Condamnation::query()->create([
            'personne_id' => $decision->personne_id,
            'decision_id' => $decision->id,
            'numero_affaire' => $affaire->numero_affaire,
            'juridiction_nom' => $decision->dossierAudiencement->juridiction?->nom ?? 'Juridiction non renseignée',
            'infraction_libelle' => $infractionRetenue?->libelle ?? 'Infraction non renseignée',
            'categorie_infraction' => $categorie,
            'peine_principale' => $decision->peine_principale,
            'sursis' => $decision->sursis,
            'condamnee_at' => $decision->rendue_at,
            'statut' => 'active',
            'inscrite_at' => $this->horodatage->maintenant(),
        ]);

        $this->audit->consigner('casier.inscription', auditable: $condamnation, acteur: $acteur, payloadSupplementaire: [
            'decision_id' => $decision->id,
        ]);

        return $condamnation;
    }
}
