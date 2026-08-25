<?php

namespace App\Http\Controllers\Api\V1\Audiencement;

use App\Domain\Audiencement\Actions\IntegrerDecisionRecoursAction;
use App\Domain\Audiencement\Models\Recours;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audiencement\IntegrerDecisionRecoursRequest;
use App\Http\Resources\RecoursResource;

class RecoursController extends Controller
{
    public function integrerDecision(IntegrerDecisionRecoursRequest $request, Recours $recours, IntegrerDecisionRecoursAction $action): RecoursResource
    {
        return RecoursResource::make($action->executer($recours, $request->string('issue')->toString(), $request->user()));
    }
}
