<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/**
 * Authentifie un agent par matricule + mot de passe et émet un jeton d'accès
 * personnel (§8 : authentification forte, comptes strictement individuels et
 * nominatifs). Toute connexion réussie est journalisée (§8, §10.1-S : action
 * métier dédiée, contrôleur mince).
 */
class AuthentifierAgentAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(string $matricule, string $password, string $deviceName): NewAccessToken
    {
        $agent = User::query()->where('matricule', $matricule)->first();

        if (! $agent || ! Hash::check($password, $agent->password)) {
            throw new AuthenticationException('Identifiants invalides.');
        }

        if (! $agent->actif || $agent->suspendu_at) {
            throw new AuthenticationException('Compte suspendu ou désactivé.');
        }

        $token = $agent->createToken($deviceName);

        $this->audit->consigner('auth.connexion', acteur: $agent, payloadSupplementaire: [
            'device_name' => $deviceName,
        ]);

        return $token;
    }
}
