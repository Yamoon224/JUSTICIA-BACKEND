<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Audit\AuditService;
use App\Domain\Auth\Actions\AuthentifierAgentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AgentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthentifierAgentAction $action): JsonResponse
    {
        $token = $action->executer(
            matricule: $request->string('matricule')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString(),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'agent' => AgentResource::make($token->accessToken->tokenable->load(['service', 'ressort'])),
        ]);
    }

    public function me(Request $request): AgentResource
    {
        return AgentResource::make($request->user()->load(['service', 'ressort']));
    }

    public function logout(Request $request, AuditService $audit): JsonResponse
    {
        $audit->consigner('auth.deconnexion', acteur: $request->user());

        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }
}
