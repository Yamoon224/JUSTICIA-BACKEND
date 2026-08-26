<?php

namespace Tests\Feature\Casier;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Casier\Actions\DetecterRehabilitationsDePleinDroitAction;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\Infraction;
use App\Models\Juridiction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Couvre §6.10 (inscription automatique, réhabilitation judiciaire et de
 * plein droit, amnistie, bulletins B1/B2/B3, journalisation des
 * consultations) et son caractère national (§6.10 : pas de cloisonnement
 * par ressort, à la différence du reste du socle, §8).
 */
class CasierTest extends TestCase
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
     * La génération d'un bulletin est une consultation : GET avec motif en
     * paramètre, même idiome que ConsulterPersonneAction (§6.2), journalisée
     * à chaque appel — jamais un simple POST muet.
     */
    private function genererBulletin(int $personneId, string $type, ?string $motif = 'Test'): TestResponse
    {
        $query = http_build_query(array_filter(['type' => $type, 'motif' => $motif], fn ($v) => $v !== null));

        return $this->getJson("/api/v1/casier/personnes/{$personneId}/bulletin?{$query}");
    }

    /**
     * Mène une affaire jusqu'à la mise à exécution d'une condamnation
     * définitive — le seul chemin qui alimente le casier (§6.10).
     *
     * @return array{personneId: int, decisionId: int}
     */
    private function obtenirUneCondamnationExecutee(Ressort $ressort, string $categorieInfraction = 'delit'): array
    {
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infraction = Infraction::query()->where('categorie', $categorieInfraction)->firstOrFail();

        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [
            'infractions' => [$infraction->id],
        ])->json('id');

        $personneId = $this->actingAs($opj)->postJson('/api/v1/personnes', [
            'type' => 'physique', 'nom' => 'Test', 'prenom' => 'Casier',
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

        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$decisionId}/mettre-a-execution")->assertCreated();

        return ['personneId' => $personneId, 'decisionId' => $decisionId];
    }

    public function test_la_mise_a_execution_inscrit_automatiquement_la_condamnation_au_casier(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');

        $liste = $this->actingAs($agentCasier)->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations");

        // Collection non paginée : le wrapping JsonResource est désactivé
        // globalement (App\Providers\AppServiceProvider) — pas d'enveloppe
        // "data" ici, à la différence des listes paginées des autres modules.
        $liste->assertOk()->assertJsonCount(1);
        $liste->assertJsonPath('0.statut', 'active');
        $liste->assertJsonPath('0.peine_principale', 'Emprisonnement 6 mois');
        $liste->assertJsonPath('0.categorie_infraction', 'delit');
    }

    public function test_le_casier_est_national_et_non_cloisonne_par_ressort(): void
    {
        $ressortA = $this->ressort('A');
        $contexte = $this->obtenirUneCondamnationExecutee($ressortA);

        // Agent du casier rattaché à un tout autre ressort : le casier
        // reste un registre national (§6.10), pas de 403 lié au ressort.
        $agentCasierB = $this->agent($this->ressort('B'), 'CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasierB)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations")
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_rehabilitation_judiciaire_change_le_statut(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');
        $condamnationId = Condamnation::query()->where('decision_id', $contexte['decisionId'])->value('id');

        $rehabilitation = $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationId}/rehabiliter");

        $rehabilitation->assertOk()->assertJsonPath('statut', 'rehabilitee');
        $rehabilitation->assertJsonPath('rehabilitation.type', 'judiciaire');
    }

    public function test_amnistie_exige_un_texte_de_reference(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');
        $condamnationId = Condamnation::query()->where('decision_id', $contexte['decisionId'])->value('id');

        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationId}/amnistier", [])
            ->assertStatus(422);

        $amnistie = $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationId}/amnistier", [
            'texte_reference' => 'Décret d\'amnistie 2026-001',
        ]);
        $amnistie->assertOk()->assertJsonPath('statut', 'amnistiee');
        $amnistie->assertJsonPath('amnistie.texte_reference', 'Décret d\'amnistie 2026-001');
    }

    public function test_on_ne_peut_pas_traiter_deux_fois_la_meme_condamnation(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');
        $condamnationId = Condamnation::query()->where('decision_id', $contexte['decisionId'])->value('id');

        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationId}/rehabiliter")->assertOk();

        // Cf. la remarque déjà faite sur Execution : une violation de règle
        // métier détectée dans l'Action (InvalidArgumentException), pas par
        // le FormRequest, se traduit par une 500 dans ce socle.
        $this->actingAs($agentCasier)
            ->postJson("/api/v1/casier/condamnations/{$condamnationId}/rehabiliter")
            ->assertStatus(500);
    }

    public function test_seul_casier_consulter_nominatif_permet_de_generer_un_bulletin(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);

        // Le greffier a casier.gerer mais pas casier.consulter_nominatif
        // (RolesEtPermissionsSeeder) : il peut inscrire/gérer des mentions
        // connues, pas consulter librement le casier de n'importe qui.
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $this->actingAs($greffier)
            ->genererBulletin($contexte['personneId'], 'b1', 'Vérification')
            ->assertForbidden();

        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->genererBulletin($contexte['personneId'], 'b1', 'Vérification')
            ->assertOk();
    }

    public function test_le_bulletin_exige_un_motif(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');

        $this->actingAs($agentCasier)
            ->genererBulletin($contexte['personneId'], 'b1', null)
            ->assertStatus(422);
    }

    public function test_les_bulletins_appliquent_des_filtres_differents(): void
    {
        $ressort = $this->ressort();
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');

        // Une même personne, trois condamnations de statuts/catégories
        // différents.
        $contexteDelit = $this->obtenirUneCondamnationExecutee($ressort, 'delit');
        $personneId = $contexteDelit['personneId'];
        $condamnationDelitId = Condamnation::query()->where('decision_id', $contexteDelit['decisionId'])->value('id');

        // Deuxième condamnation (contravention) sur la même personne, cette
        // fois amnistiée.
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infractionContravention = Infraction::query()->where('categorie', 'contravention')->firstOrFail();
        $affaire2Id = $this->actingAs($opj)->postJson('/api/v1/affaires', ['infractions' => [$infractionContravention->id]])->json('id');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire2Id}/personnes", ['personne_id' => $personneId, 'statut' => 'prevenu'])->assertOk();
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire2Id}/transmettre-parquet")->assertOk();
        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquet2Id = DossierParquet::query()->where('affaire_id', $affaire2Id)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquet2Id}/orienter", ['orientation' => 'comparution_immediate'])->assertOk();
        $dossierAud2Id = DossierAudiencement::query()->where('affaire_id', $affaire2Id)->value('id');
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $juridiction = Juridiction::query()->create(['code' => 'JUR-'.Str::random(6), 'nom' => 'Trib', 'ressort_id' => $ressort->id]);
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAud2Id}/enroler", [
            'juridiction_id' => $juridiction->id, 'chambre' => 'C1', 'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id, 'greffier_id' => $greffier->id,
        ])->assertOk();
        $decision2Id = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAud2Id}/decisions", [
            'personne_id' => $personneId, 'decision' => 'condamnation', 'peine_principale' => 'Amende',
        ])->json('id');
        Decision::find($decision2Id)->update(['delai_recours_expire_at' => now()->subDay()]);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$decision2Id}/mettre-a-execution")->assertCreated();
        $condamnationContraventionId = Condamnation::query()->where('decision_id', $decision2Id)->value('id');

        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationContraventionId}/amnistier", [
            'texte_reference' => 'Décret 2026-002',
        ])->assertOk();

        // B1 : tout sauf amnistié → seule la condamnation pour délit reste.
        $b1 = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b1')->json('condamnations');
        $this->assertCount(1, $b1);
        $this->assertSame($condamnationDelitId, $b1[0]['id']);

        // B2 : exclut en plus les contraventions (même actives) → même résultat ici.
        $b2 = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b2')->json('condamnations');
        $this->assertCount(1, $b2);

        // B3 : uniquement actif + délit/crime + sans sursis → la
        // condamnation pour délit (sans sursis) reste.
        $b3 = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b3')->json('condamnations');
        $this->assertCount(1, $b3);
        $this->assertSame($condamnationDelitId, $b3[0]['id']);

        // Une fois la condamnation pour délit elle-même réhabilitée, elle
        // disparaît du B2/B3 mais reste visible au B1.
        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationDelitId}/rehabiliter")->assertOk();

        $b1Apres = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b1')->json('condamnations');
        $this->assertCount(1, $b1Apres);

        $b2Apres = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b2')->json('condamnations');
        $this->assertCount(0, $b2Apres);

        $b3Apres = $this->actingAs($agentCasier)->genererBulletin($personneId, 'b3')->json('condamnations');
        $this->assertCount(0, $b3Apres);
    }

    public function test_chaque_generation_de_bulletin_est_journalisee(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort);
        $agentCasier = $this->agent($ressort, 'CASIER', 'casier', 'agent_casier');

        $this->actingAs($agentCasier)
            ->genererBulletin($contexte['personneId'], 'b2', 'Recrutement fonction publique')
            ->assertOk();

        $consultations = $this->actingAs($agentCasier)->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/consultations");
        $consultations->assertOk()->assertJsonCount(1);
        $consultations->assertJsonPath('0.type_bulletin', 'b2');
        $consultations->assertJsonPath('0.motif', 'Recrutement fonction publique');
    }

    public function test_rehabilitation_de_plein_droit_apres_le_delai_legal_sans_recidive(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort, 'delit');
        $condamnation = Condamnation::query()->where('decision_id', $contexte['decisionId'])->firstOrFail();

        // Délai « délit » paramétré à 5 ans (ReferentielsSeeder) : on
        // antidate la condamnation pour simuler le délai écoulé.
        $condamnation->update(['condamnee_at' => now()->subYears(6)]);

        $rehabilitations = app(DetecterRehabilitationsDePleinDroitAction::class)->executer();

        $this->assertCount(1, $rehabilitations);
        $this->assertSame('rehabilitee', $condamnation->fresh()->statut);
        $this->assertSame('plein_droit', $condamnation->fresh()->rehabilitation->type);
        $this->assertNull($condamnation->fresh()->rehabilitation->decidee_par);
    }

    public function test_rehabilitation_de_plein_droit_bloquee_par_une_condamnation_active_plus_recente(): void
    {
        $ressort = $this->ressort();
        $contexte = $this->obtenirUneCondamnationExecutee($ressort, 'delit');
        $ancienne = Condamnation::query()->where('decision_id', $contexte['decisionId'])->firstOrFail();
        $ancienne->update(['condamnee_at' => now()->subYears(6)]);

        // Nouvelle condamnation active, postérieure, sur la même personne :
        // pas de réhabilitation de plein droit tant qu'elle reste active
        // (§6.10 : « sans nouvelle condamnation dans l'intervalle »). On
        // fait rejuger la même personne sur une seconde affaire plutôt que
        // de fabriquer une ligne casier isolée, pour garder decision_id
        // référentiellement valide (contrainte de clé étrangère).
        $opj = $this->agent($ressort, 'PJ', 'police', 'opj');
        $infraction = Infraction::query()->where('categorie', 'delit')->firstOrFail();
        $affaire2Id = $this->actingAs($opj)->postJson('/api/v1/affaires', ['infractions' => [$infraction->id]])->json('id');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire2Id}/personnes", [
            'personne_id' => $ancienne->personne_id, 'statut' => 'prevenu',
        ])->assertOk();
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaire2Id}/transmettre-parquet")->assertOk();
        $procureur = $this->agent($ressort, 'PARQ', 'parquet', 'procureur');
        $dossierParquet2Id = DossierParquet::query()->where('affaire_id', $affaire2Id)->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquet2Id}/orienter", ['orientation' => 'comparution_immediate'])->assertOk();
        $dossierAud2Id = DossierAudiencement::query()->where('affaire_id', $affaire2Id)->value('id');
        $president = $this->agent($ressort, 'JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent($ressort, 'GREFFE', 'greffe', 'greffier');
        $juridiction = Juridiction::query()->create(['code' => 'JUR-'.Str::random(6), 'nom' => 'Trib', 'ressort_id' => $ressort->id]);
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAud2Id}/enroler", [
            'juridiction_id' => $juridiction->id, 'chambre' => 'C1', 'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id, 'greffier_id' => $greffier->id,
        ])->assertOk();
        $decision2Id = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAud2Id}/decisions", [
            'personne_id' => $ancienne->personne_id, 'decision' => 'condamnation', 'peine_principale' => 'Amende',
        ])->json('id');
        Decision::find($decision2Id)->update(['delai_recours_expire_at' => now()->subDay()]);
        $agentPenit = $this->agent($ressort, 'PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenit)->postJson("/api/v1/execution/decisions/{$decision2Id}/mettre-a-execution")->assertCreated();
        Condamnation::query()->where('decision_id', $decision2Id)->update(['condamnee_at' => now()->subYear()]);

        $rehabilitations = app(DetecterRehabilitationsDePleinDroitAction::class)->executer();

        $this->assertCount(0, $rehabilitations);
        $this->assertSame('active', $ancienne->fresh()->statut);
    }
}
