<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Execution\Actions\DecideAmenagementAction;
use App\Domain\Execution\Actions\EnregistrerRemiseDePeineAction;
use App\Domain\Execution\Actions\LibererAction;
use App\Domain\Execution\Actions\TransfererAction;
use App\Domain\Execution\Models\Ecrou;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\DecideAmenagementRequest;
use App\Http\Requests\Execution\EnregistrerRemiseDePeineRequest;
use App\Http\Requests\Execution\LibererRequest;
use App\Http\Requests\Execution\TransfererRequest;
use App\Http\Resources\AmenagementPeineResource;
use App\Http\Resources\EcrouResource;
use App\Models\EtablissementPenitentiaire;
use Illuminate\Http\JsonResponse;

class EcrouController extends Controller
{
    public function enregistrerRemiseDePeine(EnregistrerRemiseDePeineRequest $request, Ecrou $ecrou, EnregistrerRemiseDePeineAction $action): EcrouResource
    {
        return EcrouResource::make($action->executer($ecrou, $request->integer('jours'), $request->string('motif')->toString(), $request->user()));
    }

    public function liberer(LibererRequest $request, Ecrou $ecrou, LibererAction $action): EcrouResource
    {
        return EcrouResource::make($action->executer($ecrou, $request->string('motif')->toString(), $request->user()));
    }

    public function transferer(TransfererRequest $request, Ecrou $ecrou, TransfererAction $action): EcrouResource
    {
        $destination = EtablissementPenitentiaire::query()->findOrFail($request->integer('etablissement_destination_id'));

        return EcrouResource::make($action->executer($ecrou, $destination, $request->string('motif')->toString() ?: null, $request->user()));
    }

    public function decideAmenagement(DecideAmenagementRequest $request, Ecrou $ecrou, DecideAmenagementAction $action): JsonResponse
    {
        $amenagement = $action->executer($ecrou, $request->string('type')->toString(), $request->user());

        return AmenagementPeineResource::make($amenagement)->response()->setStatusCode(201);
    }
}
