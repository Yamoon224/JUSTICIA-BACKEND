<?php

namespace App\Http\Controllers\Api\V1\Affaires;

use App\Domain\Affaires\Actions\RectifierProcesVerbalAction;
use App\Domain\Affaires\Actions\RedigerProcesVerbalAction;
use App\Domain\Affaires\Actions\SignerProcesVerbalAction;
use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\ProcesVerbal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Affaires\RectifierProcesVerbalRequest;
use App\Http\Requests\Affaires\RedigerProcesVerbalRequest;
use App\Http\Requests\Affaires\SignerProcesVerbalRequest;
use App\Http\Resources\ProcesVerbalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProcesVerbalController extends Controller
{
    public function store(RedigerProcesVerbalRequest $request, Affaire $affaire, RedigerProcesVerbalAction $action): JsonResponse
    {
        $pv = $action->executer($affaire, $request->string('type')->toString(), $request->string('contenu')->toString(), $request->user());

        return ProcesVerbalResource::make($pv)->response()->setStatusCode(201);
    }

    public function signer(SignerProcesVerbalRequest $request, ProcesVerbal $procesVerbal, SignerProcesVerbalAction $action): ProcesVerbalResource
    {
        return ProcesVerbalResource::make($action->executer($procesVerbal, $request->user()));
    }

    public function rectifier(RectifierProcesVerbalRequest $request, ProcesVerbal $procesVerbal, RectifierProcesVerbalAction $action): ProcesVerbalResource
    {
        return ProcesVerbalResource::make($action->executer($procesVerbal, $request->string('contenu')->toString(), $request->user()));
    }

    public function show(ProcesVerbal $procesVerbal): ProcesVerbalResource
    {
        Gate::authorize('view', $procesVerbal->affaire);

        return ProcesVerbalResource::make($procesVerbal);
    }
}
