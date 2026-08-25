<?php

namespace Tests\Feature\Parquet;

use App\Domain\Parquet\Models\DossierParquet;
use App\Models\MotifClassement;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre §6.5 (bureau des arrivées, orientation des poursuites), ainsi que
 * le cloisonnement des habilitations par ressort (§8) sur les dossiers
 * parquet.
 */
class ParquetTest extends TestCase
{
    use RefreshDatabase;

    private function ressort(string $suffixe = 'A'): Ressort
    {
        return Ressort::query()->create(['code' => "TRIB-{$suffixe}", 'nom' => "Tribunal {$suffixe}", 'type' => 'tribunal']);
    }

    private function opjDansRessort(Ressort $ressort): User
    {
        $service = Service::query()->firstOrCreate(['code' => 'PJ'], ['nom' => 'Police judiciaire', 'type' => 'police']);
        $opj = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $opj->assignRole('opj');

        return $opj;
    }

    private function procureurDansRessort(Ressort $ressort): User
    {
        $service = Service::query()->firstOrCreate(['code' => 'PARQ'], ['nom' => 'Parquet', 'type' => 'parquet']);
        $procureur = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $procureur->assignRole('procureur');

        return $procureur;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

    private function transmettreUneAffaire(Ressort $ressort): int
    {
        $opj = $this->opjDansRessort($ressort);
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();

        return $affaireId;
    }

    public function test_la_transmission_au_parquet_cree_un_dossier_au_bureau_des_arrivees(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);

        $dossiers = $this->actingAs($procureur)->getJson('/api/v1/parquet/dossiers?filtre=non_affectes');

        $dossiers->assertOk();
        $this->assertCount(1, $dossiers->json('data'));
        $this->assertSame($affaireId, $dossiers->json('data.0.affaire.id'));
        $this->assertDatabaseHas('dossiers_parquet', ['affaire_id' => $affaireId, 'magistrat_id' => null]);
    }

    public function test_un_procureur_hors_ressort_ne_voit_pas_le_dossier(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $affaireId = $this->transmettreUneAffaire($ressortA);
        $procureurB = $this->procureurDansRessort($ressortB);

        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $this->actingAs($procureurB)->getJson("/api/v1/parquet/dossiers/{$dossierId}")->assertForbidden();
    }

    public function test_affectation_puis_orientation_classement_sans_suite_cloture_l_affaire(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);
        $motif = MotifClassement::query()->create(['code' => 'TEST', 'libelle' => 'Motif de test']);

        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $affectation = $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierId}/affecter", [
            'magistrat_id' => $procureur->id,
        ]);
        $affectation->assertOk()->assertJsonPath('magistrat_id', $procureur->id);

        $orientation = $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierId}/orienter", [
            'orientation' => 'classement_sans_suite',
            'motif_classement_id' => $motif->id,
        ]);
        $orientation->assertOk()->assertJsonPath('orientation', 'classement_sans_suite');

        $this->assertDatabaseHas('affaires', ['id' => $affaireId, 'statut' => 'classee_sans_suite']);
    }

    public function test_on_ne_peut_pas_affecter_un_agent_qui_n_est_pas_procureur_du_ressort(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);
        $opjAutreProfil = $this->opjDansRessort($ressort);
        $procureurAutreRessort = $this->procureurDansRessort($this->ressort('B'));

        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $this->actingAs($procureur)
            ->postJson("/api/v1/parquet/dossiers/{$dossierId}/affecter", ['magistrat_id' => $opjAutreProfil->id])
            ->assertUnprocessable();

        $this->actingAs($procureur)
            ->postJson("/api/v1/parquet/dossiers/{$dossierId}/affecter", ['magistrat_id' => $procureurAutreRessort->id])
            ->assertUnprocessable();

        $this->assertDatabaseHas('dossiers_parquet', ['id' => $dossierId, 'magistrat_id' => null]);
    }

    public function test_un_classement_sans_suite_sans_motif_est_rejete(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);
        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $response = $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierId}/orienter", [
            'orientation' => 'classement_sans_suite',
        ]);

        $response->assertUnprocessable();
    }

    public function test_ouverture_d_information_met_a_jour_le_statut_de_l_affaire(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);
        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierId}/orienter", [
            'orientation' => 'ouverture_information',
        ])->assertOk();

        $this->assertDatabaseHas('affaires', ['id' => $affaireId, 'statut' => 'information_ouverte']);
    }

    public function test_une_requisition_est_enregistree_et_journalisee(): void
    {
        $ressort = $this->ressort();
        $affaireId = $this->transmettreUneAffaire($ressort);
        $procureur = $this->procureurDansRessort($ressort);
        $dossierId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');

        $requisition = $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierId}/requisitions", [
            'type' => 'placement_detention',
            'contenu' => 'Réquisition de placement en détention provisoire.',
        ]);

        $requisition->assertCreated();
        $this->assertDatabaseHas('requisitions', ['dossier_parquet_id' => $dossierId, 'type' => 'placement_detention']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'parquet.requisition']);
    }
}
