<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Audit\AuditService;
use App\Models\Juridiction;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Enrôlement d'une affaire (§6.7) : juridiction, chambre, date d'audience
 * et composition (président, greffier) — un acte du greffe, jamais
 * automatique, même si le dossier a été ouvert automatiquement à l'entrée
 * dans le périmètre audiencement.
 */
class EnrolerAffaireAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(
        DossierAudiencement $dossier,
        Juridiction $juridiction,
        string $chambre,
        Carbon $dateAudience,
        User $president,
        User $greffier,
        User $acteur,
    ): DossierAudiencement {
        if ($dossier->estEnrole()) {
            throw new InvalidArgumentException('Cette affaire est déjà enrôlée.');
        }

        $dossier->update([
            'juridiction_id' => $juridiction->id,
            'chambre' => $chambre,
            'date_audience' => $dateAudience,
            'president_id' => $president->id,
            'greffier_id' => $greffier->id,
            'statut' => 'enrole',
        ]);

        $this->audit->consigner('audiencement.enrolement', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'juridiction_id' => $juridiction->id,
            'chambre' => $chambre,
            'date_audience' => $dateAudience->toIso8601String(),
        ]);

        return $dossier->refresh();
    }
}
