<?php

namespace App\Http\Controllers\Api\V1\Alertes;

use App\Domain\Alertes\Actions\MarquerAlerteLueAction;
use App\Domain\Alertes\Models\Alerte;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlerteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Alertes personnelles (§6.1, §6.11) : chaque agent ne voit et ne clôt que
 * les siennes — pas de notion de ressort ici, une alerte est nominative par
 * construction (CreerAlerteAction).
 */
class AlerteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $alertes = Alerte::query()
            ->where('destinataire_id', $request->user()->id)
            ->when($request->boolean('non_lues'), fn ($query) => $query->whereNull('lue_at'))
            ->latest()
            ->paginate(25);

        return AlerteResource::collection($alertes);
    }

    public function marquerLue(Request $request, Alerte $alerte, MarquerAlerteLueAction $action): AlerteResource
    {
        abort_if($alerte->destinataire_id !== $request->user()->id, 403);

        return AlerteResource::make($action->executer($alerte));
    }
}
