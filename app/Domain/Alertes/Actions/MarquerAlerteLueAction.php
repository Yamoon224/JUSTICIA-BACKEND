<?php

namespace App\Domain\Alertes\Actions;

use App\Domain\Alertes\Models\Alerte;
use InvalidArgumentException;

/**
 * Accusé de lecture d'une alerte (§6.11 : agenda personnel de l'agent). Ne
 * touche jamais au fait signalé (mesure toujours en dépassement, par
 * exemple) — seulement à la prise de connaissance par le destinataire.
 */
class MarquerAlerteLueAction
{
    public function executer(Alerte $alerte): Alerte
    {
        if ($alerte->estLue()) {
            throw new InvalidArgumentException('Cette alerte est déjà marquée comme lue.');
        }

        $alerte->update(['lue_at' => now()]);

        return $alerte->refresh();
    }
}
