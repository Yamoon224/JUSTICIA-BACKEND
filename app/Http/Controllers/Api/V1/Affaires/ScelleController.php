<?php

namespace App\Http\Controllers\Api\V1\Affaires;

use App\Domain\Affaires\Actions\EnregistrerMouvementScelleAction;
use App\Domain\Affaires\Actions\EnregistrerScelleAction;
use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\Scelle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Affaires\EnregistrerMouvementScelleRequest;
use App\Http\Requests\Affaires\EnregistrerScelleRequest;
use App\Http\Resources\ScelleResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ScelleController extends Controller
{
    public function store(EnregistrerScelleRequest $request, Affaire $affaire, EnregistrerScelleAction $action): JsonResponse
    {
        $scelle = $action->executer(
            $affaire,
            $request->string('numero_scelle')->toString(),
            $request->string('description')->toString(),
            $request->string('lieu_saisie')->toString() ?: null,
            $request->user(),
        );

        return ScelleResource::make($scelle->load('mouvements'))->response()->setStatusCode(201);
    }

    public function enregistrerMouvement(EnregistrerMouvementScelleRequest $request, Scelle $scelle, EnregistrerMouvementScelleAction $action): ScelleResource
    {
        $remettant = $request->filled('remettant_id') ? User::query()->find($request->integer('remettant_id')) : null;
        $recepteur = $request->filled('recepteur_id') ? User::query()->find($request->integer('recepteur_id')) : null;

        $action->executer(
            $scelle,
            $request->string('type')->toString(),
            $remettant,
            $recepteur,
            $request->string('motif')->toString() ?: null,
            $request->user(),
        );

        return ScelleResource::make($scelle->refresh()->load('mouvements'));
    }
}
