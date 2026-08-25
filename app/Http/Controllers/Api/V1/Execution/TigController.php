<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Execution\Actions\EnregistrerHeuresTigAction;
use App\Domain\Execution\Models\TravailInteretGeneral;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\EnregistrerHeuresTigRequest;
use App\Http\Resources\TravailInteretGeneralResource;

class TigController extends Controller
{
    public function enregistrerHeures(EnregistrerHeuresTigRequest $request, TravailInteretGeneral $tig, EnregistrerHeuresTigAction $action): TravailInteretGeneralResource
    {
        return TravailInteretGeneralResource::make($action->executer($tig, $request->integer('heures'), $request->user()));
    }
}
