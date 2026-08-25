<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Execution\Actions\AffecterTigAction;
use App\Domain\Execution\Actions\EcrouerAction;
use App\Domain\Execution\Actions\PlacerSousMiseALEpreuveAction;
use App\Domain\Execution\Actions\TransmettreAmendeAction;
use App\Domain\Execution\Models\DossierExecution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\AffecterTigRequest;
use App\Http\Requests\Execution\EcrouerRequest;
use App\Http\Requests\Execution\PlacerSousMiseALEpreuveRequest;
use App\Http\Requests\Execution\TransmettreAmendeRequest;
use App\Http\Resources\AmendeResource;
use App\Http\Resources\DecisionAExecuterResource;
use App\Http\Resources\DossierExecutionResource;
use App\Http\Resources\EcrouResource;
use App\Http\Resources\MiseALEpreuveResource;
use App\Http\Resources\TravailInteretGeneralResource;
use App\Models\EtablissementPenitentiaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Dossier d'exécution des peines (§6.9).
 */
class DossierExecutionController extends Controller
{
    /**
     * Décisions de condamnation devenues définitives (§6.7) et pas encore
     * mises à exécution — le point d'entrée du service pénitentiaire, qui
     * n'a pas accès au dossier d'audiencement lui-même (celui-ci exige
     * `audiencement.gerer`, pas `execution.gerer`).
     */
    public function decisionsAExecuter(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('execution.gerer');

        $agent = $request->user();

        $decisions = Decision::query()
            ->with(['dossierAudiencement.affaire', 'personne'])
            ->where('decision', 'condamnation')
            ->where('delai_recours_expire_at', '<', now())
            ->whereDoesntHave('recours', fn ($q) => $q->where('recevable', true))
            ->whereDoesntHave('dossierExecution')
            ->when(
                ! $agent->can('administration.gerer'),
                fn ($query) => $query->whereHas(
                    'dossierAudiencement.affaire',
                    fn ($q) => $q->where('ressort_id', $agent->ressort_id),
                ),
            )
            ->latest('delai_recours_expire_at')
            ->paginate(25);

        return DecisionAExecuterResource::collection($decisions);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('execution.gerer');

        $agent = $request->user();

        $dossiers = DossierExecution::query()
            ->with('decision.dossierAudiencement.affaire.personnes')
            ->when(
                ! $agent->can('administration.gerer'),
                fn ($query) => $query->whereHas(
                    'decision.dossierAudiencement.affaire',
                    fn ($q) => $q->where('ressort_id', $agent->ressort_id),
                ),
            )
            ->when($request->string('filtre')->toString() === 'en_cours', fn ($query) => $query->where('statut', 'en_cours'))
            ->latest('mise_a_execution_at')
            ->paginate(25);

        return DossierExecutionResource::collection($dossiers);
    }

    public function show(DossierExecution $dossier): DossierExecutionResource
    {
        Gate::authorize('view', $dossier);

        return DossierExecutionResource::make($dossier->load([
            'decision.dossierAudiencement.affaire.personnes',
            'ecrou.remisesPeine',
            'ecrou.amenagements',
            'ecrou.transferts',
            'amende',
            'tig',
            'miseALEpreuve',
        ]));
    }

    public function ecrouer(EcrouerRequest $request, DossierExecution $dossier, EcrouerAction $action): JsonResponse
    {
        $etablissement = EtablissementPenitentiaire::query()->findOrFail($request->integer('etablissement_id'));

        $ecrou = $action->executer(
            $dossier,
            $etablissement,
            $request->integer('duree_jours'),
            $request->integer('detention_provisoire_imputee_jours'),
            $request->user(),
        );

        return EcrouResource::make($ecrou)->response()->setStatusCode(201);
    }

    public function transmettreAmende(TransmettreAmendeRequest $request, DossierExecution $dossier, TransmettreAmendeAction $action): JsonResponse
    {
        $amende = $action->executer($dossier, $request->integer('montant'), $request->user());

        return AmendeResource::make($amende)->response()->setStatusCode(201);
    }

    public function affecterTig(AffecterTigRequest $request, DossierExecution $dossier, AffecterTigAction $action): JsonResponse
    {
        $tig = $action->executer($dossier, $request->integer('heures_requises'), $request->string('affecte_a')->toString() ?: null, $request->user());

        return TravailInteretGeneralResource::make($tig)->response()->setStatusCode(201);
    }

    public function placerSousMiseALEpreuve(PlacerSousMiseALEpreuveRequest $request, DossierExecution $dossier, PlacerSousMiseALEpreuveAction $action): JsonResponse
    {
        $mise = $action->executer($dossier, $request->string('obligations')->toString(), $request->user());

        return MiseALEpreuveResource::make($mise)->response()->setStatusCode(201);
    }
}
