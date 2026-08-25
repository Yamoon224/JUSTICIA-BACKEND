<?php

namespace App\Http\Controllers\Api\V1\Parquet;

use App\Domain\Parquet\Actions\AffecterMagistratAction;
use App\Domain\Parquet\Actions\EnregistrerRequisitionAction;
use App\Domain\Parquet\Actions\OrienterAction;
use App\Domain\Parquet\Models\DossierParquet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parquet\AffecterMagistratRequest;
use App\Http\Requests\Parquet\EnregistrerRequisitionRequest;
use App\Http\Requests\Parquet\OrienterRequest;
use App\Http\Resources\DossierParquetResource;
use App\Http\Resources\RequisitionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Bureau des arrivées et suivi de portefeuille du parquet (§6.5).
 */
class DossierParquetController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', DossierParquet::class);

        $agent = $request->user();

        $dossiers = DossierParquet::query()
            ->with('affaire')
            ->when(
                ! $agent->can('administration.gerer'),
                fn ($query) => $query->whereHas('affaire', fn ($q) => $q->where('ressort_id', $agent->ressort_id)),
            )
            ->when($request->string('filtre')->toString() === 'non_affectes', fn ($query) => $query->whereNull('magistrat_id'))
            ->when($request->string('filtre')->toString() === 'mon_portefeuille', fn ($query) => $query
                ->where('magistrat_id', $agent->id)
                ->whereNull('oriente_at'))
            ->latest('recu_at')
            ->paginate(25);

        return DossierParquetResource::collection($dossiers);
    }

    public function show(DossierParquet $dossier): DossierParquetResource
    {
        Gate::authorize('view', $dossier);

        return DossierParquetResource::make($dossier->load(['affaire.infractions', 'affaire.personnes', 'requisitions']));
    }

    public function affecter(AffecterMagistratRequest $request, DossierParquet $dossier, AffecterMagistratAction $action): DossierParquetResource
    {
        $magistrat = User::query()->findOrFail($request->integer('magistrat_id'));

        return DossierParquetResource::make($action->executer($dossier, $magistrat, $request->user()));
    }

    public function orienter(OrienterRequest $request, DossierParquet $dossier, OrienterAction $action): DossierParquetResource
    {
        return DossierParquetResource::make($action->executer(
            $dossier,
            $request->string('orientation')->toString(),
            $request->integer('motif_classement_id') ?: null,
            $request->user(),
        ));
    }

    public function enregistrerRequisition(EnregistrerRequisitionRequest $request, DossierParquet $dossier, EnregistrerRequisitionAction $action): JsonResponse
    {
        $requisition = $action->executer(
            $dossier,
            $request->string('type')->toString(),
            $request->string('contenu')->toString(),
            $request->user(),
        );

        return RequisitionResource::make($requisition)->response()->setStatusCode(201);
    }
}
