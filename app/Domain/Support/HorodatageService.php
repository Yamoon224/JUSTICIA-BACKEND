<?php

namespace App\Domain\Support;

use App\Domain\Contracts\Horodatable;
use Carbon\CarbonImmutable;

/**
 * Implémentation par défaut de l'horodatage de confiance : horloge serveur
 * (NTP synchronisé en production). Remplaçable par une implémentation
 * s'appuyant sur un service d'horodatage qualifié externe sans changer le
 * code métier, celui-ci ne dépendant que de App\Domain\Contracts\Horodatable.
 */
class HorodatageService implements Horodatable
{
    public function maintenant(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
