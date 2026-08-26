<?php

namespace App\Http\Controllers\Api\V1\Casier;

use App\Domain\Casier\Models\Condamnation;
use App\Domain\Casier\Models\Consultation;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationCasierResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Historique des consultations nominatives d'une personne (§6.10) : un
 * contrôle d'accès a posteriori — « qui a consulté ce casier, et pourquoi »
 * — distinct de la consultation elle-même (BulletinController), gouverné
 * par `casier.gerer` plutôt que `casier.consulter_nominatif` : consulter
 * l'historique des accès n'est pas soi-même un accès nominatif au contenu.
 */
class ConsultationController extends Controller
{
    public function index(Personne $personne): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Condamnation::class);

        $consultations = Consultation::query()
            ->where('personne_id', $personne->id)
            ->with('consultePar')
            ->latest('consultee_at')
            ->get();

        return ConsultationCasierResource::collection($consultations);
    }
}
