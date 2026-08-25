<?php

namespace Tests\Feature\Referentiels;

use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Unite;
use App\Models\User;
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
}
