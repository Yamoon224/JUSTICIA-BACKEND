<?php

namespace App\Domain\Parquet\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Parquet\Models\DossierParquet;
use App\Domain\Parquet\Models\Requisition;
use App\Models\User;

/**
 * Réquisitions du parquet consignées aux différentes étapes de la
 * procédure (§6.5) : placement en détention, peines requises...
 */
class EnregistrerRequisitionAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierParquet $dossier, string $type, string $contenu, User $magistrat): Requisition
    {
        $requisition = $dossier->requisitions()->create([
            'type' => $type,
            'contenu' => $contenu,
            'magistrat_id' => $magistrat->id,
        ]);

        $this->audit->consigner('parquet.requisition', auditable: $dossier, acteur: $magistrat, payloadSupplementaire: [
            'requisition_id' => $requisition->id,
            'type' => $type,
        ]);

        return $requisition;
    }
}
