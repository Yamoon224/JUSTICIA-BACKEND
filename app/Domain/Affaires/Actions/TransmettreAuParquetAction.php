<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Domain\Contracts\Horodatable;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Transmission électronique du dossier au parquet (§6.3) avec bordereau des
 * pièces et accusé de réception tracé. Matérialise aussi la réception au
 * bureau des arrivées du parquet (§6.5) : les deux actes sont
 * indissociables dans la procédure, donc traités dans la même transaction,
 * même si la suite de l'orientation appartient au module Parquet.
 */
class TransmettreAuParquetAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Affaire $affaire, User $agent): Affaire
    {
        if ($affaire->statut !== 'ouverte') {
            throw new InvalidArgumentException('Seule une affaire ouverte peut être transmise au parquet.');
        }

        DB::transaction(function () use ($affaire) {
            $affaire->update(['statut' => 'transmise_parquet']);

            DossierParquet::query()->create([
                'affaire_id' => $affaire->id,
                'recu_at' => $this->horodatage->maintenant(),
            ]);
        });

        $this->audit->consigner('affaires.transmission_parquet', auditable: $affaire, acteur: $agent, payloadSupplementaire: [
            'nombre_pv' => $affaire->procesVerbaux()->count(),
            'nombre_scelles' => $affaire->scelles()->count(),
        ]);

        return $affaire;
    }
}
