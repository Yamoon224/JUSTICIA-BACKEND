<?php

namespace Database\Seeders;

use App\Models\Infraction;
use App\Models\MotifClassement;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\TypePeine;
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
        Ressort::query()->create(['code' => 'TRIB-01', 'nom' => 'Tribunal de première instance pilote', 'type' => 'tribunal', 'parent_id' => $courAppel->id]);

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
    }
}
