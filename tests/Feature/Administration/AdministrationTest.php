<?php

namespace Tests\Feature\Administration;

use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * §6.13 : gestion des comptes (double validation), affectation des rôles,
 * écriture sur le référentiel des infractions.
 */
class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesEtPermissionsSeeder::class);
    }

    private function administrateur(): User
    {
        $ressort = Ressort::query()->firstOrCreate(['code' => 'NAT'], ['nom' => 'National', 'type' => 'national']);
        $service = Service::query()->firstOrCreate(['code' => 'DSI'], ['nom' => 'DSI Justice', 'type' => 'dsi']);
        $admin = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $admin->assignRole('administrateur');

        return $admin;
    }

    private function opj(): User
    {
        $ressort = Ressort::query()->firstOrCreate(['code' => 'TRIB-01'], ['nom' => 'Tribunal', 'type' => 'tribunal']);
        $service = Service::query()->firstOrCreate(['code' => 'PJ'], ['nom' => 'Police judiciaire', 'type' => 'police']);
        $agent = User::factory()->create(['service_id' => $service->id, 'ressort_id' => $ressort->id]);
        $agent->assignRole('opj');

        return $agent;
    }

    private function creerCompte(User $createur, array $overrides = []): int
    {
        $reponse = $this->actingAs($createur)->postJson('/api/v1/administration/agents', [
            'matricule' => 'MAT-'.Str::random(6),
            'nom' => 'Nouvel',
            'prenom' => 'Agent',
            'password' => 'mot-de-passe-suffisant',
            ...$overrides,
        ]);
        $reponse->assertCreated();

        return $reponse->json('id');
    }

    public function test_un_agent_sans_habilitation_ne_peut_pas_gerer_les_comptes(): void
    {
        $this->actingAs($this->opj())->postJson('/api/v1/administration/agents', [
            'matricule' => 'X', 'nom' => 'X', 'prenom' => 'X', 'password' => 'quelconque',
        ])->assertForbidden();
    }

    public function test_un_compte_cree_est_inactif_et_ne_peut_pas_se_connecter_avant_validation(): void
    {
        $admin = $this->administrateur();
        $matricule = 'MAT-'.Str::random(6);

        $creation = $this->actingAs($admin)->postJson('/api/v1/administration/agents', [
            'matricule' => $matricule, 'nom' => 'Nouvel', 'prenom' => 'Agent', 'password' => 'mot-de-passe-suffisant',
        ]);
        $creation->assertCreated()->assertJsonPath('actif', false)->assertJsonPath('valide', false);

        $this->postJson('/api/v1/auth/login', [
            'matricule' => $matricule, 'password' => 'mot-de-passe-suffisant', 'device_name' => 'test',
        ])->assertStatus(401);

        $this->assertDatabaseHas('users', ['matricule' => $matricule, 'cree_par' => $admin->id, 'actif' => false]);
    }

    public function test_le_createur_ne_peut_pas_valider_son_propre_compte_cree(): void
    {
        $admin = $this->administrateur();
        $agentId = $this->creerCompte($admin);

        $this->actingAs($admin)->postJson("/api/v1/administration/agents/{$agentId}/valider")->assertStatus(500);
        $this->assertDatabaseHas('users', ['id' => $agentId, 'actif' => false]);
    }

    public function test_un_second_administrateur_valide_le_compte_qui_devient_alors_actif(): void
    {
        $createur = $this->administrateur();
        $matricule = 'MAT-'.Str::random(6);
        $agentId = $this->creerCompte($createur, ['matricule' => $matricule]);

        $validateur = $this->administrateur();
        $validation = $this->actingAs($validateur)->postJson("/api/v1/administration/agents/{$agentId}/valider");
        $validation->assertOk()->assertJsonPath('actif', true)->assertJsonPath('valide', true);

        $this->postJson('/api/v1/auth/login', [
            'matricule' => $matricule, 'password' => 'mot-de-passe-suffisant', 'device_name' => 'test',
        ])->assertOk();

        // Une seconde validation est refusée (déjà validé).
        $this->actingAs($this->administrateur())->postJson("/api/v1/administration/agents/{$agentId}/valider")->assertStatus(500);
    }

    public function test_suspension_bloque_la_connexion_et_reactivation_la_restaure(): void
    {
        $createur = $this->administrateur();
        $matricule = 'MAT-'.Str::random(6);
        $agentId = $this->creerCompte($createur, ['matricule' => $matricule]);
        $this->actingAs($this->administrateur())->postJson("/api/v1/administration/agents/{$agentId}/valider")->assertOk();

        // La suspension n'exige qu'un seul administrateur (contrôle
        // d'urgence, cf. SuspendreCompteAction) — le créateur suffit ici.
        $this->actingAs($createur)->postJson("/api/v1/administration/agents/{$agentId}/suspendre", [
            'motif' => 'Départ de service',
        ])->assertOk()->assertJsonPath('actif', false);

        $this->postJson('/api/v1/auth/login', [
            'matricule' => $matricule, 'password' => 'mot-de-passe-suffisant', 'device_name' => 'test',
        ])->assertStatus(401);

        $this->actingAs($createur)->postJson("/api/v1/administration/agents/{$agentId}/reactiver")->assertOk()->assertJsonPath('actif', true);

        $this->postJson('/api/v1/auth/login', [
            'matricule' => $matricule, 'password' => 'mot-de-passe-suffisant', 'device_name' => 'test',
        ])->assertOk();
    }

    public function test_on_ne_peut_pas_reactiver_un_compte_jamais_valide(): void
    {
        $admin = $this->administrateur();
        $agentId = $this->creerCompte($admin);

        // Un compte jamais validé n'est jamais "suspendu" au sens strict —
        // la réactivation est donc rejetée avant même la vérification de
        // validation (aucun suspendu_at à lever).
        $this->actingAs($admin)->postJson("/api/v1/administration/agents/{$agentId}/reactiver")->assertStatus(500);
    }

    public function test_assignation_des_roles_exige_l_habilitation_dediee_distincte(): void
    {
        // administrateur porte à la fois administration.gerer et
        // habilitations.gerer (RolesEtPermissionsSeeder) : on isole ici la
        // permission pour vérifier qu'elle est bien vérifiée séparément —
        // sans passer par le rôle, qui porterait les deux.
        $adminSansHabilitations = $this->opj();
        $adminSansHabilitations->syncRoles([]);
        $adminSansHabilitations->givePermissionTo('administration.gerer');
        $agentId = $this->creerCompte($this->administrateur());

        $this->actingAs($adminSansHabilitations)
            ->postJson("/api/v1/administration/agents/{$agentId}/roles", ['roles' => ['opj']])
            ->assertForbidden();

        $admin = $this->administrateur();
        $assignation = $this->actingAs($admin)
            ->postJson("/api/v1/administration/agents/{$agentId}/roles", ['roles' => ['opj', 'chef_unite']]);
        $assignation->assertOk();
        $this->assertEqualsCanonicalizing(['opj', 'chef_unite'], $assignation->json('roles'));

        // Remplace l'ensemble, ne complète pas.
        $this->actingAs($admin)->postJson("/api/v1/administration/agents/{$agentId}/roles", ['roles' => ['greffier']])
            ->assertOk()->assertJsonPath('roles', ['greffier']);
    }

    /**
     * CONSTAT DE SÉCURITÉ (revue du 2026-08-27) : un titulaire de la seule
     * `habilitations.gerer` pouvait s'auto-accorder (ou accorder à un
     * complice) le rôle `administrateur`, qui porte en plus
     * `administration.gerer` — élévation de privilèges immédiate. Corrigé
     * dans AssignerRolesAction : on ne peut jamais accorder plus de
     * permissions qu'on n'en détient soi-même.
     */
    public function test_on_ne_peut_pas_accorder_un_role_dont_les_permissions_depassent_les_siennes(): void
    {
        $agentLimite = $this->opj();
        $agentLimite->syncRoles([]);
        $agentLimite->givePermissionTo('habilitations.gerer');
        $cible = $this->creerCompte($this->administrateur());

        // Tentative d'auto-élévation : s'accorder à soi-même le rôle
        // administrateur, qui porte administration.gerer en plus.
        $this->actingAs($agentLimite)
            ->postJson("/api/v1/administration/agents/{$agentLimite->id}/roles", ['roles' => ['administrateur']])
            ->assertStatus(500);
        $this->assertTrue($agentLimite->fresh()->hasRole('administrateur') === false);

        // Même chose au bénéfice d'un tiers/complice.
        $this->actingAs($agentLimite)
            ->postJson("/api/v1/administration/agents/{$cible}/roles", ['roles' => ['administrateur']])
            ->assertStatus(500);

        // Un rôle dont les permissions sont un sous-ensemble de celles déjà
        // détenues reste accordable normalement.
        $this->actingAs($agentLimite)
            ->postJson("/api/v1/administration/agents/{$cible}/roles", ['roles' => []])
            ->assertOk();
    }

    public function test_un_role_inconnu_est_rejete(): void
    {
        $admin = $this->administrateur();
        $agentId = $this->creerCompte($admin);

        $this->actingAs($admin)
            ->postJson("/api/v1/administration/agents/{$agentId}/roles", ['roles' => ['role_qui_n_existe_pas']])
            ->assertStatus(422);
    }

    public function test_creer_une_infraction_ajoute_une_nouvelle_version_sans_toucher_aux_existantes(): void
    {
        $admin = $this->administrateur();
        $ancienne = Infraction::query()->create([
            'code' => 'CP-999', 'libelle' => 'Ancienne version', 'categorie' => 'delit', 'date_entree_vigueur' => '2020-01-01',
        ]);

        $reponse = $this->actingAs($admin)->postJson('/api/v1/administration/infractions', [
            'code' => 'CP-999', 'libelle' => 'Nouvelle version (réforme)', 'categorie' => 'delit',
            'date_entree_vigueur' => now()->toDateString(),
        ]);

        $reponse->assertCreated();
        $this->assertDatabaseHas('infractions', ['id' => $ancienne->id, 'libelle' => 'Ancienne version']);
        $this->assertDatabaseHas('infractions', ['libelle' => 'Nouvelle version (réforme)']);
        $this->assertSame(2, Infraction::query()->where('code', 'CP-999')->count());
    }

    public function test_un_agent_sans_habilitation_ne_peut_pas_creer_d_infraction(): void
    {
        $this->actingAs($this->opj())->postJson('/api/v1/administration/infractions', [
            'code' => 'CP-1', 'libelle' => 'Test', 'categorie' => 'delit', 'date_entree_vigueur' => now()->toDateString(),
        ])->assertForbidden();
    }
}
