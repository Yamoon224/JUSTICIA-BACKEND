<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Transmission électronique du dossier au parquet (§6.3) avec bordereau des
 * pièces et accusé de réception tracé — ici matérialisée par le changement
 * de statut et l'entrée d'audit, en amont de l'implémentation complète du
 * bureau des arrivées (Phase 4, §6.5).
 */
class TransmettreAuParquetAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Affaire $affaire, User $agent): Affaire
    {
        if ($affaire->statut !== 'ouverte') {
            throw new InvalidArgumentException('Seule une affaire ouverte peut être transmise au parquet.');
        }

        $affaire->update(['statut' => 'transmise_parquet']);

        $this->audit->consigner('affaires.transmission_parquet', auditable: $affaire, acteur: $agent, payloadSupplementaire: [
            'nombre_pv' => $affaire->procesVerbaux()->count(),
            'nombre_scelles' => $affaire->scelles()->count(),
        ]);

        return $affaire;
    }
}
