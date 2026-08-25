<?php

namespace Tests\Feature\Affaires;

use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre §6.3 (dossier d'affaire, procès-verbaux) et §6.4 (scellés), ainsi
 * que le cloisonnement des habilitations par ressort (§8).
 */
class AffaireTest extends TestCase
{
    use RefreshDatabase;

    private function opjDansRessort(Ressort $ressort): User
    {
        $service = Service::query()->firstOrCreate(['code' => 'PJ'], ['nom' => 'Police judiciaire', 'type' => 'police']);
        $opj = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $opj->assignRole('opj');

        return $opj;
    }

    private function ressort(string $suffixe = 'A'): Ressort
    {
        return Ressort::query()->create(['code' => "TRIB-{$suffixe}", 'nom' => "Tribunal {$suffixe}", 'type' => 'tribunal']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

    public function test_ouvrir_une_affaire_genere_un_numero_unique_dans_le_ressort_de_l_agent(): void
    {
        $agent = $this->opjDansRessort($this->ressort());

        $response = $this->actingAs($agent)->postJson('/api/v1/affaires', [
            'description' => 'Vol avec effraction',
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->json('numero_affaire'));
        $this->assertDatabaseHas('affaires', ['ressort_id' => $agent->ressort_id, 'statut' => 'ouverte']);
    }

    public function test_un_agent_ne_voit_pas_une_affaire_hors_de_son_ressort(): void
    {
        $agentA = $this->opjDansRessort($this->ressort('A'));
        $agentB = $this->opjDansRessort($this->ressort('B'));

        $affaireId = $this->actingAs($agentA)->postJson('/api/v1/affaires', [])->json('id');

        $this->actingAs($agentB)->getJson("/api/v1/affaires/{$affaireId}")->assertForbidden();
        $this->actingAs($agentA)->getJson("/api/v1/affaires/{$affaireId}")->assertOk();
    }

    public function test_un_pv_signe_devient_immuable_et_se_corrige_par_rectificatif(): void
    {
        $agent = $this->opjDansRessort($this->ressort());
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');

        $pv = $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/proces-verbaux", [
            'type' => 'interpellation',
            'contenu' => 'Version initiale',
        ]);
        $pv->assertCreated();
        $pvId = $pv->json('id');

        $signature = $this->actingAs($agent)->postJson("/api/v1/proces-verbaux/{$pvId}/signer");
        $signature->assertOk()->assertJsonPath('signe', true);

        // Un PV signé est immuable au niveau modèle (protégé par le contrat
        // Signable), la rectification passe par un nouveau PV référencé.
        $rectificatif = $this->actingAs($agent)->postJson("/api/v1/proces-verbaux/{$pvId}/rectifier", [
            'contenu' => 'Version corrigée',
        ]);
        $rectificatif->assertCreated();
        $this->assertDatabaseHas('proces_verbaux', [
            'id' => $rectificatif->json('id'),
            'rectifie_par_pv_id' => $pvId,
            'contenu' => 'Version corrigée',
        ]);
        $this->assertDatabaseHas('proces_verbaux', ['id' => $pvId, 'contenu' => 'Version initiale']);
    }

    public function test_la_chaine_de_conservation_d_un_scelle_trace_chaque_mouvement(): void
    {
        $agent = $this->opjDansRessort($this->ressort());
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');

        $scelle = $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/scelles", [
            'numero_scelle' => 'SC-0001',
            'description' => 'Arme blanche',
        ]);
        $scelle->assertCreated()->assertJsonPath('statut', 'en_depot');
        $this->assertCount(1, $scelle->json('mouvements'));

        $mouvement = $this->actingAs($agent)->postJson("/api/v1/scelles/{$scelle->json('id')}/mouvements", [
            'type' => 'sortie_expertise',
            'motif' => 'Expertise balistique',
        ]);
        $mouvement->assertOk()->assertJsonPath('statut', 'sorti_expertise');
        $this->assertCount(2, $mouvement->json('mouvements'));
    }

    public function test_transmettre_au_parquet_change_le_statut_et_est_journalise(): void
    {
        $agent = $this->opjDansRessort($this->ressort());
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');

        $response = $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet");

        $response->assertOk()->assertJsonPath('statut', 'transmise_parquet');
        $this->assertDatabaseHas('audit_logs', ['action' => 'affaires.transmission_parquet']);

        // Une affaire déjà transmise ne peut pas l'être une seconde fois.
        $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertStatus(500);
    }

    public function test_ouvrir_une_affaire_avec_plusieurs_infractions_les_rattache_toutes(): void
    {
        $agent = $this->opjDansRessort($this->ressort());
        $delit = Infraction::query()->create(['code' => 'D1', 'libelle' => 'Delit', 'categorie' => 'delit', 'date_entree_vigueur' => now()]);
        $crime = Infraction::query()->create(['code' => 'C1', 'libelle' => 'Crime', 'categorie' => 'crime', 'date_entree_vigueur' => now()]);

        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [
            'infractions' => [$delit->id, $crime->id],
        ])->json('id');

        $this->assertDatabaseHas('affaire_infraction', ['affaire_id' => $affaireId, 'infraction_id' => $crime->id]);
        $this->assertDatabaseHas('affaire_infraction', ['affaire_id' => $affaireId, 'infraction_id' => $delit->id]);
    }
}
