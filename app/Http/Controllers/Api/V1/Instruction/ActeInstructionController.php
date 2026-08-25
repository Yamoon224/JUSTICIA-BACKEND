<?php

namespace App\Http\Controllers\Api\V1\Instruction;

use App\Domain\Instruction\Actions\MettreAJourActeInstructionAction;
use App\Domain\Instruction\Models\ActeInstruction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instruction\MettreAJourActeInstructionRequest;
use App\Http\Resources\ActeInstructionResource;

class ActeInstructionController extends Controller
{
    public function mettreAJour(MettreAJourActeInstructionRequest $request, ActeInstruction $acte, MettreAJourActeInstructionAction $action): ActeInstructionResource
    {
        return ActeInstructionResource::make($action->executer($acte, $request->string('statut')->toString(), $request->user()));
    }
}
