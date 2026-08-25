<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Audit\AuditService;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Renvoi d'audience motivé, avec nouvelle date immédiate (§6.7). Le renvoi
 * est tracé (jamais une correction silencieuse de la date) et le dossier
 * reste enrôlé — seule la date change.
 */
class RenvoyerAudienceAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierAudiencement $dossier, Carbon $nouvelleDate, string $motif, User $acteur): DossierAudiencement
    {
        if (! $dossier->estEnrole()) {
            throw new InvalidArgumentException('Seule une affaire enrôlée peut être renvoyée.');
        }

        $ancienneDate = $dossier->date_audience;

        $dossier->renvois()->create([
            'ancienne_date_audience' => $ancienneDate,
            'nouvelle_date_audience' => $nouvelleDate,
            'motif' => $motif,
            'decide_par' => $acteur->id,
        ]);

        $dossier->update(['date_audience' => $nouvelleDate]);

        $this->audit->consigner('audiencement.renvoi', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'ancienne_date' => $ancienneDate?->toIso8601String(),
            'nouvelle_date' => $nouvelleDate->toIso8601String(),
            'motif' => $motif,
        ]);

        return $dossier->refresh();
    }
}
