<?php

namespace App\Http\Controllers\Api\V1\Instruction;

use App\Domain\Instruction\Actions\MettreAJourMandatAction;
use App\Domain\Instruction\Models\Mandat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instruction\MettreAJourMandatRequest;
use App\Http\Resources\MandatResource;

class MandatController extends Controller
{
    public function mettreAJour(MettreAJourMandatRequest $request, Mandat $mandat, MettreAJourMandatAction $action): MandatResource
    {
        return MandatResource::make($action->executer($mandat, $request->string('etape')->toString(), $request->user()));
    }
}
