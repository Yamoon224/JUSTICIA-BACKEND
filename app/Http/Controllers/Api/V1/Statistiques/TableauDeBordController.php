<?php

namespace App\Http\Controllers\Api\V1\Statistiques;

use App\Domain\Statistiques\Actions\GenererTableauDeBordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Statistiques\TableauDeBordRequest;
use Illuminate\Http\JsonResponse;

/**
 * Tableau de bord agrégé (§6.11, §6.12) : lecture seule, aucun état propre.
 */
class TableauDeBordController extends Controller
{
    public function afficher(TableauDeBordRequest $request, GenererTableauDeBordAction $action): JsonResponse
    {
        $agent = $request->user();

        // Un agent sans administration.gerer reste cantonné à son propre
        // ressort (§8), quel que soit le ressort_id qu'il aurait pu
        // transmettre — seul un administrateur peut choisir un ressort
        // arbitraire ou l'agrégat national (aucun ressort_id).
        $ressortId = $agent->can('administration.gerer')
            ? $request->integer('ressort_id') ?: null
            : $agent->ressort_id;

        return response()->json($action->executer($ressortId));
    }
}
