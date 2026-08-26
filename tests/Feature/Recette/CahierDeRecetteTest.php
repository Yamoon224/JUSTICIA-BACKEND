<?php

namespace Tests\Feature\Recette;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Casier\Actions\DetecterRehabilitationsDePleinDroitAction;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\EtablissementPenitentiaire;
use App\Models\Infraction;
use App\Models\Juridiction;
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
 * Cahier de recette (§14) : rejoue de bout en bout, sur le référentiel
 * pilote (ReferentielsSeeder), chacun des parcours nommément exigés par le
 * §14 comme condition de recevabilité de la recette fonctionnelle
 * (Phase 8) avant déploiement pilote (Phase 9). Chaque méthode de test
 * correspond à une ligne du cahier de recette et peut servir de base au PV
 * de recette signé par le comité mixte (chancellerie, magistrats
 * référents, DSI).
 *
 * À la différence des tests Feature par module (GardeAVueTest,
 * ParquetTest, InstructionTest, AudiencementTest, ExecutionTest,
 * CasierTest, ...), qui vérifient des règles de gestion isolées à
 * l'intérieur d'un seul module, ceux-ci vérifient qu'une chaîne complète —
 * de l'interpellation jusqu'au casier — produit l'état final attendu, y
 * compris dans les modules situés en aval de celui qui vient d'agir.
 *
 * Hors périmètre de ce fichier (§14) : tests d'intrusion et audit de
 * sécurité indépendants (nécessitent un prestataire tiers), et le jeu de
 * cas de référence des délais légaux validé par des juristes (à
 * constituer avec la chancellerie, cf. §11).
 */
class CahierDeRecetteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
        $this->seed(ReferentielsSeeder::class);
    }

    private function ressort(): Ressort
    {
        return Ressort::query()->where('code', 'TRIB-01')->firstOrFail();
    }

    private function unite(): Unite
    {
        return Unite::query()->where('code', 'UNITE-01')->firstOrFail();
    }

    private function juridiction(): Juridiction
    {
        return Juridiction::query()->where('code', 'TPI-01')->firstOrFail();
    }

    private function etablissement(): EtablissementPenitentiaire
    {
        return EtablissementPenitentiaire::query()->where('code', 'MAC-01')->firstOrFail();
    }

    private function infraction(string $categorie): Infraction
    {
        return Infraction::query()->where('categorie', $categorie)->firstOrFail();
    }

    private function agent(string $codeService, string $typeService, string $role): User
    {
        $service = Service::query()->firstOrCreate(['code' => $codeService], ['nom' => $codeService, 'type' => $typeService]);
        $agent = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $this->ressort()->id]);
        $agent->assignRole($role);

        return $agent;
    }

    private function creerPersonne(User $opj, string $nom = 'Recette'): int
    {
        return $this->actingAs($opj)->postJson('/api/v1/personnes', [
            'type' => 'physique', 'nom' => $nom, 'prenom' => Str::random(6),
        ])->json('id');
    }

    /**
     * Ouvre une affaire pour une infraction donnée et y attache une
     * personne (déjà créée ou nouvelle) — le socle commun de tous les
     * parcours, qui matérialise l'« interpellation » du §14.
     *
     * @return array{affaireId: int, personneId: int}
     */
    private function interpeller(User $opj, string $categorieInfraction, ?int $personneId = null, string $statut = 'suspect'): array
    {
        $affaireId = $this->actingAs($opj)->postJson('/api/v1/affaires', [
            'infractions' => [$this->infraction($categorieInfraction)->id],
        ])->json('id');

        $personneId ??= $this->creerPersonne($opj);

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$affaireId}/personnes", [
            'personne_id' => $personneId, 'statut' => $statut,
        ])->assertOk();

        return compact('affaireId', 'personneId');
    }

    /**
     * Mène une affaire jusqu'à une condamnation définitive, par comparution
     * immédiate — le chemin le plus court vers le jugement.
     *
     * @return array{affaireId: int, personneId: int, decisionId: int}
     */
    private function condamnerParComparutionImmediate(string $categorieInfraction, string $peinePrincipale, ?int $personneId = null): array
    {
        $opj = $this->agent('PJ', 'police', 'opj');
        $contexte = $this->interpeller($opj, $categorieInfraction, $personneId, 'prevenu');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$contexte['affaireId']}/transmettre-parquet")->assertOk();

        $procureur = $this->agent('PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'comparution_immediate',
        ])->assertOk();

        $dossierAudId = DossierAudiencement::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $president = $this->agent('JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent('GREFFE', 'greffe', 'greffier');
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/enroler", [
            'juridiction_id' => $this->juridiction()->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
        ])->assertOk();

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
            'peine_principale' => $peinePrincipale,
        ])->assertCreated()->json('id');

        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);

        return $contexte + ['decisionId' => $decisionId];
    }

    /**
     * §14 — « interpellation → GAV+prolongation → déferrement →
     * comparution immédiate → condamnation → écrou → casier ».
     */
    public function test_parcours_comparution_immediate_jusqu_au_casier(): void
    {
        $opj = $this->agent('PJ', 'police', 'opj');
        $contexte = $this->interpeller($opj, 'delit');

        $mesureId = $this->actingAs($opj)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'unite_id' => $this->unite()->id,
        ])->assertCreated()->json('id');

        $this->actingAs($opj)->postJson("/api/v1/gav/mesures/{$mesureId}/prolonger", [
            'heures' => 24, 'autorise_par_id' => $opj->id,
        ])->assertOk()->assertJsonPath('statut', 'prolongee');

        // Déferrement : clôture de la GAV avec l'issue idoine, puis
        // transmission effective au bureau des arrivées du parquet — cette
        // dernière n'est pas automatique (cf. CloturerGardeAVueAction).
        $this->actingAs($opj)->postJson("/api/v1/gav/mesures/{$mesureId}/cloturer", ['issue' => 'deferement'])
            ->assertOk()->assertJsonPath('issue', 'deferement');
        $this->actingAs($opj)->postJson("/api/v1/affaires/{$contexte['affaireId']}/transmettre-parquet")->assertOk();

        $procureur = $this->agent('PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'comparution_immediate',
        ])->assertOk();

        $dossierAudId = DossierAudiencement::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $president = $this->agent('JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent('GREFFE', 'greffe', 'greffier');
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/enroler", [
            'juridiction_id' => $this->juridiction()->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
        ])->assertOk();

        $decisionId = $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
            'peine_principale' => 'Emprisonnement 6 mois',
        ])->assertCreated()->json('id');
        Decision::find($decisionId)->update(['delai_recours_expire_at' => now()->subDay()]);

        $agentPenit = $this->agent('PENIT', 'penitentiaire', 'agent_penitentiaire');
        $dossierExecId = $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$decisionId}/mettre-a-execution")
            ->assertCreated()->json('id');

        $this->actingAs($agentPenit)->postJson("/api/v1/execution/dossiers/{$dossierExecId}/ecrouer", [
            'etablissement_id' => $this->etablissement()->id,
            'duree_jours' => 180,
        ])->assertCreated()->assertJsonPath('statut', 'en_detention');

        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $condamnations = $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations");
        $condamnations->assertOk()->assertJsonCount(1);
        $condamnations->assertJsonPath('0.statut', 'active');
        $condamnations->assertJsonPath('0.peine_principale', 'Emprisonnement 6 mois');

        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'jugee']);
    }

    /**
     * §14 — « interpellation → GAV → relâchement (aucune trace indue au
     * casier) ».
     */
    public function test_parcours_relachement_sans_transmission_ni_trace_au_casier(): void
    {
        $opj = $this->agent('PJ', 'police', 'opj');
        $contexte = $this->interpeller($opj, 'delit');

        $mesureId = $this->actingAs($opj)->postJson('/api/v1/gav/mesures', [
            'affaire_id' => $contexte['affaireId'],
            'personne_id' => $contexte['personneId'],
            'unite_id' => $this->unite()->id,
        ])->assertCreated()->json('id');

        $this->actingAs($opj)->postJson("/api/v1/gav/mesures/{$mesureId}/cloturer", ['issue' => 'liberation'])
            ->assertOk()->assertJsonPath('statut', 'terminee')->assertJsonPath('issue', 'liberation');

        // Jamais transmise au parquet : aucune décision n'a donc jamais pu
        // être rendue, et le casier — qui ne s'alimente qu'à la mise à
        // exécution d'une condamnation (MettreAExecutionAction) — reste
        // structurellement vierge pour cette personne.
        $this->assertDatabaseMissing('dossiers_parquet', ['affaire_id' => $contexte['affaireId']]);

        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations")
            ->assertOk()->assertJsonCount(0);
    }

    /**
     * §14 — « information judiciaire → non-lieu ».
     */
    public function test_parcours_information_judiciaire_non_lieu(): void
    {
        $opj = $this->agent('PJ', 'police', 'opj');
        $contexte = $this->interpeller($opj, 'crime');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$contexte['affaireId']}/transmettre-parquet")->assertOk();

        $procureur = $this->agent('PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'ouverture_information',
        ])->assertOk();

        $dossierInstructionId = DossierInstruction::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $juge = $this->agent('INSTR', 'instruction', 'juge_instruction');
        $this->actingAs($juge)->postJson("/api/v1/instruction/dossiers/{$dossierInstructionId}/ordonnance", [
            'ordonnance' => 'non_lieu',
        ])->assertOk();

        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'cloturee']);
        $this->assertDatabaseMissing('affaire_personne', [
            'affaire_id' => $contexte['affaireId'], 'personne_id' => $contexte['personneId'], 'statut' => 'condamne',
        ]);

        // Un non-lieu ne passe jamais par une mise à exécution : le casier
        // reste vierge pour cette personne (§3 : présomption d'innocence).
        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations")
            ->assertOk()->assertJsonCount(0);
    }

    /**
     * §14 — « jugement → relaxe → mise à jour immédiate des statuts ».
     */
    public function test_parcours_jugement_relaxe_met_a_jour_immediatement_le_statut(): void
    {
        $opj = $this->agent('PJ', 'police', 'opj');
        $contexte = $this->interpeller($opj, 'delit', null, 'prevenu');

        $this->actingAs($opj)->postJson("/api/v1/affaires/{$contexte['affaireId']}/transmettre-parquet")->assertOk();
        $procureur = $this->agent('PARQ', 'parquet', 'procureur');
        $dossierParquetId = DossierParquet::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $this->actingAs($procureur)->postJson("/api/v1/parquet/dossiers/{$dossierParquetId}/orienter", [
            'orientation' => 'comparution_immediate',
        ])->assertOk();

        $dossierAudId = DossierAudiencement::query()->where('affaire_id', $contexte['affaireId'])->value('id');
        $president = $this->agent('JURID', 'juridiction', 'juge_audience');
        $greffier = $this->agent('GREFFE', 'greffe', 'greffier');
        $this->actingAs($greffier)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/enroler", [
            'juridiction_id' => $this->juridiction()->id,
            'chambre' => 'Chambre correctionnelle',
            'date_audience' => now()->addWeek()->toIso8601String(),
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
        ])->assertOk();

        $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'relaxe',
        ])->assertCreated()->assertJsonPath('decision', 'relaxe');

        // Mise à jour immédiate — sans attendre l'expiration d'un quelconque
        // délai — du statut de la personne et de l'affaire (§6.7, §3).
        $this->assertDatabaseHas('affaire_personne', [
            'affaire_id' => $contexte['affaireId'], 'personne_id' => $contexte['personneId'], 'statut' => 'relaxe',
        ]);
        $this->assertDatabaseMissing('affaire_personne', [
            'affaire_id' => $contexte['affaireId'], 'personne_id' => $contexte['personneId'], 'statut' => 'condamne',
        ]);
        $this->assertDatabaseHas('affaires', ['id' => $contexte['affaireId'], 'statut' => 'jugee']);

        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations")
            ->assertOk()->assertJsonCount(0);
    }

    /**
     * §14 — « appel avec infirmation → mise à jour exécution/casier ».
     *
     * Corrige l'anomalie bloquante initialement constatée par ce test (la
     * contrainte unique (dossier_audiencement_id, personne_id) empêchait
     * toute seconde décision) : la migration
     * 2026_08_31_100000_drop_unique_constraint_from_decisions_table l'a
     * levée, et EnregistrerDecisionAction n'autorise désormais une nouvelle
     * décision sur un dossier déjà jugé que si la précédente a été rouverte
     * par un recours recevable et résolu — exactement le cas d'un appel
     * jugé.
     */
    public function test_parcours_appel_avec_infirmation_met_a_jour_execution_et_casier(): void
    {
        $contexte = $this->condamnerParComparutionImmediate('delit', 'Emprisonnement 3 mois');

        // L'appel est formé alors que le délai est encore ouvert (on ne
        // l'expire qu'après), donc recevable et suspensif.
        $decisionPremiereInstance = Decision::find($contexte['decisionId']);
        $decisionPremiereInstance->update(['delai_recours_expire_at' => now()->addDays(15)]);

        $greffier = $this->agent('GREFFE', 'greffe', 'greffier');

        $recours = $this->actingAs($greffier)->postJson("/api/v1/audiencement/decisions/{$contexte['decisionId']}/recours", [
            'type' => 'appel',
            'formee_par_personne_id' => $contexte['personneId'],
        ]);
        $recours->assertCreated()->assertJsonPath('recevable', true)->assertJsonPath('effet_suspensif', true);
        $recoursId = $recours->json('id');

        $this->assertFalse($decisionPremiereInstance->fresh()->estDefinitive());

        $this->actingAs($greffier)->postJson("/api/v1/audiencement/recours/{$recoursId}/decision", [
            'issue' => 'infirmation',
        ])->assertOk()->assertJsonPath('decision_recours', 'infirmation');

        // Nouvelle décision rendue en appel, sur le même dossier : relaxe —
        // désormais possible, l'infirmation ayant résolu le recours
        // recevable qui portait sur la première décision.
        $dossierAudId = $decisionPremiereInstance->dossier_audiencement_id;
        $president = $this->agent('JURID', 'juridiction', 'juge_audience');
        $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'relaxe',
        ])->assertCreated();

        // Mise à jour immédiate du statut de la personne — la relaxe en
        // appel prévaut, l'historique de la condamnation initiale reste
        // consultable (pivot non écrasé, seulement complété).
        $this->assertDatabaseHas('affaire_personne', [
            'affaire_id' => $contexte['affaireId'], 'personne_id' => $contexte['personneId'], 'statut' => 'relaxe',
        ]);

        // La décision de première instance, infirmée, n'a jamais été et ne
        // sera jamais mise à exécution : aucun dossier d'exécution, aucune
        // trace au casier.
        $this->assertDatabaseMissing('dossiers_execution', ['decision_id' => $contexte['decisionId']]);
        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$contexte['personneId']}/condamnations")
            ->assertOk()->assertJsonCount(0);

        // Non-régression : un doublon accidentel (aucun recours résolu
        // entre les deux) reste rejeté — l'assouplissement de la
        // contrainte unique ne doit pas ouvrir la porte à des décisions
        // multiples arbitraires sur un même dossier.
        $this->actingAs($president)->postJson("/api/v1/audiencement/dossiers/{$dossierAudId}/decisions", [
            'personne_id' => $contexte['personneId'],
            'decision' => 'condamnation',
            'peine_principale' => 'Rejugé sans recours',
        ])->assertStatus(500);
        $this->assertDatabaseCount('decisions', 2);
    }

    /**
     * §14 — « délivrance des 3 bulletins avec règles de filtrage ».
     */
    public function test_parcours_delivrance_des_trois_bulletins_avec_filtrage(): void
    {
        $condamnationActive = $this->condamnerParComparutionImmediate('delit', 'Emprisonnement 6 mois');
        $personneId = $condamnationActive['personneId'];

        $agentPenit = $this->agent('PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$condamnationActive['decisionId']}/mettre-a-execution")
            ->assertCreated();

        // Seconde condamnation, pour contravention, amnistiée — sur la même
        // personne.
        $condamnationAmnistiee = $this->condamnerParComparutionImmediate('contravention', 'Amende', $personneId);
        $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$condamnationAmnistiee['decisionId']}/mettre-a-execution")
            ->assertCreated();

        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $condamnationAmnistieeId = Condamnation::query()->where('decision_id', $condamnationAmnistiee['decisionId'])->value('id');
        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationAmnistieeId}/amnistier", [
            'texte_reference' => "Décret d'amnistie de recette",
        ])->assertOk();

        $genererBulletin = fn (string $type) => $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$personneId}/bulletin?type={$type}&motif=Recette");

        // B1 : version intégrale — tout sauf ce qui est amnistié/réhabilité.
        $b1 = $genererBulletin('b1')->assertOk()->json('condamnations');
        $this->assertCount(1, $b1);

        // B2 et B3 filtrent en outre les contraventions (déjà exclues ici
        // par l'amnistie), et B3 se limite en plus aux peines sans sursis.
        $b2 = $genererBulletin('b2')->assertOk()->json('condamnations');
        $this->assertCount(1, $b2);
        $b3 = $genererBulletin('b3')->assertOk()->json('condamnations');
        $this->assertCount(1, $b3);

        // Chacune des trois délivrances est une consultation journalisée
        // distincte (§6.10 : jamais un simple accès muet).
        $consultations = $this->actingAs($agentCasier)->getJson("/api/v1/casier/personnes/{$personneId}/consultations");
        $consultations->assertOk()->assertJsonCount(3);
        $typesConsultes = collect($consultations->json())->pluck('type_bulletin')->sort()->values()->all();
        $this->assertSame(['b1', 'b2', 'b3'], $typesConsultes);
    }

    /**
     * §14 — « réhabilitation avec effacement ». Le glossaire du cahier des
     * charges (§12) définit la réhabilitation comme l'« effacement
     * légal/judiciaire d'une condamnation après délai sans nouvelle
     * infraction » : concrètement, dans ce socle, l'effacement s'entend au
     * sens des bulletins de circulation (B2/B3, cf. filtrage), la
     * condamnation restant tracée au bulletin intégral B1 à l'usage de
     * l'autorité judiciaire elle-même.
     */
    public function test_parcours_rehabilitation_avec_effacement_des_bulletins_de_circulation(): void
    {
        // Réhabilitation judiciaire, sur décision explicite du service du
        // casier.
        $condamnation = $this->condamnerParComparutionImmediate('delit', 'Amende');
        $agentPenit = $this->agent('PENIT', 'penitentiaire', 'agent_penitentiaire');
        $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$condamnation['decisionId']}/mettre-a-execution")
            ->assertCreated();

        $agentCasier = $this->agent('CASIER', 'casier', 'agent_casier');
        $condamnationId = Condamnation::query()->where('decision_id', $condamnation['decisionId'])->value('id');
        $this->actingAs($agentCasier)->postJson("/api/v1/casier/condamnations/{$condamnationId}/rehabiliter")
            ->assertOk()->assertJsonPath('statut', 'rehabilitee');

        $bulletin = fn (string $type) => $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$condamnation['personneId']}/bulletin?type={$type}&motif=Recette")
            ->assertOk()->json('condamnations');

        $this->assertCount(1, $bulletin('b1'));
        $this->assertCount(0, $bulletin('b2'));
        $this->assertCount(0, $bulletin('b3'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'casier.rehabilitation']);

        // Réhabilitation de plein droit, sans acte humain, sur le jeu de
        // délais du référentiel pilote (REHAB_DELIT = 5 ans, cf.
        // ReferentielsSeeder) : même effet d'effacement des bulletins de
        // circulation.
        $condamnationPleinDroit = $this->condamnerParComparutionImmediate('delit', 'Amende');
        $this->actingAs($agentPenit)
            ->postJson("/api/v1/execution/decisions/{$condamnationPleinDroit['decisionId']}/mettre-a-execution")
            ->assertCreated();
        $entree = Condamnation::query()->where('decision_id', $condamnationPleinDroit['decisionId'])->firstOrFail();
        $entree->update(['condamnee_at' => now()->subYears(6)]);

        $rehabilitations = app(DetecterRehabilitationsDePleinDroitAction::class)->executer();

        $this->assertCount(1, $rehabilitations);
        $this->assertSame('rehabilitee', $entree->fresh()->statut);
        $this->assertSame('plein_droit', $entree->fresh()->rehabilitation->type);

        $bulletinPleinDroit = fn (string $type) => $this->actingAs($agentCasier)
            ->getJson("/api/v1/casier/personnes/{$condamnationPleinDroit['personneId']}/bulletin?type={$type}&motif=Recette")
            ->assertOk()->json('condamnations');
        $this->assertCount(0, $bulletinPleinDroit('b2'));
    }
}
