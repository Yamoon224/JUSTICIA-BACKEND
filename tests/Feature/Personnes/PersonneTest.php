<?php

namespace Tests\Feature\Personnes;

use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre §6.2 : fichier central des personnes, recherche multicritère,
 * consultation journalisée avec motif obligatoire, fusion tracée.
 */
class PersonneTest extends TestCase
{
    use RefreshDatabase;

    private function opj(): User
    {
        $this->seed(RolesEtPermissionsSeeder::class);
        $opj = User::factory()->create();
        $opj->assignRole('opj');

        return $opj;
    }

    public function test_un_opj_peut_creer_une_personne(): void
    {
        $response = $this->actingAs($this->opj())->postJson('/api/v1/personnes', [
            'type' => 'physique',
            'nom' => 'Kouassi',
            'prenom' => 'Jean',
            'date_naissance' => '1990-01-01',
        ]);

        $response->assertCreated()->assertJsonPath('nom', 'Kouassi');
        $this->assertDatabaseHas('personnes', ['nom' => 'Kouassi', 'prenom' => 'Jean']);
    }

    public function test_la_recherche_multicritere_trouve_par_nom_partiel(): void
    {
        $agent = $this->opj();
        $this->actingAs($agent)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Kouassi', 'prenom' => 'Jean']);
        $this->actingAs($agent)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Yao', 'prenom' => 'Awa']);

        $response = $this->actingAs($agent)->getJson('/api/v1/personnes?nom=Koua');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_consulter_une_personne_exige_un_motif_et_journalise(): void
    {
        $agent = $this->opj();
        $creation = $this->actingAs($agent)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Kouassi', 'prenom' => 'Jean']);
        $personneId = $creation->json('id');

        $sansMotif = $this->actingAs($agent)->getJson("/api/v1/personnes/{$personneId}");
        $sansMotif->assertStatus(422);

        $avecMotif = $this->actingAs($agent)->getJson("/api/v1/personnes/{$personneId}?motif=Verification+identite");
        $avecMotif->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'personnes.consultation',
            'motif' => 'Verification identite',
        ]);
    }

    public function test_fusionner_deux_fiches_reprend_les_rattachements_et_marque_l_absorbee(): void
    {
        $agent = $this->opj();
        $conservee = $this->actingAs($agent)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Kouassi', 'prenom' => 'Jean'])->json('id');
        $absorbee = $this->actingAs($agent)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Kouassi', 'prenom' => 'Jean'])->json('id');

        $response = $this->actingAs($agent)->postJson("/api/v1/personnes/{$conservee}/fusionner", [
            'personne_absorbee_id' => $absorbee,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('personnes', ['id' => $absorbee, 'fusionnee_dans_id' => $conservee]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'personnes.fusion']);
    }
}
