<?php

namespace Tests\Feature\Alertes;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Alertes\Models\Alerte;
use App\Domain\GardeAVue\Actions\DetecterEcheancesGardeAVueAction;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Instruction\Actions\DetecterEcheancesDetentionAction;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Instruction\Models\MesureSurete;
use App\Domain\Parquet\Models\DossierParquet;
use App\Domain\Personnes\Models\Personne;
use App\Models\Infraction;
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
 * §6.1, §6.11 : le moteur de délais qualifiait déjà les échéances
 * (DetecterEcheancesGardeAVueAction, DetecterEcheancesDetentionAction) —
 * couvre ici le routage effectif vers un destinataire humain, jusqu'ici
 * seulement journalisé (cf. échange avec l'utilisateur : « notifications
 * réelles des alertes »).
 */
class AlerteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

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

    private function unite(Ressort $ressort): Unite
    {
        return Unite::query()->create(['code' => 'U-'.Str::random(4), 'nom' => 'Commissariat', 'type' => 'police', 'ressort_id' => $ressort->id]);
    }

    private function placerMesureGav(User $opj, Ressort $ressort): MesureGardeAVue
    {
        $infraction = Infraction::query()->create([
            'code' => 'INF-'.Str::random(6), 'libelle' => 'Test', 'categorie' => 'delit', 'date_entree_vigueur' => now(),
        ]);
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', ['infractions' => [$infraction->id]])->json('id');
        $personneId = Personne::query()->create(['identifiant_unique' => (string) Str::uuid(), 'type' => 'physique', 'nom' => 'Test'])->id;

        $mesureId = $this->actingAs($opj)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $affaireId, 'personne_id' => $personneId, 'unite_id' => $this->unite($ressort)->id,
        ])->json('id');

        return MesureGardeAVue::query()->findOrFail($mesureId);
    }

    public function test_une_echeance_de_gav_proche_alerte_l_opj_et_le_chef_d_unite_du_ressort(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $chefUnite = $this->agent($ressort, 'PJ', 'police', 'chef_unite');
        $mesure = $this->placerMesureGav($opj, $ressort);
        $mesure->update(['fin_prevue_at' => now()->addHour()]); // < 2h : niveau "information"

        app(DetecterEcheancesGardeAVueAction::class)->executer();

        $this->assertDatabaseHas('alertes', [
            'alertable_type' => (new MesureGardeAVue())->getMorphClass(), 'alertable_id' => $mesure->id,
            'destinataire_id' => $opj->id, 'niveau' => 'information',
        ]);
        $this->assertDatabaseHas('alertes', [
            'alertable_id' => $mesure->id, 'destinataire_id' => $chefUnite->id, 'niveau' => 'information',
        ]);
    }

    public function test_un_chef_d_unite_d_un_autre_ressort_n_est_pas_alerte(): void
    {
        $ressortA = $this->ressort('A');
        $ressortB = $this->ressort('B');
        $opj = $this->agent($ressortA, 'PJ', 'police', 'opj');
        $chefUniteAutreRessort = $this->agent($ressortB, 'PJ', 'police', 'chef_unite');
        $mesure = $this->placerMesureGav($opj, $ressortA);
        $mesure->update(['fin_prevue_at' => now()->subMinute()]); // dépassement

        app(DetecterEcheancesGardeAVueAction::class)->executer();

        $this->assertDatabaseMissing('alertes', ['destinataire_id' => $chefUniteAutreRessort->id]);
    }

    public function test_l_alerte_n_est_pas_dupliquee_tant_que_le_niveau_ne_change_pas_mais_l_escalade_cree_une_nouvelle_alerte(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $mesure = $this->placerMesureGav($opj, $ressort);
        $mesure->update(['fin_prevue_at' => now()->addHour()]);

        app(DetecterEcheancesGardeAVueAction::class)->executer();
        app(DetecterEcheancesGardeAVueAction::class)->executer();

        $this->assertSame(1, Alerte::query()->where('destinataire_id', $opj->id)->where('niveau', 'information')->count());

        // La mesure s'aggrave : dépassement — niveau différent, nouvelle alerte.
        $mesure->update(['fin_prevue_at' => now()->subMinute()]);
        app(DetecterEcheancesGardeAVueAction::class)->executer();

        $this->assertSame(2, Alerte::query()->where('destinataire_id', $opj->id)->count());
        $this->assertDatabaseHas('alertes', ['destinataire_id' => $opj->id, 'niveau' => 'depassement']);
    }

    public function test_une_detention_provisoire_en_depassement_alerte_le_juge_affecte(): void
    {
        $ressort = $this->ressort();
        $this->seed(ReferentielsSeeder::class);
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infractionCrime = Infraction::query()->where('categorie', 'crime')->firstOrFail();
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', ['infractions' => [$infractionCrime->id]])->json('id');
        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'Test'])->json('id');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", ['personne_id' => $personneId, 'statut' => 'suspect'])->assertOk();
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();
        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", ['orientation' => 'ouverture_information'])->assertOk();
        $dossierId = DossierInstruction::query()->where('affaire_id', $affaireId)->value('id');
        $juge = $this->agent($ressort, 'INSTR', 'instruction', 'juge_instruction');
        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$dossierId}/affecter", ['juge_id' => $juge->id])->assertOk();
        $mesureId = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$dossierId}/detention-provisoire", ['personne_id' => $personneId])->json('id');
        MesureSurete::query()->where('id', $mesureId)->update(['fin_prevue_at' => now()->subDay()]);

        app(DetecterEcheancesDetentionAction::class)->executer();

        $this->assertDatabaseHas('alertes', [
            'type' => 'detention_echeance', 'niveau' => 'depassement', 'destinataire_id' => $juge->id,
        ]);
    }

    public function test_un_agent_ne_voit_et_ne_cloture_que_ses_propres_alertes(): void
    {
        $ressort = $this->ressort();
        $destinataire = $this->agent($ressort, 'PJ', 'police', 'opj');
        $tiers = $this->agent($ressort, 'PJ', 'police', 'opj');
        $mesure = $this->placerMesureGav($destinataire, $ressort);
        $alerte = Alerte::query()->create([
            'type' => 'gav_echeance', 'niveau' => 'information', 'message' => 'Test',
            'alertable_type' => $mesure->getMorphClass(), 'alertable_id' => $mesure->id, 'destinataire_id' => $destinataire->id,
        ]);

        $this->actingAs($tiers)->getJson('/api/v1/alertes')->assertJsonCount(0, 'data');
        $this->actingAs($destinataire)->getJson('/api/v1/alertes')->assertJsonCount(1, 'data');

        $this->actingAs($tiers)->postJson("/api/v1/alertes/{$alerte->id}/lire")->assertForbidden();

        $lecture = $this->actingAs($destinataire)->postJson("/api/v1/alertes/{$alerte->id}/lire");
        $lecture->assertOk()->assertJsonPath('lue', true);

        // Une alerte déjà lue ne peut pas l'être une seconde fois (même
        // convention que les autres clôtures définitives du socle).
        $this->actingAs($destinataire)->postJson("/api/v1/alertes/{$alerte->id}/lire")->assertStatus(500);
    }
}
