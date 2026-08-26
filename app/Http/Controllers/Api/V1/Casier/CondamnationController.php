<?php

namespace App\Http\Controllers\Api\V1\Casier;

use App\Domain\Casier\Actions\AmnistierAction;
use App\Domain\Casier\Actions\RehabiliterAction;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Casier\AmnistierRequest;
use App\Http\Requests\Casier\RehabiliterRequest;
use App\Http\Resources\CondamnationResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Gestion des condamnations inscrites au casier judiciaire (§6.10).
 * Aucune route de création : l'inscription est exclusivement automatique
 * (App\Domain\Casier\Actions\EnregistrerCondamnationCasierAction),
 * déclenchée par la mise à exécution d'une décision (module Execution).
 */
class CondamnationController extends Controller
{
    /**
     * Vue de gestion (pas une consultation nominative au sens §6.10) : liste
     * les condamnations d'une personne pour repérer celle à réhabiliter ou
     * amnistier — gouvernée par `casier.gerer`, pas
     * `casier.consulter_nominatif` (voir GenererBulletinRequest).
     */
    public function index(Personne $personne): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Condamnation::class);

        $condamnations = Condamnation::query()
            ->where('personne_id', $personne->id)
            ->with(['rehabilitation', 'amnistie'])
            ->latest('condamnee_at')
            ->get();

        return CondamnationResource::collection($condamnations);
    }

    public function rehabiliter(RehabiliterRequest $request, Condamnation $condamnation, RehabiliterAction $action): CondamnationResource
    {
        return CondamnationResource::make($action->executer($condamnation, $request->user())->load(['rehabilitation', 'amnistie']));
    }

    public function amnistier(AmnistierRequest $request, Condamnation $condamnation, AmnistierAction $action): CondamnationResource
    {
        $condamnation = $action->executer($condamnation, $request->string('texte_reference')->toString(), $request->user());

        return CondamnationResource::make($condamnation->load(['rehabilitation', 'amnistie']));
    }
}
