<?php

namespace App\Domain\Casier\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Casier\Models\Rehabilitation;
use App\Domain\Contracts\Horodatable;
use App\Domain\Support\ResolveurDelaiLegal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Réhabilitation de plein droit (§6.10, §6.11) : constatée automatiquement,
 * sans décision humaine, une fois le délai légal écoulé (référentiel
 * `delais_legaux`, type_acte `rehabilitation_plein_droit`) — à condition
 * qu'aucune nouvelle condamnation active ne soit intervenue dans
 * l'intervalle. Planifiée quotidiennement (voir bootstrap/app.php), à la
 * différence des moteurs d'alertes GAV/détention/liberté qui tournent en
 * continu : ici rien n'est urgent à la minute près.
 *
 * Simplification assumée du socle : « sans nouvelle condamnation dans
 * l'intervalle » est vérifié uniquement contre les condamnations encore
 * *actives* de la même personne, pas contre l'historique complet (une
 * condamnation elle-même réhabilitée ou amnistiée entre-temps n'est pas
 * reconsidérée) — à affiner avec un juriste avant recette.
 */
class DetecterRehabilitationsDePleinDroitAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
        private readonly ResolveurDelaiLegal $delais,
    ) {}

    /**
     * @return Collection<int, Condamnation>
     */
    public function executer(): Collection
    {
        $maintenant = $this->horodatage->maintenant();

        return Condamnation::query()
            ->where('statut', 'active')
            ->get()
            ->filter(fn (Condamnation $condamnation) => $this->estEligible($condamnation, $maintenant))
            ->values()
            ->each(function (Condamnation $condamnation) use ($maintenant) {
                Rehabilitation::query()->create([
                    'condamnation_id' => $condamnation->id,
                    'type' => 'plein_droit',
                    'decidee_at' => $maintenant,
                    'decidee_par' => null,
                ]);

                $condamnation->update(['statut' => 'rehabilitee']);

                $this->audit->consigner('casier.rehabilitation', auditable: $condamnation, payloadSupplementaire: [
                    'type' => 'plein_droit',
                ]);
            });
    }

    private function estEligible(Condamnation $condamnation, CarbonInterface $maintenant): bool
    {
        $delaiJours = $this->delais->dureeJoursPourCategorie('rehabilitation_plein_droit', $condamnation->categorie_infraction);

        if ($delaiJours === null) {
            return false;
        }

        if ($condamnation->condamnee_at->clone()->addDays($delaiJours)->isAfter($maintenant)) {
            return false;
        }

        return ! Condamnation::query()
            ->where('personne_id', $condamnation->personne_id)
            ->where('id', '!=', $condamnation->id)
            ->where('statut', 'active')
            ->where('condamnee_at', '>', $condamnation->condamnee_at)
            ->exists();
    }
}
