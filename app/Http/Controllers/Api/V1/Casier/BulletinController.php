<?php

namespace App\Http\Controllers\Api\V1\Casier;

use App\Domain\Casier\Actions\GenererBulletinAction;
use App\Domain\Contracts\GenerateurPdf;
use App\Domain\Personnes\Models\Personne;
use App\Domain\Support\EmpreinteDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Casier\GenererBulletinRequest;
use App\Http\Resources\CondamnationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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

    /**
     * Édition sécurisée au format légal (§6.10). Réutilise
     * GenererBulletinAction (même règles de filtrage, même journalisation
     * de la consultation) : ce n'est qu'une seconde représentation du même
     * résultat, jamais un second chemin de calcul.
     */
    public function telechargerPdf(GenererBulletinRequest $request, Personne $personne, GenererBulletinAction $action, GenerateurPdf $pdf): Response
    {
        $type = $request->string('type')->toString();
        $motif = $request->string('motif')->toString();

        $condamnations = $action->executer($personne, $type, $motif, $request->user());

        $empreinte = EmpreinteDocument::calculer([
            'personne_id' => $personne->id,
            'type' => $type,
            'condamnations' => $condamnations->pluck('id')->all(),
            'genere_at' => now()->toIso8601String(),
        ]);

        $contenu = $pdf->depuisVue('pdf.bulletin-casier', [
            'eyebrow' => '§6.10 — Bulletin '.strtoupper($type),
            'titre' => 'Bulletin '.strtoupper($type).' du casier judiciaire',
            'genereAt' => now()->format('d/m/Y H:i'),
            'empreinte' => $empreinte,
            'personne' => $personne,
            'condamnations' => $condamnations,
            'motif' => $motif,
        ]);

        return response($contenu, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"bulletin-{$type}-{$personne->id}.pdf\"");
    }
}
