<?php

namespace App\Http\Controllers\Api\V1\Instruction;

use App\Domain\Instruction\Actions\AffecterJugeInstructionAction;
use App\Domain\Instruction\Actions\EmettreMandatAction;
use App\Domain\Instruction\Actions\EnregistrerActeInstructionAction;
use App\Domain\Instruction\Actions\MettreEnExamenAction;
use App\Domain\Instruction\Actions\PlacerEnDetentionProvisoireAction;
use App\Domain\Instruction\Actions\PlacerSousControleJudiciaireAction;
use App\Domain\Instruction\Actions\RendreOrdonnanceAction;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instruction\AffecterJugeInstructionRequest;
use App\Http\Requests\Instruction\EmettreMandatRequest;
use App\Http\Requests\Instruction\EnregistrerActeInstructionRequest;
use App\Http\Requests\Instruction\MettreEnExamenRequest;
use App\Http\Requests\Instruction\PlacerEnDetentionProvisoireRequest;
use App\Http\Requests\Instruction\PlacerSousControleJudiciaireRequest;
use App\Http\Requests\Instruction\RendreOrdonnanceRequest;
use App\Http\Resources\ActeInstructionResource;
use App\Http\Resources\DossierInstructionResource;
use App\Http\Resources\MandatResource;
use App\Http\Resources\MesureSureteResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Dossier d'information (§6.6).
 */
class DossierInstructionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('instruction.gerer');

        $agent = $request->user();

        $dossiers = DossierInstruction::query()
            ->with('affaire')
            ->when(
                ! $agent->can('administration.gerer'),
                fn ($query) => $query->whereHas('affaire', fn ($q) => $q->where('ressort_id', $agent->ressort_id)),
            )
            ->when($request->string('filtre')->toString() === 'non_affectes', fn ($query) => $query->whereNull('juge_instruction_id'))
            ->when($request->string('filtre')->toString() === 'mon_portefeuille', fn ($query) => $query
                ->where('juge_instruction_id', $agent->id)
                ->where('statut', 'en_cours'))
            ->latest('ouvert_at')
            ->paginate(25);

        return DossierInstructionResource::collection($dossiers);
    }

    public function show(DossierInstruction $dossier): DossierInstructionResource
    {
        Gate::authorize('view', $dossier);

        return DossierInstructionResource::make(
            $dossier->load(['affaire.infractions', 'affaire.personnes', 'actes', 'mandats', 'mesuresSurete']),
        );
    }

    public function affecter(AffecterJugeInstructionRequest $request, DossierInstruction $dossier, AffecterJugeInstructionAction $action): DossierInstructionResource
    {
        $juge = User::query()->findOrFail($request->integer('juge_id'));

        return DossierInstructionResource::make($action->executer($dossier, $juge, $request->user()));
    }

    public function mettreEnExamen(MettreEnExamenRequest $request, DossierInstruction $dossier, MettreEnExamenAction $action): DossierInstructionResource
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $action->executer($dossier, $personne, $request->string('statut')->toString(), $request->user());

        return DossierInstructionResource::make($dossier->load('affaire.personnes'));
    }

    public function enregistrerActe(EnregistrerActeInstructionRequest $request, DossierInstruction $dossier, EnregistrerActeInstructionAction $action): JsonResponse
    {
        $datePrevue = $request->filled('date_prevue') ? Carbon::parse($request->string('date_prevue')->toString()) : null;

        $acte = $action->executer($dossier, $request->string('type')->toString(), $request->string('description')->toString() ?: null, $datePrevue, $request->user());

        return ActeInstructionResource::make($acte)->response()->setStatusCode(201);
    }

    public function emettreMandat(EmettreMandatRequest $request, DossierInstruction $dossier, EmettreMandatAction $action): JsonResponse
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $mandat = $action->executer($dossier, $personne, $request->string('type')->toString(), $request->user());

        return MandatResource::make($mandat)->response()->setStatusCode(201);
    }

    public function placerSousControleJudiciaire(PlacerSousControleJudiciaireRequest $request, DossierInstruction $dossier, PlacerSousControleJudiciaireAction $action): JsonResponse
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $mesure = $action->executer($dossier, $personne, $request->string('obligations')->toString(), $request->user());

        return MesureSureteResource::make($mesure)->response()->setStatusCode(201);
    }

    public function placerEnDetentionProvisoire(PlacerEnDetentionProvisoireRequest $request, DossierInstruction $dossier, PlacerEnDetentionProvisoireAction $action): JsonResponse
    {
        $personne = Personne::query()->findOrFail($request->integer('personne_id'));

        $mesure = $action->executer($dossier, $personne, $request->user());

        return MesureSureteResource::make($mesure)->response()->setStatusCode(201);
    }

    public function rendreOrdonnance(RendreOrdonnanceRequest $request, DossierInstruction $dossier, RendreOrdonnanceAction $action): DossierInstructionResource
    {
        return DossierInstructionResource::make($action->executer($dossier, $request->string('ordonnance')->toString(), $request->user()));
    }
}
