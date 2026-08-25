<?php

namespace Tests\Feature\Referentiels;

use App\Models\Infraction;
use App\Models\Juridiction;
use App\Models\Ressort;
use App\Models\Unite;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les référentiels de choix (§6.13) sont accessibles à tout agent
 * authentifié, sans habilitation particulière, et ne renvoient que les
 * infractions actuellement en vigueur.
 */
class ReferentielTest extends TestCase
{
    use RefreshDatabase;

    public function test_seules_les_infractions_en_vigueur_sont_listees(): void
    {
        $agent = User::factory()->create();

        Infraction::query()->create(['code' => 'ACTIVE', 'libelle' => 'Active', 'categorie' => 'delit', 'date_entree_vigueur' => now()->subYear()]);
        Infraction::query()->create([
            'code' => 'PERIMEE', 'libelle' => 'Périmée', 'categorie' => 'delit',
            'date_entree_vigueur' => now()->subYears(2), 'date_fin_vigueur' => now()->subYear(),
        ]);

        $response = $this->actingAs($agent)->getJson('/api/v1/referentiels/infractions');

        $response->assertOk();
        $codes = collect($response->json())->pluck('code');
        $this->assertTrue($codes->contains('ACTIVE'));
        $this->assertFalse($codes->contains('PERIMEE'));
    }

    public function test_les_unites_sont_listees(): void
    {
        $agent = User::factory()->create();
        $ressort = Ressort::query()->create(['code' => 'R1', 'nom' => 'Ressort', 'type' => 'tribunal']);
        Unite::query()->create(['code' => 'U1', 'nom' => 'Commissariat', 'type' => 'police', 'ressort_id' => $ressort->id]);

        $response = $this->actingAs($agent)->getJson('/api/v1/referentiels/unites');

        $response->assertOk()->assertJsonFragment(['code' => 'U1']);
    }

    public function test_les_juridictions_sont_listees(): void
    {
        $ressort = Ressort::query()->create(['code' => 'R2', 'nom' => 'Ressort', 'type' => 'tribunal']);
        $agent = User::factory()->create(['ressort_id' => $ressort->id]);
        Juridiction::query()->create(['code' => 'J1', 'nom' => 'Tribunal pilote', 'ressort_id' => $ressort->id]);

        $response = $this->actingAs($agent)->getJson('/api/v1/referentiels/juridictions');

        $response->assertOk()->assertJsonFragment(['code' => 'J1']);
    }

    /**
     * §6.7 : l'enrôlement (EnrolerAffaireAction) exige une juridiction —
     * un référentiel de démonstration sans aucune juridiction rendrait le
     * module Audiencement inutilisable dès l'installation.
     */
    public function test_le_seed_de_demonstration_fournit_au_moins_une_juridiction(): void
    {
        $this->seed(ReferentielsSeeder::class);

        $this->assertGreaterThan(0, Juridiction::query()->count());
    }
}
