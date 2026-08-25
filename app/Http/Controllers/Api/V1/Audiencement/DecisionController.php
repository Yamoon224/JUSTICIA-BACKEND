<?php

namespace App\Http\Controllers\Api\V1\Audiencement;

use App\Domain\Audiencement\Actions\EnregistrerRecoursAction;
use App\Domain\Audiencement\Models\Decision;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audiencement\EnregistrerRecoursRequest;
use App\Http\Resources\RecoursResource;
use Illuminate\Http\JsonResponse;

class DecisionController extends Controller
{
    public function enregistrerRecours(EnregistrerRecoursRequest $request, Decision $decision, EnregistrerRecoursAction $action): JsonResponse
    {
        $formePar = $request->filled('formee_par_personne_id')
            ? Personne::query()->findOrFail($request->integer('formee_par_personne_id'))
            : null;

        $recours = $action->executer($decision, $request->string('type')->toString(), $formePar, $request->user());

        return RecoursResource::make($recours)->response()->setStatusCode(201);
    }
}
