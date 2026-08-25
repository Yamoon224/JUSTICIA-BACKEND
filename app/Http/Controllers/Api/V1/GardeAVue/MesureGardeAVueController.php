<?php

namespace App\Http\Controllers\Api\V1\GardeAVue;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\GardeAVue\Actions\AviserRepresentantLegalAction;
use App\Domain\GardeAVue\Actions\CloturerGardeAVueAction;
use App\Domain\GardeAVue\Actions\EnregistrerActeGardeAVueAction;
use App\Domain\GardeAVue\Actions\NotifierDroitAction;
use App\Domain\GardeAVue\Actions\PlacerEnGardeAVueAction;
use App\Domain\GardeAVue\Actions\ProlongerGardeAVueAction;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\GardeAVue\AviserRepresentantLegalRequest;
use App\Http\Requests\GardeAVue\CloturerGardeAVueRequest;
use App\Http\Requests\GardeAVue\EnregistrerActeGardeAVueRequest;
use App\Http\Requests\GardeAVue\NotifierDroitRequest;
use App\Http\Requests\GardeAVue\PlacerEnGardeAVueRequest;
use App\Http\Requests\GardeAVue\ProlongerGardeAVueRequest;
use App\Http\Resources\MesureGardeAVueResource;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MesureGardeAVueController extends Controller
{
    public function store(PlacerEnGardeAVueRequest $request, PlacerEnGardeAVueAction $action): JsonResponse
    {
        $affaire = Affaire::query()->findOrFail($request->integer('affaire_id'));
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));
        $unite = Unite::query()->findOrFail($request->integer('unite_id'));
        $debut = $request->filled('debut_at') ? Carbon::parse($request->string('debut_at')->toString()) : null;

        $mesure = $action->executer($affaire, $personne, $unite, $request->user(), $debut);

        return MesureGardeAVueResource::make($mesure)->response()->setStatusCode(201);
    }

    public function show(MesureGardeAVue $mesure): MesureGardeAVueResource
    {
        Gate::authorize('gav.gerer');

        return MesureGardeAVueResource::make($mesure->load(['notificationsDroits', 'actes']));
    }

    public function prolonger(ProlongerGardeAVueRequest $request, MesureGardeAVue $mesure, ProlongerGardeAVueAction $action): MesureGardeAVueResource
    {
        $autorisePar = User::query()->findOrFail($request->integer('autorise_par_id'));

        return MesureGardeAVueResource::make($action->executer($mesure, $request->integer('heures'), $autorisePar, $request->user()));
    }

    public function notifierDroit(NotifierDroitRequest $request, MesureGardeAVue $mesure, NotifierDroitAction $action): MesureGardeAVueResource
    {
        $action->executer($mesure, $request->string('droit')->toString(), $request->string('mode_de_remise')->toString(), $request->user());

        return MesureGardeAVueResource::make($mesure->load('notificationsDroits'));
    }

    public function aviserRepresentantLegal(AviserRepresentantLegalRequest $request, MesureGardeAVue $mesure, AviserRepresentantLegalAction $action): MesureGardeAVueResource
    {
        return MesureGardeAVueResource::make($action->executer($mesure, $request->user()));
    }

    public function enregistrerActe(EnregistrerActeGardeAVueRequest $request, MesureGardeAVue $mesure, EnregistrerActeGardeAVueAction $action): MesureGardeAVueResource
    {
        $action->executer(
            $mesure,
            $request->string('type')->toString(),
            Carbon::parse($request->string('debut_at')->toString()),
            $request->filled('fin_at') ? Carbon::parse($request->string('fin_at')->toString()) : null,
            $request->string('notes')->toString() ?: null,
            $request->user(),
        );

        return MesureGardeAVueResource::make($mesure->load('actes'));
    }

    public function cloturer(CloturerGardeAVueRequest $request, MesureGardeAVue $mesure, CloturerGardeAVueAction $action): MesureGardeAVueResource
    {
        return MesureGardeAVueResource::make($action->executer($mesure, $request->string('issue')->toString(), $request->user()));
    }
}
