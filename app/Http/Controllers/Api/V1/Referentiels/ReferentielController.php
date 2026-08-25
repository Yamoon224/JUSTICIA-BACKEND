<?php

namespace App\Http\Controllers\Api\V1\Referentiels;

use App\Http\Controllers\Controller;
use App\Models\Infraction;
use App\Models\MotifClassement;
use App\Models\Unite;
use App\Models\User;
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
        $agent = $request->user();

        $magistrats = User::query()
            ->role('procureur')
            ->when(! $agent->can('administration.gerer'), fn ($q) => $q->where('ressort_id', $agent->ressort_id))
            ->orderBy('nom')
            ->get(['id', 'matricule', 'nom', 'prenom']);

        return response()->json($magistrats);
    }
}
