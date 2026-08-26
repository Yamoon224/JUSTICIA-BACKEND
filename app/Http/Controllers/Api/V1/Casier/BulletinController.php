<?php

namespace App\Http\Controllers\Api\V1\Casier;

use App\Domain\Casier\Actions\GenererBulletinAction;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Casier\GenererBulletinRequest;
use App\Http\Resources\CondamnationResource;
use Illuminate\Http\JsonResponse;

class BulletinController extends Controller
{
    public function generer(GenererBulletinRequest $request, Personne $personne, GenererBulletinAction $action): JsonResponse
    {
        $type = $request->string('type')->toString();

        $condamnations = $action->executer(
            $personne,
            $type,
            $request->string('motif')->toString(),
            $request->user(),
        );

        return response()->json([
            'personne_id' => $personne->id,
            'type' => $type,
            'genere_at' => now()->toIso8601String(),
            'condamnations' => CondamnationResource::collection($condamnations),
        ]);
    }
}
