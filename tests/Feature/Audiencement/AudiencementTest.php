<?php

namespace Tests\Feature\Audiencement;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Parquet\Models\DossierParquet;
use App\Domain\Personnes\Models\Personne;
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
 * Couvre §6.7 (audiencement, jugement, caractère définitif) et §6.8 (voies
 * de recours), ainsi que le cloisonnement par ressort (§8). Reprend
 * notamment le parcours de recette « jugement → relaxe → mise à jour
 * immédiate des statuts » (§14).
 */
class AudiencementTest extends TestCase
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

    /**
     * Ouvre une affaire, la transmet au parquet et l'oriente vers une
     * comparution immédiate — le chemin le plus court vers l'audiencement.
     *
     * @return array{affaireId: int, dossierId: int, personneId: int}
     */
    private function enrolerUneAffaire(Ressort $ressort): array
    {
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infraction = Infraction::query()->where('categorie', 'delit')->firstOrFail();

        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [
            'infractions' => [$infraction->id],
        ])->json('id');

        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', [
            'type' => 'physique',
            'nom' => 'Test',
        ])->json('id');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", [
            'personne_id' => $personneId,
            'statut' => 'prevenu',
        ])->assertOk();

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/transmettre-parquet")->assertOk();

        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $affaireId)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'comparution_immediate',
        ])->assertOk();

        $dossierId = DossierAudiencement::query()->where('affaire_id', $affaireId)->value('id');

        return compact('affaireId', 'dossierId', 'personneId');
    }

    private function enrolerLeDossier(DossierAudiencement $dossier, User $president, User $greffier, User $acteur): void
    {
        $juridiction = Juridiction::query()->create([
            'code' => 'JUR-'.Str::random(6), 'nom' => 'Tribunal pilote', 'ressort_id' => $dossier->affaire->ressort_id,
        ]);

        $this->actingAs($acteur)->postJson("/api/v1/audiencement/dossiers/{$dossier->id}/enroler", [
            'juridiction_id' => $juridiction->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
        ])->assertOk();
    }

    public function test_comparution_immediate_ouvre_un_dossier_audiencement(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);

        $this->assertDatabaseHas('dossiers_audiencement', ['affaire_id' => $contexte['affaireId'], 'statut' => 'a_enroler']);
    }

    public function test_un_agent_hors_ressort_ne_voit_pas_le_dossier(): void
    {
        $ressortA = $this->ressort('A');
        $contexte = $this->enrolerUneAffaire($ressortA);
        $greffierAutreRessort = $this->agent($this->ressort('B'), 'GREFFE', 'greffe', 'greffier');

        $this->actingAs($greffierAutreRessort)
            ->getJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}")
            ->assertForbidden();
    }

    public function test_l_enrolement_exige_un_president_et_un_greffier_du_ressort(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $juridiction = Juridiction::query()->create(['code' => 'JUR-X', 'nom' => 'Tribunal X', 'ressort_id' => $ressort->id]);

        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/enroler", [
            'juridiction_id' => $juridiction->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $procureur->id, // mauvais rôle
            'greffier_id' => $greffier->id,
        ])->assertUnprocessable();
    }

    public function test_decision_de_relaxe_met_a_jour_immediatement_le_statut_de_la_personne(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $decision = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'relaxe',
        ]);

        $decision->assertCreated()->assertJsonPath('decision', 'relaxe');

        $this->assertDatabaseHas('affaire_personne', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'statut' => 'relaxe',
        ]);
        $this->assertDatabaseMissing('affaire_personne', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'statut' => 'condamne',
        ]);
        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'jugee']);
    }

    public function test_on_ne_peut_pas_rendre_une_decision_pour_une_personne_etrangere_a_l_affaire(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $personneEtrangere = Personne::query()->create([
            'identifiant_unique' => (string) Str::uuid(),
            'type' => 'physique',
            'nom' => 'Étranger',
        ]);

        $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/decisions", [
            'personne_id' => $personneEtrangere->id,
            'decision' => 'condamnation',
        ])->assertUnprocessable();
    }

    public function test_une_decision_devient_definitive_apres_expiration_du_delai_sans_recours(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
            'peine_principale' => 'Emprisonnement 6 mois',
        ])->json('id');

        $this->assertFalse(Decision::find($decisionId)->estDefinitive());

        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);

        $this->assertTrue(Decision::find($decisionId)->estDefinitive());
    }

    public function test_un_recours_hors_delai_est_irrecevable(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
        ])->json('id');

        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);

        $recours = $this->actingAs($greffier)->postJson("/api/v1/audiencement/decisions/{$decisionId}/recours", [
            'type' => 'appel',
            'formee_par_personne_id' => $contexte['personneId'],
        ]);

        $recours->assertCreated()->assertJsonPath('recevable', false)->assertJsonPath('effet_suspensif', false);
        $this->assertTrue(Decision::find($decisionId)->estDefinitive());
    }

    public function test_un_recours_recevable_suspend_le_caractere_definitif_jusqu_a_integration(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
        ])->json('id');

        $recours = $this->actingAs($greffier)->postJson("/api/v1/audiencement/decisions/{$decisionId}/recours", [
            'type' => 'appel',
            'formee_par_personne_id' => $contexte['personneId'],
        ]);
        $recours->assertCreated()->assertJsonPath('recevable', true)->assertJsonPath('effet_suspensif', true);
        $recoursId = $recours->json('id');

        // Même après expiration du délai initial, un recours recevable non
        // résolu empêche le caractère définitif (§6.8).
        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);
        $this->assertFalse(Decision::find($decisionId)->estDefinitive());

        $integration = $this->actingAs($greffier)->postJson("/api/v1/audiencement/recours/{$recoursId}/decision", [
            'issue' => 'infirmation',
        ]);
        $integration->assertOk()->assertJsonPath('decision_recours', 'infirmation');

        // Une seconde décision sur le même recours est refusée.
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/recours/{$recoursId}/decision", [
            'issue' => 'confirmation',
        ])->assertUnprocessable();
    }

    public function test_un_renvoi_d_audience_est_trace(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->enrolerUneAffaire($ressort);
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->enrolerLeDossier(DossierAudiencement::find($contexte['dossierId']), $president, $greffier, $greffier);

        $nouvelleDate = now()->addMonth()->toIso8601String();
        $response = $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$contexte['dossierId']}/renvoyer", [
            'nouvelle_date' => $nouvelleDate,
            'motif' => 'Absence du prévenu',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('renvois_audience', [
            'dossier_audiencement_id' => $contexte['dossierId'],
            'motif' => 'Absence du prévenu',
        ]);
    }
}
