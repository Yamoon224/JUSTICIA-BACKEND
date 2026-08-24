<?php

namespace App\Domain\Contracts;

use Carbon\CarbonImmutable;

/**
 * Contrat d'horodatage de confiance (§8, §9, §10.1-D).
 *
 * Le cœur procédural ne doit jamais appeler `now()` directement pour les
 * actes juridiquement significatifs (placement en GAV, signature d'un PV,
 * décision...) : il dépend de cette interface, injectée, afin que
 * l'implémentation technique (horloge serveur, service d'horodatage
 * qualifié RFC 3161...) reste substituable sans toucher au métier.
 */
interface Horodatable
{
    /**
     * Retourne l'horodatage de confiance courant.
     */
    public function maintenant(): CarbonImmutable;
}
