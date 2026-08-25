<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Execution\Actions\LeverMiseALEpreuveAction;
use App\Domain\Execution\Models\MiseALEpreuve;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\LeverMiseALEpreuveRequest;
use App\Http\Resources\MiseALEpreuveResource;

class MiseALEpreuveController extends Controller
{
    public function lever(LeverMiseALEpreuveRequest $request, MiseALEpreuve $mise, LeverMiseALEpreuveAction $action): MiseALEpreuveResource
    {
        return MiseALEpreuveResource::make($action->executer($mise, $request->user()));
    }
}
