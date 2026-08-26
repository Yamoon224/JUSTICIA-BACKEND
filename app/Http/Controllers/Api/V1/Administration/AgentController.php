<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Actions\CreerCompteAction;
use App\Domain\Administration\Actions\ReactiverCompteAction;
use App\Domain\Administration\Actions\SuspendreCompteAction;
use App\Domain\Administration\Actions\ValiderCompteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\CreerCompteRequest;
use App\Http\Requests\Administration\SuspendreCompteRequest;
use App\Http\Resources\AgentResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Gestion des comptes agents (§6.13). Toute donnée sensible (mot de passe)
 * reste hors de AgentResource (§8) ; la création/suspension à double
 * validation est portée par les Actions dédiées, pas ici (§10.1-S).
 */
class AgentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('administration.gerer');

        $agents = User::query()
            ->with(['service', 'ressort'])
            ->when($request->boolean('en_attente'), fn ($query) => $query->whereNull('valide_at'))
            ->orderBy('nom')
            ->paginate(25);

        return AgentResource::collection($agents);
    }

    public function store(CreerCompteRequest $request, CreerCompteAction $action): JsonResponse
    {
        $agent = $action->executer($request->validated(), $request->user());

        return AgentResource::make($agent)->response()->setStatusCode(201);
    }

    public function valider(Request $request, User $agent, ValiderCompteAction $action): AgentResource
    {
        Gate::authorize('administration.gerer');

        return AgentResource::make($action->executer($agent, $request->user()));
    }

    public function suspendre(SuspendreCompteRequest $request, User $agent, SuspendreCompteAction $action): AgentResource
    {
        return AgentResource::make($action->executer($agent, $request->user(), $request->string('motif')->toString() ?: null));
    }

    public function reactiver(Request $request, User $agent, ReactiverCompteAction $action): AgentResource
    {
        Gate::authorize('administration.gerer');

        return AgentResource::make($action->executer($agent, $request->user()));
    }
}
