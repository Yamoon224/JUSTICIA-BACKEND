<?php

namespace App\Domain\Parquet\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Contracts\Horodatable;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\User;
use InvalidArgumentException;

/**
 * Affectation d'un dossier reçu au bureau des arrivées à un magistrat
 * (§6.5). Un dossier déjà orienté ne peut plus changer de magistrat par
 * cette voie — une réaffectation après orientation est un cas
 * exceptionnel qui sortirait du périmètre de cette action.
 */
class AffecterMagistratAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(DossierParquet $dossier, User $magistrat, User $acteur): DossierParquet
    {
        if ($dossier->orientation !== null) {
            throw new InvalidArgumentException('Ce dossier est déjà orienté ; il ne peut plus être réaffecté par cette voie.');
        }

        $dossier->update([
            'magistrat_id' => $magistrat->id,
            'affecte_at' => $this->horodatage->maintenant(),
        ]);

        $this->audit->consigner('parquet.affectation', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'magistrat_id' => $magistrat->id,
        ]);

        return $dossier;
    }
}
