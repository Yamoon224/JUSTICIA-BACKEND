<?php

namespace Tests\Feature\GardeAVue;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\GardeAVue\Actions\DetecterEcheancesGardeAVueAction;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Personnes\Models\Personne;
use App\Models\DelaiLegal;
use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\Unite;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre §6.1 : placement en garde à vue avec délai résolu depuis le
 * référentiel, régime mineur automatique, notification des droits,
 * prolongation, clôture avec issue obligatoire, moteur d'alertes (§6.11).
 */
class GardeAVueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

    private function opj(): User
    {
        $service = Service::query()->create(['code' => 'PJ', 'nom' => 'Police judiciaire', 'type' => 'police']);
        $ressort = Ressort::query()->create(['code' => 'TRIB', 'nom' => 'Tribunal', 'type' => 'tribunal']);
        $opj = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $opj->assignRole('opj');

        return $opj;
    }

    private function unite(): Unite
    {
        $ressort = Ressort::query()->create(['code' => 'RESS-UNITE-'.Str::random(4), 'nom' => 'Ressort unité', 'type' => 'tribunal']);

        return Unite::query()->create(['code' => 'U-'.Str::random(4), 'nom' => 'Commissariat', 'type' => 'police', 'ressort_id' => $ressort->id]);
    }

    private function opjDansAutreRessort(): User
    {
        $service = Service::query()->create(['code' => 'PJ-'.Str::random(4), 'nom' => 'Police judiciaire', 'type' => 'police']);
        $ressort = Ressort::query()->create(['code' => 'TRIB-'.Str::random(4), 'nom' => 'Autre tribunal', 'type' => 'tribunal']);
        $opj = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $opj->assignRole('opj');

        return $opj;
    }

    private function affaireAvecInfraction(User $agent, string $categorie): Affaire
    {
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');
        $infraction = Infraction::query()->create([
            'code' => 'INF-'.Str::random(6),
            'libelle' => 'Infraction test',
            'categorie' => $categorie,
            'date_entree_vigueur' => now(),
        ]);
        Affaire::query()->find($affaireId)->infractions()->attach($infraction->id);

        return Affaire::query()->find($affaireId);
    }

    public function test_le_placement_resout_la_duree_depuis_le_referentiel_selon_la_categorie(): void
    {
        $agent = $this->opj();
        DelaiLegal::query()->create([
            'code' => 'GAV_DELIT_TEST', 'libelle' => 'GAV délit', 'type_acte' => 'garde_a_vue',
            'categorie_infraction' => 'delit', 'duree_heures' => 48, 'date_entree_vigueur' => now(),
        ]);
        $affaire = $this->affaireAvecInfraction($agent, 'delit');
        $personne = Personne::query()->create(['identifiant_unique' => (string) Str::uuid(), 'type' => 'physique', 'nom' => 'Kouassi', 'date_naissance' => '1990-01-01']);
        $unite = $this->unite();

        $response = $this->actingAs($agent)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaire->id,
            'personne_id' => $personne->id,
            'unite_id' => $unite->id,
        ]);

        $response->assertCreated()->assertJsonPath('duree_heures', 48)->assertJsonPath('mineur', false);
    }

    public function test_le_regime_mineur_est_applique_automatiquement_selon_l_age(): void
    {
        $agent = $this->opj();
        $affaire = $this->affaireAvecInfraction($agent, 'delit');
        $mineur = Personne::query()->create([
            'identifiant_unique' => (string) Str::uuid(),
            'type' => 'physique',
            'nom' => 'Yao',
            'date_naissance' => now()->subYears(15)->toDateString(),
        ]);

        $response = $this->actingAs($agent)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaire->id,
            'personne_id' => $mineur->id,
            'unite_id' => $this->unite()->id,
        ]);

        $response->assertCreated()->assertJsonPath('mineur', true);
    }

    public function test_un_agent_hors_ressort_ne_peut_pas_placer_une_mesure_sur_l_affaire(): void
    {
        $proprietaire = $this->opj();
        $affaire = $this->affaireAvecInfraction($proprietaire, 'delit');
        $personne = Personne::query()->create(['identifiant_unique' => (string) Str::uuid(), 'type' => 'physique', 'nom' => 'Kouassi']);

        $intrus = $this->opjDansAutreRessort();

        $this->actingAs($intrus)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaire->id,
            'personne_id' => $personne->id,
            'unite_id' => $this->unite()->id,
        ])->assertForbidden();
    }

    public function test_un_agent_hors_ressort_ne_peut_ni_consulter_ni_cloturer_la_mesure(): void
    {
        $proprietaire = $this->opj();
        $mesure = $this->placerMesure($proprietaire);
        $intrus = $this->opjDansAutreRessort();

        $this->actingAs($intrus)->getJson("/api/v1/gav/mesures/{$mesure->id}")->assertForbidden();
        $this->actingAs($intrus)->postJson("/api/v1/gav/mesures/{$mesure->id}/cloturer", ['issue' => 'liberation'])
            ->assertForbidden();

        // Le propriétaire, lui, agit normalement sur sa propre mesure.
        $this->actingAs($proprietaire)->getJson("/api/v1/gav/mesures/{$mesure->id}")->assertOk();
    }

    public function test_un_droit_ne_peut_pas_etre_notifie_deux_fois(): void
    {
        $agent = $this->opj();
        $mesure = $this->placerMesure($agent);

        $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/droits", [
            'droit' => 'avocat', 'mode_de_remise' => 'oral',
        ])->assertOk();

        $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/droits", [
            'droit' => 'avocat', 'mode_de_remise' => 'oral',
        ])->assertStatus(500);
    }

    public function test_prolonger_repousse_l_echeance_et_journalise_l_autorisation(): void
    {
        $agent = $this->opj();
        $mesure = $this->placerMesure($agent);
        $finInitiale = $mesure->fin_prevue_at;

        $response = $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/prolonger", [
            'heures' => 24,
            'autorise_par_id' => $agent->id,
        ]);

        $response->assertOk()->assertJsonPath('statut', 'prolongee');
        $this->assertTrue(Carbon::parse($response->json('fin_prevue_at'))->equalTo($finInitiale->clone()->addHours(24)));
        $this->assertDatabaseHas('audit_logs', ['action' => 'gav.prolongation']);
    }

    public function test_la_cloture_exige_une_issue_valide_et_est_definitive(): void
    {
        $agent = $this->opj();
        $mesure = $this->placerMesure($agent);

        $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/cloturer", ['issue' => 'invalide'])
            ->assertStatus(422);

        $cloture = $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/cloturer", ['issue' => 'liberation']);
        $cloture->assertOk()->assertJsonPath('statut', 'terminee')->assertJsonPath('issue', 'liberation');

        // Une mesure déjà clôturée ne peut pas l'être une seconde fois.
        $this->actingAs($agent)->postJson("/api/v1/gav/mesures/{$mesure->id}/cloturer", ['issue' => 'liberation'])
            ->assertStatus(500);
    }

    public function test_le_moteur_d_alertes_qualifie_une_mesure_depassee(): void
    {
        $agent = $this->opj();
        $mesure = $this->placerMesure($agent);
        $mesure->update(['fin_prevue_at' => now()->subHour()]);

        $alertes = app(DetecterEcheancesGardeAVueAction::class)->executer();

        $this->assertCount(1, $alertes);
        $this->assertSame('depassement', $alertes->first()['niveau']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'gav.alerte']);
    }

    private function placerMesure(User $agent): MesureGardeAVue
    {
        $affaire = $this->affaireAvecInfraction($agent, 'delit');
        $personne = Personne::query()->create(['identifiant_unique' => (string) Str::uuid(), 'type' => 'physique', 'nom' => 'Kouassi']);

        $id = $this->actingAs($agent)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaire->id,
            'personne_id' => $personne->id,
            'unite_id' => $this->unite()->id,
        ])->json('id');

        return MesureGardeAVue::query()->findOrFail($id);
    }
}
