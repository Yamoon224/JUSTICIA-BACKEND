<?php

namespace App\Http\Controllers\Api\V1\Personnes;

use App\Domain\Personnes\Actions\ConsulterPersonneAction;
use App\Domain\Personnes\Actions\CreerPersonneAction;
use App\Domain\Personnes\Actions\FusionnerPersonnesAction;
use App\Domain\Personnes\Actions\RechercherPersonnesAction;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Personnes\ConsulterPersonneRequest;
use App\Http\Requests\Personnes\CreerPersonneRequest;
use App\Http\Requests\Personnes\FusionnerPersonnesRequest;
use App\Http\Requests\Personnes\RechercherPersonnesRequest;
use App\Http\Resources\PersonneResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonneController extends Controller
{
    public function index(RechercherPersonnesRequest $request, RechercherPersonnesAction $action): AnonymousResourceCollection
    {
        $personnes = $action->executer(
            $request->string('nom')->toString() ?: null,
            $request->string('prenom')->toString() ?: null,
            $request->string('date_naissance')->toString() ?: null,
        );

        return PersonneResource::collection($personnes);
    }

    public function store(CreerPersonneRequest $request, CreerPersonneAction $action): JsonResponse
    {
        $personne = $action->executer($request->validated(), $request->user());

        return PersonneResource::make($personne)->response()->setStatusCode(201);
    }

    public function show(ConsulterPersonneRequest $request, Personne $personne, ConsulterPersonneAction $action): PersonneResource
    {
        $personne = $action->executer($personne, $request->user(), $request->string('motif')->toString());

        return PersonneResource::make($personne);
    }

    public function fusionner(FusionnerPersonnesRequest $request, Personne $personne, FusionnerPersonnesAction $action): PersonneResource
    {
        $absorbee = Personne::query()->findOrFail($request->integer('personne_absorbee_id'));

        return PersonneResource::make($action->executer($personne, $absorbee, $request->user()));
    }
}
