<?php

namespace Tests\Feature\Execution;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Execution\Models\Ecrou;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\EtablissementPenitentiaire;
use App\Models\Infraction;
use App\Models\Juridiction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Couvre §6.9 (mise à exécution, écrou, remises de peine, aménagements,
 * transferts, amendes, TIG, mise à l'épreuve) et le cloisonnement par
 * ressort (§8). Reprend le parcours de recette « ... → condamnation →
 * écrou → casier » (§14) jusqu'à l'écrou inclus.
 */
class ExecutionTest extends TestCase
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

    private function etablissement(Ressort $ressort): EtablissementPenitentiaire
    {
        return EtablissementPenitentiaire::query()->create([
            'code' => 'MAC-'.Str::random(8),
            'nom' => 'Maison d\'arrêt de test',
            'ressort_id' => $ressort->id,
            'capacite' => 200,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
        $this->seed(ReferentielsSeeder::class);
    }

    /**
     * Mène une affaire jusqu'à une condamnation définitive (comparution
     * immédiate → enrôlement → condamnation → délai expiré sans recours).
     *
     * @return array{affaireId: int, personneId: int, decisionId: int, ressort: Ressort}
     */
    private function obtenirUneCondamnationDefinitive(Ressort $ressort): array
    {
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infraction = Infraction::query()->where('categorie', 'delit')->firstOrFail();

        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [
            'infractions' => [$infraction->id],
        ])->json('id');

        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', [
            'type' => 'physique', 'nom' => 'Test',
        ])->json('id');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", [
            'personne_id' => $personneId, 'statut' => 'prevenu',
        ])->assertOk();

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();

        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'comparution_immediate',
        ])->assertOk();

        $dossierAudId = DossierAudiencement::query()->where('affaire_id', $affaireId)->value('id');
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $juridiction = Juridiction::query()->create(['code' => 'JUR-'.Str::random(6), 'nom' => 'Tribunal pilote', 'ressort_id' => $ressort->id]);

        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/enroler", [
            'juridiction_id' => $juridiction->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
        ])->assertOk();

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $personneId,
            'decision' => 'condamnation',
            'peine_principale' => 'Emprisonnement 6 mois',
        ])->json('id');

        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);

        return compact('affaireId', 'personneId', 'decisionId') + ['ressort' => $ressort];
    }

    public function test_on_ne_peut_pas_mettre_a_execution_une_decision_non_definitive(): void
    {
        $ressort = $this->ressort();
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');

        // Décision fraîche : délai encore ouvert.
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [])->json('id');
        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', ['type' => 'physique', 'nom' => 'X'])->json('id');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", ['personne_id' => $personneId, 'statut' => 'prevenu'])->assertOk();
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();
        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", ['orientation' => 'comparution_immediate'])->assertOk();
        $dossierAudId = DossierAudiencement::query()->where('affaire_id', $affaireId)->value('id');
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $juridiction = Juridiction::query()->create(['code' => 'J-X', 'nom' => 'Trib', 'ressort_id' => $ressort->id]);
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/enroler", [
            'juridiction_id' => $juridiction->id, 'chambre' => 'C1', 'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id, 'greffier_id' => $greffier->id,
        ])->assertOk();
        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $personneId, 'decision' => 'condamnation',
        ])->json('id');

        // Comme ailleurs dans le socle (cf. GardeAVueTest), une violation de
        // règle métier détectée dans l'Action (InvalidArgumentException) et
        // non par le FormRequest se traduit par une 500 : il n'y a pas de
        // mapping applicatif InvalidArgumentException → 422.
        $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$decisionId}/mettre-a-execution")
            ->assertStatus(500);
    }

    public function test_mise_a_execution_puis_ecrou_calcule_l_echeance(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $etablissement = $this->etablissement($ressort);

        $dossier = $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution");
        $dossier->assertCreated();
        $dossierId = $dossier->json('id');

        $ecrou = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissement->id,
            'duree_jours' => 180,
            'detention_provisoire_imputee_jours' => 30,
        ]);

        $ecrou->assertCreated()->assertJsonPath('statut', 'en_detention');
        $this->assertNotEmpty($ecrou->json('numero_ecrou'));

        $attendu = now()->addDays(150)->toDateString();
        $this->assertSame($attendu, substr($ecrou->json('date_fin_prevue'), 0, 10));
    }

    /**
     * Verrou de non-régression (détecté par capture d'écran §… — le même
     * bug que sur Audiencement/Instruction : un eager-load incomplet de
     * affaire.personnes fait retomber le frontend sur « Personne #id »).
     */
    public function test_le_dossier_expose_le_nom_de_la_personne_condamnee(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');

        $show = $this->actingAs($agentPenit)->getJson("/api/v1/execution/dossiers/{$dossierId}");
        $show->assertOk();
        $personnes = collect($show->json('affaire.personnes'))->pluck('id');
        $this->assertContains($contexte['personneId'], $personnes);

        $liste = $this->actingAs($agentPenit)->getJson('/api/v1/execution/dossiers');
        $liste->assertOk();
        $this->assertNotEmpty($liste->json('data.0.affaire.personnes'));
    }

    public function test_on_ne_peut_pas_ecrouer_deux_fois_le_meme_dossier(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $etablissement = $this->etablissement($ressort);

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');
        $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissement->id, 'duree_jours' => 100,
        ])->assertCreated();

        // Cf. la remarque de test_on_ne_peut_pas_mettre_a_execution_une_decision_non_definitive :
        // violation de règle métier détectée par l'Action → 500 dans ce socle.
        $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissement->id, 'duree_jours' => 100,
        ])->assertStatus(500);
    }

    public function test_remise_de_peine_avance_l_echeance(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $etablissement = $this->etablissement($ressort);

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');
        $ecrouId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissement->id, 'duree_jours' => 100,
        ])->json('id');
        $finAvant = Ecrou::find($ecrouId)->date_fin_prevue;

        $remise = $this->actingAs($agentPenit)->postJson("/api/v1/execution/ecrous/{$ecrouId}/remise-de-peine", [
            'jours' => 10, 'motif' => 'reduction_peine',
        ]);

        $remise->assertOk();
        $this->assertTrue(Ecrou::find($ecrouId)->date_fin_prevue->equalTo($finAvant->clone()->subDays(10)));
    }

    public function test_levee_d_ecrou_termine_le_dossier_d_execution(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $etablissement = $this->etablissement($ressort);

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');
        $ecrouId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissement->id, 'duree_jours' => 100,
        ])->json('id');

        $liberation = $this->actingAs($agentPenit)->postJson("/api/v1/execution/ecrous/{$ecrouId}/liberer", ['motif' => 'terme']);
        $liberation->assertOk()->assertJsonPath('statut', 'libere')->assertJsonPath('motif_liberation', 'terme');

        $this->assertDatabaseHas('dossiers_execution', ['id' => $dossierId, 'statut' => 'terminee']);
    }

    public function test_transfert_change_l_etablissement_et_est_trace(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $etablissementA = $this->etablissement($ressort);
        $etablissementB = $this->etablissement($ressort);

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');
        $ecrouId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/ecrouer", [
            'etablissement_id' => $etablissementA->id, 'duree_jours' => 100,
        ])->json('id');

        $transfert = $this->actingAs($agentPenit)->postJson("/api/v1/execution/ecrous/{$ecrouId}/transferer", [
            'etablissement_destination_id' => $etablissementB->id, 'motif' => 'Surpopulation',
        ]);

        $transfert->assertOk()->assertJsonPath('etablissement_id', $etablissementB->id);
        $this->assertDatabaseHas('transferts_ecrou', [
            'ecrou_id' => $ecrouId, 'etablissement_origine_id' => $etablissementA->id, 'etablissement_destination_id' => $etablissementB->id,
        ]);
    }

    public function test_amende_transmise_puis_marquee_recouvree(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');

        $amende = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/amende", ['montant' => 50000]);
        $amende->assertCreated()->assertJsonPath('statut', 'transmise_tresor');

        $this->actingAs($agentPenit)->postJson("/api/v1/execution/amendes/{$amende->json('id')}/recouvree")
            ->assertOk()->assertJsonPath('statut', 'recouvree');
    }

    public function test_tig_heures_effectuees_termine_automatiquement_au_seuil(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationDefinitive($ressort);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');

        $dossierId = $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');
        $tig = $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierId}/tig", ['heures_requises' => 40]);
        $tigId = $tig->json('id');

        $this->actingAs($agentPenit)->postJson("/api/v1/execution/tig/{$tigId}/heures", ['heures' => 25])
            ->assertOk()->assertJsonPath('statut', 'en_cours');

        $this->actingAs($agentPenit)->postJson("/api/v1/execution/tig/{$tigId}/heures", ['heures' => 20])
            ->assertOk()->assertJsonPath('statut', 'terminee')->assertJsonPath('heures_effectuees', 40);
    }

    public function test_un_agent_hors_ressort_ne_voit_pas_le_dossier(): void
    {
        $ressortA = $this->ressort('A');
        $contexte = $this->obtenirUneCondamnationDefinitive($ressortA);
        $agentPenitA = $this->agent($ressortA, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $dossierId = $this->actingAs($agentPenitA)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->json('id');

        $agentPenitB = $this->agent($this->ressort('B'), 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenitB)->getJson("/api/v1/execution/dossiers/{$dossierId}")->assertForbidden();
    }

    /**
     * Le service pénitentiaire n'a pas accès au dossier d'audiencement lui-
     * même (App\Policies\DossierAudiencementPolicy exige `audiencement.gerer`,
     * pas `execution.gerer`) : /execution/decisions-a-executer est son seul
     * point d'entrée vers la mise à exécution.
     */
    public function test_les_decisions_a_executer_listent_les_condamnations_definitives_sans_dossier(): void
    {
        $ressortA = $this->ressort('A');
        $contexte = $this->obtenirUneCondamnationDefinitive($ressortA);
        $agentPenitA = $this->agent($ressortA, 'PENIT', 'penitentiaire', 'agent_penitentiaire');

        $liste = $this->actingAs($agentPenitA)->getJson('/api/v1/execution/decisions-a-executer');
        $liste->assertOk();
        $this->assertCount(1, $liste->json('data'));
        $this->assertSame($contexte['decisionId'], $liste->json('data.0.id'));

        // Une fois mise à exécution, la décision disparaît de la liste.
        $this->actingAs($agentPenitA)->postJson("/api/v1/execution/decisions/{$contexte['decisionId']}/mettre-a-execution")->assertCreated();
        $this->actingAs($agentPenitA)->getJson('/api/v1/execution/decisions-a-executer')->assertJsonCount(0, 'data');

        // Un agent d'un autre ressort ne la voit jamais.
        $this->obtenirUneCondamnationDefinitive($this->ressort('B'));
        $this->actingAs($agentPenitA)->getJson('/api/v1/execution/decisions-a-executer')->assertJsonCount(0, 'data');
    }
}
