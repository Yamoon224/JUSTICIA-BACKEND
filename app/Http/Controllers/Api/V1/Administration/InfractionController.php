<?php

namespace App\Http\Controllers\Api\V1\Administration;

use App\Domain\Administration\Actions\CreerInfractionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\CreerInfractionRequest;
use Illuminate\Http\JsonResponse;

/**
 * Écriture sur le référentiel des infractions (§6.13) — la lecture reste
 * portée par App\Http\Controllers\Api\V1\Referentiels\ReferentielController,
 * ouverte à tout agent ; ici, réservé à l'administration.
 */
class InfractionController extends Controller
{
    public function store(CreerInfractionRequest $request, CreerInfractionAction $action): JsonResponse
    {
        $infraction = $action->executer($request->validated(), $request->user());

        return response()->json($infraction)->setStatusCode(201);
    }
}
