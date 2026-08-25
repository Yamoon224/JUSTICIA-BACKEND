<?php

namespace App\Http\Controllers\Api\V1\Audiencement;

use App\Domain\Audiencement\Actions\EnregistrerDecisionAction;
use App\Domain\Audiencement\Actions\EnrolerAffaireAction;
use App\Domain\Audiencement\Actions\RenvoyerAudienceAction;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audiencement\EnregistrerDecisionRequest;
use App\Http\Requests\Audiencement\EnrolerRequest;
use App\Http\Requests\Audiencement\RenvoyerRequest;
use App\Http\Resources\DecisionResource;
use App\Http\Resources\DossierAudiencementResource;
use App\Models\Juridiction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Dossier d'audiencement (§6.7).
 */
class DossierAudiencementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('audiencement.gerer');

        $agent = $request->user();

        $dossiers = DossierAudiencement::query()
            ->with('affaire')
            ->when(
                ! $agent->can('administration.gerer'),
                fn ($query) => $query->whereHas('affaire', fn ($q) => $q->where('ressort_id', $agent->ressort_id)),
            )
            ->when($request->string('filtre')->toString() === 'a_enroler', fn ($query) => $query->where('statut', 'a_enroler'))
            ->when($request->string('filtre')->toString() === 'a_venir', fn ($query) => $query
                ->where('statut', 'enrole')
                ->where('date_audience', '>=', now()))
            ->orderBy('date_audience')
            ->paginate(25);

        return DossierAudiencementResource::collection($dossiers);
    }

    public function show(DossierAudiencement $dossier): DossierAudiencementResource
    {
        Gate::authorize('view', $dossier);

        return DossierAudiencementResource::make(
            $dossier->load(['affaire.infractions', 'affaire.personnes', 'renvois', 'decisions.recours']),
        );
    }

    public function enroler(EnrolerRequest $request, DossierAudiencement $dossier, EnrolerAffaireAction $action): DossierAudiencementResource
    {
        $juridiction = Juridiction::query()->findOrFail($request->integer('juridiction_id'));
        $president = User::query()->findOrFail($request->integer('president_id'));
        $greffier = User::query()->findOrFail($request->integer('greffier_id'));

        return DossierAudiencementResource::make($action->executer(
            $dossier,
            $juridiction,
            $request->string('chambre')->toString(),
            Carbon::parse($request->string('date_audience')->toString()),
            $president,
            $greffier,
            $request->user(),
        ));
    }

    public function renvoyer(RenvoyerRequest $request, DossierAudiencement $dossier, RenvoyerAudienceAction $action): DossierAudiencementResource
    {
        return DossierAudiencementResource::make($action->executer(
            $dossier,
            Carbon::parse($request->string('nouvelle_date')->toString()),
            $request->string('motif')->toString(),
            $request->user(),
        ));
    }

    public function enregistrerDecision(EnregistrerDecisionRequest $request, DossierAudiencement $dossier, EnregistrerDecisionAction $action): JsonResponse
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $decision = $action->executer(
            $dossier,
            $personne,
            $request->string('decision')->toString(),
            $request->string('peine_principale')->toString() ?: null,
            $request->boolean('sursis'),
            $request->string('interets_civils')->toString() ?: null,
            $request->user(),
        );

        return DecisionResource::make($decision)->response()->setStatusCode(201);
    }
}
