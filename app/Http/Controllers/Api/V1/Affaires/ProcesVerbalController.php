<?php

namespace App\Http\Controllers\Api\V1\Affaires;

use App\Domain\Affaires\Actions\RectifierProcesVerbalAction;
use App\Domain\Affaires\Actions\RedigerProcesVerbalAction;
use App\Domain\Affaires\Actions\SignerProcesVerbalAction;
use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\ProcesVerbal;
use App\Domain\Contracts\GenerateurPdf;
use App\Domain\Support\EmpreinteDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Affaires\RectifierProcesVerbalRequest;
use App\Http\Requests\Affaires\RedigerProcesVerbalRequest;
use App\Http\Requests\Affaires\SignerProcesVerbalRequest;
use App\Http\Resources\ProcesVerbalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProcesVerbalController extends Controller
{
    public function store(RedigerProcesVerbalRequest $request, Affaire $affaire, RedigerProcesVerbalAction $action): JsonResponse
    {
        $pv = $action->executer($affaire, $request->string('type')->toString(), $request->string('contenu')->toString(), $request->user());

        return ProcesVerbalResource::make($pv)->response()->setStatusCode(201);
    }

    public function signer(SignerProcesVerbalRequest $request, ProcesVerbal $procesVerbal, SignerProcesVerbalAction $action): ProcesVerbalResource
    {
        return ProcesVerbalResource::make($action->executer($procesVerbal, $request->user()));
    }

    public function rectifier(RectifierProcesVerbalRequest $request, ProcesVerbal $procesVerbal, RectifierProcesVerbalAction $action): ProcesVerbalResource
    {
        return ProcesVerbalResource::make($action->executer($procesVerbal, $request->string('contenu')->toString(), $request->user()));
    }

    public function show(ProcesVerbal $procesVerbal): ProcesVerbalResource
    {
        Gate::authorize('view', $procesVerbal->affaire);

        return ProcesVerbalResource::make($procesVerbal);
    }

    /**
     * Édition au format légal (§6.3, §9). Un PV non signé reste
     * téléchargeable — utile pour relecture avant signature — mais porte la
     * mention explicite « projet » (voir le gabarit) : jamais confondu avec
     * l'original signé.
     */
    public function telechargerPdf(ProcesVerbal $procesVerbal, GenerateurPdf $pdf): Response
    {
        Gate::authorize('view', $procesVerbal->affaire);

        $procesVerbal->load(['affaire', 'redacteur']);

        $empreinte = EmpreinteDocument::calculer([
            'proces_verbal_id' => $procesVerbal->id,
            'cote' => $procesVerbal->cote,
            'contenu' => $procesVerbal->contenu,
            'signe_at' => $procesVerbal->signe_at?->toIso8601String(),
        ]);

        $contenu = $pdf->depuisVue('pdf.proces-verbal', [
            'eyebrow' => '§6.3 — Procès-verbal',
            'titre' => "PV {$procesVerbal->cote}",
            'genereAt' => now()->format('d/m/Y H:i'),
            'empreinte' => $empreinte,
            'pv' => $procesVerbal,
        ]);

        return response($contenu, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"pv-{$procesVerbal->cote}.pdf\"");
    }
}
