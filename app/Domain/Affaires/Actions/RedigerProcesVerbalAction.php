<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\ProcesVerbal;
use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Rédige un procès-verbal (§6.3). La cote est attribuée automatiquement à
 * la rédaction — le PV reste modifiable jusqu'à sa signature
 * (SignerProcesVerbalAction), qui le rend immuable.
 */
class RedigerProcesVerbalAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Affaire $affaire, string $type, string $contenu, User $agent): ProcesVerbal
    {
        $pv = ProcesVerbal::query()->create([
            'affaire_id' => $affaire->id,
            'cote' => $this->genererCote($affaire),
            'type' => $type,
            'contenu' => $contenu,
            'redige_par' => $agent->id,
        ]);

        $this->audit->consigner('affaires.pv.redaction', auditable: $pv, acteur: $agent);

        return $pv;
    }

    private function genererCote(Affaire $affaire): string
    {
        $rang = $affaire->procesVerbaux()->count() + 1;

        return sprintf('%s-PV%03d', $affaire->numero_affaire, $rang);
    }
}
