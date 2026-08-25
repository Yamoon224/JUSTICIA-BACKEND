<?php

namespace App\Http\Controllers\Api\V1\Referentiels;

use App\Http\Controllers\Controller;
use App\Models\Infraction;
use App\Models\Unite;
use Illuminate\Http\JsonResponse;

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
}
