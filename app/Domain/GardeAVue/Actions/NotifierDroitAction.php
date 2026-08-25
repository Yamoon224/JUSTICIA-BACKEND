<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\GavNotificationDroit;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use InvalidArgumentException;

/**
 * Notification tracée d'un droit (§6.1) : silence, avocat, médecin,
 * information d'un proche — avec horodatage, comme l'exige le cahier des
 * charges. Chaque droit n'est notifié qu'une fois par mesure.
 */
class NotifierDroitAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureGardeAVue $mesure, string $droit, string $modeDeRemise, User $agent): GavNotificationDroit
    {
        if ($mesure->notificationsDroits()->where('droit', $droit)->exists()) {
            throw new InvalidArgumentException("Le droit « {$droit} » a déjà été notifié pour cette mesure.");
        }

        $notification = $mesure->notificationsDroits()->create([
            'droit' => $droit,
            'notifie_at' => now(),
            'mode_de_remise' => $modeDeRemise,
            'enregistre_par' => $agent->id,
        ]);

        $this->audit->consigner('gav.notification_droit', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'droit' => $droit,
        ]);

        return $notification;
    }
}
