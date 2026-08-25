<?php

namespace Tests\Feature\Instruction;

use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Parquet\Models\DossierParquet;
use App\Domain\Personnes\Models\Personne;
use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre §6.6 (dossier d'information, mise en examen, mesures de sûreté,
 * ordonnance de règlement) ainsi que le cloisonnement par ressort (§8).
 */
class InstructionTest extends TestCase
{
    use RefreshDatabase;

    private function ressort(string $suffixe = 'A'): Ressort
    {
        return Ressort::query()->create(['code' => "TRIB-{$suffixe}", 'nom' => "Tribunal {$suffixe}", 'type' => 'tribunal']);
    }

    private function agent(Ressort $ressort, string $service, string $role): User
    {
        $svc = Service::query()->firstOrCreate(['code' => $service], ['nom' => $service, 'type' => 'parquet']);
        $agent = User::factory()->create(['service_id' => $svc->id, 'ressort_id' => $ressort->id]);
        $agent->assignRole($role);

        return $agent;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
        $this->seed(ReferentielsSeeder::class);
    }

    /**
     * Ouvre une affaire criminelle, la transmet au parquet et l'oriente vers
     * une ouverture d'information — le point de départ commun aux tests.
     *
     * @return array{affaireId: int, dossierId: int, personneId: int, ressort: Ressort}
     */
    private function ouvrirUneInformation(Ressort $ressort): array
    {
        $opj = $this->agent($ressort, 'PJ', 'opj');
        $infractionCrime = Infraction::query()->where('categorie', 'crime')->firstOrFail();

        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [
            'infractions' => [$infractionCrime->id],
        ])->json('id');

        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', [
            'type' => 'physique',
            'nom' => 'Test',
        ])->json('id');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", [
            'personne_id' => $personneId,
            'statut' => 'suspect',
        ])->assertOk();

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();

        $procureur = $this->agent($ressort, 'PARQ', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'ouverture_information',
        ])->assertOk();

        $dossierId = DossierInstruction::query()->where('affaire_id', $affaireId)->value('id');

        return compact('affaireId', 'dossierId', 'personneId') + ['ressort' => $ressort];
    }

    public function test_l_orientation_ouverture_d_information_cree_un_dossier(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);

        $this->assertDatabaseHas('dossiers_instruction', ['affaire_id' => $contexte['affaireId'], 'statut' => 'en_cours']);
        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'information_ouverte']);
    }

    public function test_un_juge_hors_ressort_ne_voit_pas_le_dossier(): void
    {
        $ressortA = $this->ressort('A');
        $contexte = $this->ouvrirUneInformation($ressortA);
        $jugeAutreRessort = $this->agent($this->ressort('B'), 'INSTR', 'juge_instruction');

        $this->actingAs($jugeAutreRessort)
            ->getJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}")
            ->assertForbidden();
    }

    public function test_on_ne_peut_pas_affecter_un_agent_qui_n_est_pas_juge_d_instruction_du_ressort(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');
        $procureurMemeRessort = $this->agent($ressort, 'PARQ', 'procureur');

        $this->actingAs($juge)
            ->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/affecter", ['juge_id' => $procureurMemeRessort->id])
            ->assertUnprocessable();
    }

    public function test_on_ne_peut_pas_mettre_en_examen_une_personne_etrangere_a_l_affaire(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $personneEtrangere = Personne::query()->create([
            'identifiant_unique' => (string) Str::uuid(),
            'type' => 'physique',
            'nom' => 'Étranger à l\'affaire',
        ]);

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/mise-en-examen", [
            'personne_id' => $personneEtrangere->id,
            'statut' => 'mis_en_examen',
        ])->assertUnprocessable();

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/detention-provisoire", [
            'personne_id' => $personneEtrangere->id,
        ])->assertUnprocessable();
    }

    public function test_mise_en_examen_ajoute_le_statut_sur_le_pivot_affaire_personne(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/mise-en-examen", [
            'personne_id' => $contexte['personneId'],
            'statut' => 'mis_en_examen',
        ])->assertOk();

        $this->assertDatabaseHas('affaire_personne', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'statut' => 'mis_en_examen',
        ]);
        // Le statut antérieur (suspect) reste dans l'historique.
        $this->assertDatabaseHas('affaire_personne', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'statut' => 'suspect',
        ]);
    }

    public function test_detention_provisoire_calcule_l_echeance_depuis_le_referentiel_crime(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $response = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/detention-provisoire", [
            'personne_id' => $contexte['personneId'],
        ]);

        $response->assertCreated()->assertJsonPath('duree_jours', 365)->assertJsonPath('statut', 'en_cours');
        $this->assertDatabaseHas('mesures_surete', [
            'dossier_instruction_id' => $contexte['dossierId'],
            'type' => 'detention_provisoire',
            'duree_jours' => 365,
        ]);
    }

    public function test_renouvellement_prolonge_l_echeance_de_detention(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $mesure = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/detention-provisoire", [
            'personne_id' => $contexte['personneId'],
        ])->json();

        $renouvellement = $this->actingAs($juge)->postJson("/api/v1/instruction/mesures-surete/{$mesure['id']}/renouveler", [
            'jours' => 30,
        ]);

        $renouvellement->assertOk()->assertJsonPath('duree_jours', 395);
    }

    public function test_mainlevee_termine_la_mesure_de_surete(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $mesure = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/controle-judiciaire", [
            'personne_id' => $contexte['personneId'],
            'obligations' => 'Pointage hebdomadaire au commissariat.',
        ])->json();

        $levee = $this->actingAs($juge)->postJson("/api/v1/instruction/mesures-surete/{$mesure['id']}/lever", [
            'motif' => 'mise_en_liberte',
        ]);

        $levee->assertOk()->assertJsonPath('statut', 'terminee')->assertJsonPath('motif_fin', 'mise_en_liberte');
    }

    public function test_ordonnance_de_renvoi_dirige_l_affaire_vers_l_audiencement(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/ordonnance", [
            'ordonnance' => 'renvoi',
        ])->assertOk();

        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'audiencee']);
        $this->assertDatabaseHas('dossiers_instruction', ['id' => $contexte['dossierId'], 'statut' => 'cloture']);
    }

    public function test_ordonnance_de_non_lieu_cloture_l_affaire_sans_condamnation(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/ordonnance", [
            'ordonnance' => 'non_lieu',
        ])->assertOk();

        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'cloturee']);
        // §3 : aucune trace d'un statut "condamné" ne doit résulter d'un non-lieu.
        $this->assertDatabaseMissing('affaire_personne', [
            'affaire_id' => $contexte['affaireId'],
            'statut' => 'condamne',
        ]);
    }

    public function test_enregistrer_et_mettre_a_jour_un_acte_d_instruction(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $acte = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/actes", [
            'type' => 'expertise',
            'description' => 'Expertise psychiatrique',
        ]);
        $acte->assertCreated()->assertJsonPath('statut', 'en_attente');

        $miseAJour = $this->actingAs($juge)->postJson("/api/v1/instruction/actes/{$acte->json('id')}/statut", [
            'statut' => 'rapport_depose',
        ]);
        $miseAJour->assertOk()->assertJsonPath('statut', 'rapport_depose');

        $this->assertDatabaseHas('actes_instruction', [
            'dossier_instruction_id' => $contexte['dossierId'],
            'statut' => 'rapport_depose',
        ]);
    }

    public function test_emettre_diffuser_et_executer_un_mandat(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $mandat = $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/mandats", [
            'personne_id' => $contexte['personneId'],
            'type' => 'amener',
        ]);
        $mandat->assertCreated();
        $mandatId = $mandat->json('id');

        $this->actingAs($juge)->postJson("/api/v1/instruction/mandats/{$mandatId}/etape", ['etape' => 'diffuse'])
            ->assertOk()->assertJsonPath('diffuse_at', fn ($valeur) => $valeur !== null);

        $this->actingAs($juge)->postJson("/api/v1/instruction/mandats/{$mandatId}/etape", ['etape' => 'execute'])
            ->assertOk()->assertJsonPath('execute_at', fn ($valeur) => $valeur !== null);
    }

    public function test_on_ne_peut_pas_emettre_un_mandat_pour_une_personne_etrangere_a_l_affaire(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->ouvrirUneInformation($ressort);
        $juge = $this->agent($ressort, 'INSTR', 'juge_instruction');

        $personneEtrangere = Personne::query()->create([
            'identifiant_unique' => (string) Str::uuid(),
            'type' => 'physique',
            'nom' => 'Étranger',
        ]);

        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$contexte['dossierId']}/mandats", [
            'personne_id' => $personneEtrangere->id,
            'type' => 'amener',
        ])->assertUnprocessable();
    }
}
