<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Execution\Actions\MarquerAmendeRecouvreeAction;
use App\Domain\Execution\Models\Amende;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\MarquerAmendeRecouvreeRequest;
use App\Http\Resources\AmendeResource;

class AmendeController extends Controller
{
    public function marquerRecouvree(MarquerAmendeRecouvreeRequest $request, Amende $amende, MarquerAmendeRecouvreeAction $action): AmendeResource
    {
        return AmendeResource::make($action->executer($amende, $request->user()));
    }
}
