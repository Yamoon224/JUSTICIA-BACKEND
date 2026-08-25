<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Execution\Actions\MettreAExecutionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\MettreAExecutionRequest;
use App\Http\Resources\DossierExecutionResource;
use Illuminate\Http\JsonResponse;

class MiseAExecutionController extends Controller
{
    public function mettreAExecution(MettreAExecutionRequest $request, Decision $decision, MettreAExecutionAction $action): JsonResponse
    {
        $dossier = $action->executer($decision, $request->user());

        return DossierExecutionResource::make($dossier)->response()->setStatusCode(201);
    }
}
