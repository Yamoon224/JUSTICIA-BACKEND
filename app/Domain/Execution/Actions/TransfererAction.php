<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Ecrou;
use App\Models\EtablissementPenitentiaire;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Transfert entre établissements pénitentiaires, tracé (§6.9).
 */
class TransfererAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Ecrou $ecrou, EtablissementPenitentiaire $destination, ?string $motif, User $acteur): Ecrou
    {
        if (! $ecrou->estEnDetention()) {
            throw new InvalidArgumentException('Seul un écrou en cours peut faire l\'objet d\'un transfert.');
        }

        $origineId = $ecrou->etablissement_id;

        DB::transaction(function () use ($ecrou, $origineId, $destination, $motif, $acteur) {
            $ecrou->transferts()->create([
                'etablissement_origine_id' => $origineId,
                'etablissement_destination_id' => $destination->id,
                'motif' => $motif,
                'transfere_at' => now(),
                'transfere_par' => $acteur->id,
            ]);

            $ecrou->update(['etablissement_id' => $destination->id]);
        });

        $this->audit->consigner('execution.transfert', auditable: $ecrou, acteur: $acteur, payloadSupplementaire: [
            'etablissement_origine_id' => $origineId,
            'etablissement_destination_id' => $destination->id,
        ]);

        return $ecrou->refresh();
    }
}
