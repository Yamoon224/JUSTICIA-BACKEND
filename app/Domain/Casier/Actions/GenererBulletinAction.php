<?php

namespace App\Domain\Casier\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Casier\Models\Consultation;
use App\Domain\Contracts\Horodatable;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Génère un bulletin du casier judiciaire (§6.10) : chaque consultation
 * nominative est journalisée et motivée (contrôle d'accès strict), jamais
 * silencieuse — y compris quand le résultat est vide.
 *
 * Règles de filtrage par bulletin — simplification assumée du socle, à
 * faire valider par la chancellerie avant recette (les règles réelles du
 * casier judiciaire ivoirien distinguent des cas plus fins selon la nature
 * exacte de la peine) :
 * - B1 (réservé aux autorités judiciaires) : toutes les condamnations sauf
 *   amnistiées — l'amnistie efface même le B1, une réhabilitation non.
 * - B2 (administrations pour emplois/activités réglementés) : exclut en
 *   outre les réhabilitées et les contraventions.
 * - B3 (délivré à la personne elle-même) : uniquement les condamnations
 *   encore actives, pour crime ou délit, à une peine ferme (sans sursis).
 */
class GenererBulletinAction
{
    private const TYPES_VALIDES = ['b1', 'b2', 'b3'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    /**
     * @return Collection<int, Condamnation>
     */
    public function executer(Personne $personne, string $type, string $motif, User $acteur): Collection
    {
        if (! in_array($type, self::TYPES_VALIDES, true)) {
            throw new InvalidArgumentException("Type de bulletin inconnu : {$type}.");
        }

        $condamnations = Condamnation::query()->where('personne_id', $personne->id)->get();
        $filtrees = $this->filtrer($condamnations, $type);

        $consultation = Consultation::query()->create([
            'personne_id' => $personne->id,
            'type_bulletin' => $type,
            'motif' => $motif,
            'consultee_at' => $this->horodatage->maintenant(),
            'consultee_par' => $acteur->id,
        ]);

        $this->audit->consigner('casier.consultation', auditable: $consultation, acteur: $acteur, motif: $motif, payloadSupplementaire: [
            'personne_id' => $personne->id,
            'type_bulletin' => $type,
            'resultat_nombre' => $filtrees->count(),
        ]);

        return $filtrees;
    }

    /**
     * @param  Collection<int, Condamnation>  $condamnations
     * @return Collection<int, Condamnation>
     */
    private function filtrer(Collection $condamnations, string $type): Collection
    {
        return (match ($type) {
            'b1' => $condamnations->reject(fn (Condamnation $c) => $c->statut === 'amnistiee'),
            'b2' => $condamnations->reject(fn (Condamnation $c) => in_array($c->statut, ['amnistiee', 'rehabilitee'], true)
                || $c->categorie_infraction === 'contravention'),
            'b3' => $condamnations->filter(fn (Condamnation $c) => $c->statut === 'active'
                && ! $c->sursis
                && in_array($c->categorie_infraction, ['delit', 'crime'], true)),
            default => collect(),
        })->values();
    }
}
