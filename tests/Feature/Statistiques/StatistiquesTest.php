<?php

namespace Tests\Feature\Statistiques;

use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\Unite;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre §6.11-6.12 (tableau de bord agrégé) : cloisonnement par ressort
 * pour un chef de juridiction (§8), agrégat national réservé à
 * l'administrateur, et le caractère national assumé du casier (§6.10)
 * même dans une vue par ressort.
 */
class StatistiquesTest extends TestCase
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
        $this->seed(ReferentielsSeeder::class);
    }

    private function unite(Ressort $ressort): Unite
    {
        return Unite::query()->create([
            'code' => 'UNITE-'.Str::random(8),
            'nom' => 'Commissariat de test',
            'type' => 'police',
            'ressort_id' => $ressort->id,
        ]);
    }

    /**
     * Ouvre une affaire dans le ressort donné, via l'API, pour peupler le
     * tableau de bord de données réelles plutôt que d'insertions directes.
     */
    private function ouvrirAffaire(Ressort $ressort): int
    {
        $opj = $this->agent($ressort, 'PJ-'.$ressort->code, 'police', 'opj');

        return $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');
    }

    public function test_un_agent_sans_habilitation_statistiques_est_refuse(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');

        $this->actingAs($opj)->getJson('/api/v1/statistiques/tableau-de-bord')->assertForbidden();
    }

    public function test_un_chef_de_juridiction_ne_voit_que_son_propre_ressort(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $this->ouvrirAffaire($ressortA);
        $this->ouvrirAffaire($ressortA);
        $this->ouvrirAffaire($ressortB);

        $chefA = $this->agent($ressortA, 'JURID', 'juridiction', 'chef_juridiction');

        $reponse = $this->actingAs($chefA)->getJson('/api/v1/statistiques/tableau-de-bord');
        $reponse->assertOk();
        $reponse->assertJsonPath('ressort_id', $ressortA->id);
        $reponse->assertJsonPath('affaires.total', 2);
    }

    public function test_un_chef_de_juridiction_ne_peut_pas_forcer_un_autre_ressort(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $this->ouvrirAffaire($ressortA);
        $this->ouvrirAffaire($ressortB);
        $this->ouvrirAffaire($ressortB);

        $chefA = $this->agent($ressortA, 'JURID', 'juridiction', 'chef_juridiction');

        // Un ressort_id fourni par un non-administrateur est ignoré (§8) :
        // il reste cantonné au sien, quoi qu'il transmette.
        $reponse = $this->actingAs($chefA)->getJson("/api/v1/statistiques/tableau-de-bord?ressort_id={$ressortB->id}");
        $reponse->assertOk();
        $reponse->assertJsonPath('ressort_id', $ressortA->id);
        $reponse->assertJsonPath('affaires.total', 1);
    }

    public function test_un_administrateur_peut_voir_un_ressort_choisi_ou_l_agregat_national(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $this->ouvrirAffaire($ressortA);
        $this->ouvrirAffaire($ressortB);
        $this->ouvrirAffaire($ressortB);

        $dsi = Service::query()->where('code', 'DSI')->firstOrFail();
        $admin = User::factory()->create(['service_id' => $dsi->id, 'ressort_id' => null]);
        $admin->assignRole('administrateur');

        $vueRessortB = $this->actingAs($admin)->getJson("/api/v1/statistiques/tableau-de-bord?ressort_id={$ressortB->id}");
        $vueRessortB->assertOk()->assertJsonPath('ressort_id', $ressortB->id)->assertJsonPath('affaires.total', 2);

        $vueNationale = $this->actingAs($admin)->getJson('/api/v1/statistiques/tableau-de-bord');
        $vueNationale->assertOk();
        $this->assertNull($vueNationale->json('ressort_id'));
        $this->assertSame(3, $vueNationale->json('affaires.total'));
    }

    public function test_les_mesures_de_garde_a_vue_en_cours_et_en_echeance_sont_comptees(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');
        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Test'])->json('id');

        $this->actingAs($opj)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaireId,
            'personne_id' => $personneId,
            'unite_id' => $this->unite($ressort)->id,
            'debut_at' => now()->subHours(3)->toIso8601String(),
        ])->assertCreated();

        $mesure = MesureGardeAVue::latest('id')->first();
        // Échéance déjà dépassée pour ce test, sans clôturer la mesure.
        $mesure->update(['fin_prevue_at' => now()->subHour()]);

        $chef = $this->agent($ressort, 'JURID', 'juridiction', 'chef_juridiction');
        $reponse = $this->actingAs($chef)->getJson('/api/v1/statistiques/tableau-de-bord');

        $reponse->assertOk();
        $reponse->assertJsonPath('garde_a_vue.en_cours', 1);
        $reponse->assertJsonPath('garde_a_vue.echeances_depassees', 1);
    }

    public function test_le_delai_moyen_de_garde_a_vue_se_calcule_en_heures(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');
        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Test'])->json('id');

        $mesureId = $this->actingAs($opj)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaireId,
            'personne_id' => $personneId,
            'unite_id' => $this->unite($ressort)->id,
            'debut_at' => now()->subHours(10)->toIso8601String(),
        ])->json('id');

        $this->actingAs($opj)->postJson("/api/v1/gav/mesures/{$mesureId}/cloturer", ['issue' => 'liberation'])->assertOk();
        MesureGardeAVue::find($mesureId)->update(['fin_reelle_at' => now()]);

        $chef = $this->agent($ressort, 'JURID', 'juridiction', 'chef_juridiction');
        $reponse = $this->actingAs($chef)->getJson('/api/v1/statistiques/tableau-de-bord');

        $reponse->assertOk();
        $this->assertEqualsWithDelta(10.0, $reponse->json('delais_moyens_jours.garde_a_vue_heures'), 0.2);
    }

    public function test_le_casier_reste_national_meme_dans_une_vue_par_ressort(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $chefA = $this->agent($ressortA, 'JURID', 'juridiction', 'chef_juridiction');
        $chefB = $this->agent($ressortB, 'JURID', 'juridiction', 'chef_juridiction');

        $reponseA = $this->actingAs($chefA)->getJson('/api/v1/statistiques/tableau-de-bord');
        $reponseB = $this->actingAs($chefB)->getJson('/api/v1/statistiques/tableau-de-bord');

        $reponseA->assertOk();
        $reponseB->assertOk();
        $this->assertSame($reponseA->json('casier'), $reponseB->json('casier'));
    }

    public function test_le_referentiel_des_ressorts_est_accessible(): void
    {
        $ressort = $this->ressort();
        $agent = $this->agent($ressort, 'PJ', 'police', 'opj');

        $this->actingAs($agent)->getJson('/api/v1/referentiels/ressorts')->assertOk();
    }
}
