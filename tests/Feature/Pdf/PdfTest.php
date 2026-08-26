<?php

namespace Tests\Feature\Pdf;

use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Édition au format légal (§6.3 PV, §6.10 bulletin, §9). N'entre pas dans le
 * détail des règles métier déjà couvertes ailleurs (AffaireTest, CasierTest)
 * — vérifie seulement que le document produit est un PDF valide, avec la
 * même habilitation que sa représentation JSON.
 */
class PdfTest extends TestCase
{
    use RefreshDatabase;

    private function ressort(string $suffixe = 'A'): Ressort
    {
        return Ressort::query()->create(['code' => "TRIB-{$suffixe}", 'nom' => "Tribunal {$suffixe}", 'type' => 'tribunal']);
    }

    private function agent(Ressort $ressort, string $codeService, string $typeService, string $role): User
    {
        $service = Service::query()->firstOrCreate(['code' => $codeService], ['nom' => $codeService, 'type' => $typeService]);
        $agent = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $agent->assignRole($role);

        return $agent;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

    private function assertContenuEstUnPdf(string $contenu): void
    {
        $this->assertStringStartsWith('%PDF-', $contenu);
    }

    public function test_le_pdf_d_un_pv_signe_est_telechargeable_par_un_agent_du_ressort(): void
    {
        $ressort = $this->ressort();
        $agent = $this->agent($ressort, 'PJ', 'police', 'opj');
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');
        $pvId = $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/proces-verbaux", [
            'type' => 'interpellation', 'contenu' => 'Constat sur place.',
        ])->json('id');
        $this->actingAs($agent)->postJson("/api/v1/proces-verbaux/{$pvId}/signer")->assertOk();

        $pdf = $this->actingAs($agent)->get("/api/v1/proces-verbaux/{$pvId}/pdf");

        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertContenuEstUnPdf($pdf->getContent());
    }

    public function test_le_pdf_d_un_pv_non_signe_reste_telechargeable(): void
    {
        $ressort = $this->ressort();
        $agent = $this->agent($ressort, 'PJ', 'police', 'opj');
        $affaireId = $this->actingAs($agent)->postJson('/api/v1/affaires', [])->json('id');
        $pvId = $this->actingAs($agent)->postJson("/api/v1/affaires/{$affaireId}/proces-verbaux", [
            'type' => 'audition', 'contenu' => 'Version provisoire.',
        ])->json('id');

        $pdf = $this->actingAs($agent)->get("/api/v1/proces-verbaux/{$pvId}/pdf");

        $pdf->assertOk();
        $this->assertContenuEstUnPdf($pdf->getContent());
    }

    public function test_un_agent_hors_ressort_ne_peut_pas_telecharger_le_pdf_du_pv(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $agentA = $this->agent($ressortA, 'PJ', 'police', 'opj');
        $affaireId = $this->actingAs($agentA)->postJson('/api/v1/affaires', [])->json('id');
        $pvId = $this->actingAs($agentA)->postJson("/api/v1/affaires/{$affaireId}/proces-verbaux", [
            'type' => 'interpellation', 'contenu' => 'Confidentiel.',
        ])->json('id');

        $intrus = $this->agent($ressortB, 'PJ', 'police', 'opj');
        $this->actingAs($intrus)->get("/api/v1/proces-verbaux/{$pvId}/pdf")->assertForbidden();
    }

    public function test_le_pdf_d_un_bulletin_exige_le_motif_et_l_habilitation_nominative(): void
    {
        $ressort = $this->ressort();
        $personneId = $this->actingAs($this->agent($ressort, 'PJ', 'police', 'opj'))
            ->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => Str::random(6)])->json('id');

        // Le greffier a casier.gerer mais pas casier.consulter_nominatif
        // (RolesEtPermissionsSeeder, cf. CasierTest) : même restriction que
        // pour la représentation JSON.
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->actingAs($greffier)
            ->get("/api/v1/casier/personnes/{$personneId}/bulletin/pdf?type=b1&motif=Test")
            ->assertForbidden();

        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->get("/api/v1/casier/personnes/{$personneId}/bulletin/pdf?type=b1")
            ->assertStatus(422);

        $pdf = $this->actingAs($agentCasier)
            ->get("/api/v1/casier/personnes/{$personneId}/bulletin/pdf?type=b1&motif=Recrutement");
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertContenuEstUnPdf($pdf->getContent());
    }

    public function test_chaque_edition_de_bulletin_pdf_reste_une_consultation_journalisee(): void
    {
        $ressort = $this->ressort();
        $personneId = $this->actingAs($this->agent($ressort, 'PJ', 'police', 'opj'))
            ->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => Str::random(6)])->json('id');
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');

        $this->actingAs($agentCasier)
            ->get("/api/v1/casier/personnes/{$personneId}/bulletin/pdf?type=b2&motif=Verification")
            ->assertOk();

        $this->assertDatabaseHas('casier_consultations', ['personne_id' => $personneId, 'type_bulletin' => 'b2', 'motif' => 'Verification']);
    }
}
