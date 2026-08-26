<?php

namespace App\Http\Controllers\Api\V1\Affaires;

use App\Domain\Affaires\Actions\OuvrirAffaireAction;
use App\Domain\Affaires\Actions\RattacherPersonneAAffaireAction;
use App\Domain\Affaires\Actions\TransmettreAuParquetAction;
use App\Domain\Affaires\Models\Affaire;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Affaires\OuvrirAffaireRequest;
use App\Http\Requests\Affaires\RattacherPersonneRequest;
use App\Http\Requests\Affaires\TransmettreAuParquetRequest;
use App\Http\Resources\AffaireResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AffaireController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Affaire::class);

        $agent = $request->user();

        $affaires = Affaire::query()
            ->when(
                ! $agent->can('affaires.superviser') && ! $agent->can('administration.gerer'),
                fn ($query) => $query->where('ressort_id', $agent->ressort_id),
            )
            ->latest()
            ->paginate(25);

        return AffaireResource::collection($affaires);
    }

    public function store(OuvrirAffaireRequest $request, OuvrirAffaireAction $action): JsonResponse
    {
        $affaire = $action->executer($request->safe()->except('infractions'), $request->user());

        if ($infractions = $request->input('infractions')) {
            $affaire->infractions()->sync($infractions);
        }

        return AffaireResource::make($affaire->load('infractions'))->response()->setStatusCode(201);
    }

    public function show(Affaire $affaire): AffaireResource
    {
        Gate::authorize('view', $affaire);

        return AffaireResource::make($affaire->load(['infractions', 'personnes', 'procesVerbaux', 'scelles.mouvements', 'scelles.documents', 'documents']));
    }

    public function rattacherPersonne(RattacherPersonneRequest $request, Affaire $affaire, RattacherPersonneAAffaireAction $action): AffaireResource
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $action->executer($affaire, $personne, $request->string('statut')->toString(), $request->user());

        return AffaireResource::make($affaire->load('personnes'));
    }

    public function transmettreAuParquet(TransmettreAuParquetRequest $request, Affaire $affaire, TransmettreAuParquetAction $action): AffaireResource
    {
        return AffaireResource::make($action->executer($affaire, $request->user()));
    }
}
