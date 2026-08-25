<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Personnes\Models\Personne;
use App\Models\DelaiLegal;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Placement en garde à vue (§6.1). La durée légale n'est jamais codée en
 * dur : elle est résolue depuis le référentiel `delais_legaux` selon la
 * catégorie la plus grave des infractions retenues sur l'affaire, avec un
 * repli explicite si aucune règle n'est paramétrée (à ne jamais laisser
 * silencieux en production — §11, réformes intégrées par paramétrage).
 * Le régime mineur (§6.1) est appliqué automatiquement selon l'âge.
 */
class PlacerEnGardeAVueAction
{
    private const DUREE_DEFAUT_HEURES = 24;

    /**
     * Ordre de gravité décroissant — le plus sévère détermine le délai.
     *
     * @var list<string>
     */
    private const ORDRE_GRAVITE = ['crime', 'delit', 'contravention'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Affaire $affaire, Personne $personne, Unite $unite, User $agent, ?Carbon $debut = null): MesureGardeAVue
    {
        $debutAt = $debut ?? now();
        $dureeHeures = $this->resoudreDureeLegale($affaire);
        $mineur = $personne->date_naissance !== null && $personne->date_naissance->age < 18;

        $mesure = MesureGardeAVue::query()->create([
            'affaire_id' => $affaire->id,
            'personne_id' => $personne->id,
            'unite_id' => $unite->id,
            'debut_at' => $debutAt,
            'duree_heures' => $dureeHeures,
            'fin_prevue_at' => $debutAt->clone()->addHours($dureeHeures),
            'mineur' => $mineur,
            'created_by' => $agent->id,
        ]);

        $this->audit->consigner('gav.placement', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'duree_heures' => $dureeHeures,
            'mineur' => $mineur,
        ]);

        return $mesure;
    }

    private function resoudreDureeLegale(Affaire $affaire): int
    {
        $categories = $affaire->infractions()->pluck('categorie');
        $categorie = collect(self::ORDRE_GRAVITE)->first(fn (string $c) => $categories->contains($c));

        $today = now()->toDateString();

        // whereDate() plutôt que where() : une colonne au cast 'date' est
        // néanmoins écrite avec l'heure par Eloquent (fromDateTime() suit le
        // format de connexion, pas le cast) — sans troncature explicite côté
        // SQL, une comparaison texte peut échouer selon le pilote (SQLite ne
        // tronque pas silencieusement comme MySQL le ferait sur une colonne
        // DATE).
        $delai = DelaiLegal::query()
            ->where('type_acte', 'garde_a_vue')
            ->where('categorie_infraction', $categorie)
            ->whereDate('date_entree_vigueur', '<=', $today)
            ->where(fn ($q) => $q->whereNull('date_fin_vigueur')->orWhereDate('date_fin_vigueur', '>=', $today))
            ->value('duree_heures');

        return $delai ?? self::DUREE_DEFAUT_HEURES;
    }
}
