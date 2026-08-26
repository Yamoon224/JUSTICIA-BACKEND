<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Actions\AssignerRolesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AssignerRolesRequest;
use App\Http\Resources\AgentResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * Affectation des profils habilités (§6.13). Distincte de la gestion des
 * comptes (AgentController) : la permission `habilitations.gerer` peut être
 * déléguée sans donner par ailleurs le pouvoir de créer/suspendre des
 * comptes (`administration.gerer`) — cf. RolesEtPermissionsSeeder.
 */
class HabilitationController extends Controller
{
    public function roles(): JsonResponse
    {
        Gate::authorize('habilitations.gerer');

        return response()->json(Role::query()->orderBy('name')->pluck('name'));
    }

    public function assigner(AssignerRolesRequest $request, User $agent, AssignerRolesAction $action): AgentResource
    {
        return AgentResource::make($action->executer($agent, $request->input('roles', []), $request->user()));
    }
}
