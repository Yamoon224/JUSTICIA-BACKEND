<?php

namespace App\Http\Controllers\Api\V1\Referentiels;

use App\Http\Controllers\Controller;
use App\Models\Infraction;
use App\Models\Juridiction;
use App\Models\MotifClassement;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lecture seule des référentiels nationaux (§6.13) nécessaires à la saisie
 * dans les interfaces « Web » (listes de choix). Ni acte de procédure ni
 * donnée sensible : accessible à tout agent authentifié, sans Action dédiée.
 */
class ReferentielController extends Controller
{
    public function infractions(): JsonResponse
    {
        $today = now()->toDateString();

        $infractions = Infraction::query()
            ->whereDate('date_entree_vigueur', '<=', $today)
            ->where(fn ($q) => $q->whereNull('date_fin_vigueur')->orWhereDate('date_fin_vigueur', '>=', $today))
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle', 'categorie']);

        return response()->json($infractions);
    }

    public function unites(): JsonResponse
    {
        $unites = Unite::query()
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'type', 'ressort_id']);

        return response()->json($unites);
    }

    public function motifsClassement(): JsonResponse
    {
        return response()->json(MotifClassement::query()->orderBy('libelle')->get(['id', 'code', 'libelle']));
    }

    /**
     * Magistrats du parquet du ressort de l'agent, pour l'affectation des
     * dossiers au bureau des arrivées (§6.5).
     */
    public function magistrats(Request $request): JsonResponse
    {
        return response()->json($this->agentsAyantLeRole('procureur', $request));
    }

    /**
     * Juges d'instruction du ressort de l'agent, pour l'affectation des
     * dossiers d'information (§6.6).
     */
    public function jugesInstruction(Request $request): JsonResponse
    {
        return response()->json($this->agentsAyantLeRole('juge_instruction', $request));
    }

    /**
     * Juges d'audience et greffiers du ressort de l'agent, pour
     * l'enrôlement des affaires (§6.7).
     */
    public function jugesAudience(Request $request): JsonResponse
    {
        return response()->json($this->agentsAyantLeRole('juge_audience', $request));
    }

    public function greffiers(Request $request): JsonResponse
    {
        return response()->json($this->agentsAyantLeRole('greffier', $request));
    }

    /**
     * Juridictions du ressort de l'agent, pour l'enrôlement (§6.7).
     */
    public function juridictions(Request $request): JsonResponse
    {
        $agent = $request->user();

        $juridictions = Juridiction::query()
            ->when(! $agent->can('administration.gerer'), fn ($q) => $q->where('ressort_id', $agent->ressort_id))
            ->orderBy('nom')
            ->get(['id', 'code', 'nom', 'type', 'ressort_id']);

        return response()->json($juridictions);
    }

    /**
     * @return Collection<int, User>
     */
    private function agentsAyantLeRole(string $role, Request $request): Collection
    {
        $agent = $request->user();

        return User::query()
            ->role($role)
            ->when(! $agent->can('administration.gerer'), fn ($q) => $q->where('ressort_id', $agent->ressort_id))
            ->orderBy('nom')
            ->get(['id', 'matricule', 'nom', 'prenom']);
    }
}
