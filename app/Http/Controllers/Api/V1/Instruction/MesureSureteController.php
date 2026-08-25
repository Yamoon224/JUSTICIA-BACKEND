<?php

namespace App\Http\Controllers\Api\V1\Instruction;

use App\Domain\Instruction\Actions\LeverMesureSureteAction;
use App\Domain\Instruction\Actions\RenouvelerDetentionProvisoireAction;
use App\Domain\Instruction\Models\MesureSurete;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instruction\LeverMesureSureteRequest;
use App\Http\Requests\Instruction\RenouvelerDetentionProvisoireRequest;
use App\Http\Resources\MesureSureteResource;

class MesureSureteController extends Controller
{
    public function renouveler(RenouvelerDetentionProvisoireRequest $request, MesureSurete $mesure, RenouvelerDetentionProvisoireAction $action): MesureSureteResource
    {
        return MesureSureteResource::make($action->executer($mesure, $request->integer('jours'), $request->user()));
    }

    public function lever(LeverMesureSureteRequest $request, MesureSurete $mesure, LeverMesureSureteAction $action): MesureSureteResource
    {
        return MesureSureteResource::make($action->executer($mesure, $request->string('motif')->toString(), $request->user()));
    }
}
