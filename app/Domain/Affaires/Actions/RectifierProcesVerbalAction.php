<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\ProcesVerbal;
use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Corrige un PV déjà signé (§6.3) en créant un PV rectificatif référencé —
 * l'original signé n'est jamais modifié ni supprimé (§7 intégrité).
 */
class RectifierProcesVerbalAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(ProcesVerbal $original, string $contenu, User $agent): ProcesVerbal
    {
        if (! $original->estSigne()) {
            throw new InvalidArgumentException('Seul un procès-verbal déjà signé peut faire l\'objet d\'un rectificatif.');
        }

        $rectificatif = ProcesVerbal::query()->create([
            'affaire_id' => $original->affaire_id,
            'cote' => "{$original->cote}-RECT",
            'type' => 'rectificatif',
            'rectifie_par_pv_id' => $original->id,
            'contenu' => $contenu,
            'redige_par' => $agent->id,
        ]);

        $this->audit->consigner('affaires.pv.rectification', auditable: $rectificatif, acteur: $agent, payloadSupplementaire: [
            'pv_original_id' => $original->id,
        ]);

        return $rectificatif;
    }
}
