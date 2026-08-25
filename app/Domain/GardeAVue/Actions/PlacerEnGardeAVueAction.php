<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Personnes\Models\Personne;
use App\Domain\Support\ResolveurDelaiLegal;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Placement en garde à vue (§6.1). La durée légale n'est jamais codée en
 * dur : elle est résolue depuis le référentiel `delais_legaux`
 * (ResolveurDelaiLegal), avec un repli explicite si aucune règle n'est
 * paramétrée (à ne jamais laisser silencieux en production — §11, réformes
 * intégrées par paramétrage). Le régime mineur (§6.1) est appliqué
 * automatiquement selon l'âge.
 */
class PlacerEnGardeAVueAction
{
    private const DUREE_DEFAUT_HEURES = 24;

    public function __construct(
        private readonly AuditService $audit,
        private readonly ResolveurDelaiLegal $delais,
    ) {}

    public function executer(Affaire $affaire, Personne $personne, Unite $unite, User $agent, ?Carbon $debut = null): MesureGardeAVue
    {
        $debutAt = $debut ?? now();
        $dureeHeures = $this->delais->dureeHeures('garde_a_vue', $affaire) ?? self::DUREE_DEFAUT_HEURES;
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
            // Explicite plutôt que de compter sur le défaut SQL de la
            // colonne : create() ne relit pas la ligne insérée, la valeur
            // par défaut resterait absente de la réponse API immédiate.
            'statut' => 'en_cours',
        ]);

        $this->audit->consigner('gav.placement', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'duree_heures' => $dureeHeures,
            'mineur' => $mineur,
        ]);

        return $mesure;
    }
}
