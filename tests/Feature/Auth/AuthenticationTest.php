<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre le socle d'authentification (§8, §10.1) : émission/validation du
 * jeton, rejet d'un compte suspendu, et traçabilité de la connexion dans le
 * journal d'audit scellé.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_agent_actif_peut_se_connecter_et_recevoir_un_jeton(): void
    {
        $this->seed(RolesEtPermissionsSeeder::class);

        $service = Service::query()->create(['code' => 'PJ', 'nom' => 'Police judiciaire', 'type' => 'police']);
        $agent = User::factory()->create([
            'matricule' => 'OPJ-0001',
            'service_id' => $service->id,
            'password' => 'mot-de-passe-sur',
        ]);
        $agent->assignRole('opj');

        $response = $this->postJson('/api/v1/auth/login', [
            'matricule' => 'OPJ-0001',
            'password' => 'mot-de-passe-sur',
            'device_name' => 'tests',
        ]);

        $response->assertOk()->assertJsonPath('agent.matricule', 'OPJ-0001');
        $this->assertNotEmpty($response->json('token'));

        // La connexion réussie est journalisée dans le journal d'audit scellé.
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $agent->id,
            'action' => 'auth.connexion',
        ]);
        $log = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($log?->hash);

        $me = $this->withToken($response->json('token'))->getJson('/api/v1/auth/me');
        $me->assertOk()->assertJsonPath('matricule', 'OPJ-0001');
    }

    public function test_un_mot_de_passe_invalide_est_rejete(): void
    {
        $agent = User::factory()->create(['matricule' => 'OPJ-0002', 'password' => 'mot-de-passe-sur']);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricule' => $agent->matricule,
            'password' => 'mauvais-mot-de-passe',
            'device_name' => 'tests',
        ]);

        $response->assertUnauthorized();
    }

    public function test_un_compte_suspendu_ne_peut_pas_se_connecter(): void
    {
        $agent = User::factory()->create([
            'matricule' => 'OPJ-0003',
            'password' => 'mot-de-passe-sur',
            'suspendu_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'matricule' => $agent->matricule,
            'password' => 'mot-de-passe-sur',
            'device_name' => 'tests',
        ]);

        $response->assertUnauthorized();
    }
}
