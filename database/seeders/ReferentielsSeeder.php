<?php

namespace Database\Seeders;

use App\Models\DelaiLegal;
use App\Models\Infraction;
use App\Models\MotifClassement;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\TypePeine;
use App\Models\Unite;
use Illuminate\Database\Seeder;

/**
 * Amorce les référentiels nationaux (§6.13) avec un jeu minimal
 * d'exemples, à valider et compléter par la chancellerie avant recette
 * (§14). Ne remplace pas le plan de reprise de l'existant (§11).
 */
class ReferentielsSeeder extends Seeder
{
    public function run(): void
    {
        $national = Ressort::query()->create(['code' => 'NAT', 'nom' => 'Ressort national', 'type' => 'national']);
        $courAppel = Ressort::query()->create(['code' => 'CA-01', 'nom' => "Cour d'appel pilote", 'type' => 'cour_appel', 'parent_id' => $national->id]);
        $tribunal = Ressort::query()->create(['code' => 'TRIB-01', 'nom' => 'Tribunal de première instance pilote', 'type' => 'tribunal', 'parent_id' => $courAppel->id]);

        Unite::query()->create(['code' => 'UNITE-01', 'nom' => 'Commissariat central pilote', 'type' => 'police', 'ressort_id' => $tribunal->id]);

        collect([
            ['code' => 'PJ', 'nom' => 'Police judiciaire', 'type' => 'police'],
            ['code' => 'GN', 'nom' => 'Gendarmerie', 'type' => 'gendarmerie'],
            ['code' => 'PARQ', 'nom' => 'Parquet', 'type' => 'parquet'],
            ['code' => 'INSTR', 'nom' => "Cabinet d'instruction", 'type' => 'instruction'],
            ['code' => 'JURID', 'nom' => 'Juridiction de jugement', 'type' => 'juridiction'],
            ['code' => 'GREFFE', 'nom' => 'Greffe', 'type' => 'greffe'],
            ['code' => 'PENIT', 'nom' => 'Administration pénitentiaire', 'type' => 'penitentiaire'],
            ['code' => 'CASIER', 'nom' => 'Service du casier judiciaire', 'type' => 'casier'],
            ['code' => 'DSI', 'nom' => 'DSI Justice', 'type' => 'dsi'],
        ])->each(fn (array $service) => Service::query()->create($service));

        $today = now()->toDateString();

        collect([
            ['code' => 'CP-311', 'libelle' => 'Vol simple', 'categorie' => 'delit'],
            ['code' => 'CP-312', 'libelle' => 'Vol aggravé', 'categorie' => 'crime'],
            ['code' => 'CP-222-11', 'libelle' => 'Coups et blessures volontaires', 'categorie' => 'delit'],
            ['code' => 'CP-221-1', 'libelle' => 'Homicide volontaire', 'categorie' => 'crime'],
            ['code' => 'CP-313-1', 'libelle' => 'Escroquerie', 'categorie' => 'delit'],
            ['code' => 'CR-R625', 'libelle' => 'Violence légère', 'categorie' => 'contravention'],
        ])->each(fn (array $infraction) => Infraction::query()->create([
            ...$infraction,
            'date_entree_vigueur' => $today,
        ]));

        collect([
            ['code' => 'INFRACTION_INSUFFISAMMENT_CARACTERISEE', 'libelle' => 'Infraction insuffisamment caractérisée'],
            ['code' => 'AUTEUR_NON_IDENTIFIE', 'libelle' => 'Auteur non identifié'],
            ['code' => 'PRESCRIPTION', 'libelle' => "Prescription de l'action publique"],
            ['code' => 'MEDIATION_REUSSIE', 'libelle' => 'Médiation pénale réussie'],
            ['code' => 'DEFAUT_DE_PLAINTE', 'libelle' => 'Défaut de plainte préalable requise'],
        ])->each(fn (array $motif) => MotifClassement::query()->create($motif));

        collect([
            ['code' => 'EMPRISONNEMENT_FERME', 'libelle' => 'Emprisonnement ferme', 'categorie' => 'emprisonnement'],
            ['code' => 'EMPRISONNEMENT_SURSIS', 'libelle' => 'Emprisonnement avec sursis', 'categorie' => 'sursis'],
            ['code' => 'AMENDE', 'libelle' => 'Amende', 'categorie' => 'amende'],
            ['code' => 'TIG', 'libelle' => "Travail d'intérêt général", 'categorie' => 'tig'],
            ['code' => 'INTERDICTION_PROFESSIONNELLE', 'libelle' => 'Interdiction professionnelle', 'categorie' => 'complementaire'],
            ['code' => 'DISPENSE_DE_PEINE', 'libelle' => 'Dispense de peine', 'categorie' => 'dispense'],
        ])->each(fn (array $type) => TypePeine::query()->create($type));

        // §6.1, §6.11 : durées légales de garde à vue par catégorie
        // d'infraction, avec seuils d'alerte (2h / 30 min avant échéance).
        // Valeurs d'exemple à valider par la chancellerie avant recette.
        collect([
            ['code' => 'GAV_CONTRAVENTION', 'categorie_infraction' => 'contravention', 'duree_heures' => 24],
            ['code' => 'GAV_DELIT', 'categorie_infraction' => 'delit', 'duree_heures' => 48],
            ['code' => 'GAV_CRIME', 'categorie_infraction' => 'crime', 'duree_heures' => 96],
        ])->each(fn (array $delai) => DelaiLegal::query()->create([
            ...$delai,
            'libelle' => "Garde à vue — {$delai['categorie_infraction']}",
            'type_acte' => 'garde_a_vue',
            'alerte_avant_heures' => 2,
            'alerte_avant_minutes' => 30,
            'date_entree_vigueur' => $today,
        ]));

        // §6.6, §6.11 : détention provisoire, exceptionnelle et strictement
        // limitée dans le temps — valeurs d'exemple à valider par la
        // chancellerie avant recette.
        collect([
            ['code' => 'DP_DELIT', 'categorie_infraction' => 'delit', 'duree_jours' => 120],
            ['code' => 'DP_CRIME', 'categorie_infraction' => 'crime', 'duree_jours' => 365],
        ])->each(fn (array $delai) => DelaiLegal::query()->create([
            ...$delai,
            'libelle' => "Détention provisoire — {$delai['categorie_infraction']}",
            'type_acte' => 'detention_provisoire',
            'alerte_avant_heures' => 15 * 24,
            'date_entree_vigueur' => $today,
        ]));

        // §6.7 : délai de recours contre une ordonnance de règlement.
        DelaiLegal::query()->create([
            'code' => 'RECOURS_ORDONNANCE',
            'libelle' => 'Recours contre une ordonnance de règlement',
            'type_acte' => 'ordonnance_reglement',
            'duree_jours' => 10,
            'date_entree_vigueur' => $today,
        ]);

        // §6.7, §6.8 : délai d'appel/opposition/pourvoi contre un jugement.
        DelaiLegal::query()->create([
            'code' => 'RECOURS_JUGEMENT',
            'libelle' => 'Recours contre une décision de jugement',
            'type_acte' => 'recours_jugement',
            'duree_jours' => 15,
            'date_entree_vigueur' => $today,
        ]);
    }
}
