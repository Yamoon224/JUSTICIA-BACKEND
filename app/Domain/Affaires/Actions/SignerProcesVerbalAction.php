<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\ProcesVerbal;
use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Clôture et signature d'un PV (§6.3) : à partir de cet instant, l'acte est
 * immuable (voir ProcesVerbal::booted()) — toute correction ultérieure passe
 * obligatoirement par RectifierProcesVerbalAction.
 */
class SignerProcesVerbalAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(ProcesVerbal $pv, User $signataire): ProcesVerbal
    {
        $pv->signer($signataire);

        $this->audit->consigner('affaires.pv.signature', auditable: $pv, acteur: $signataire);

        return $pv;
    }
}
