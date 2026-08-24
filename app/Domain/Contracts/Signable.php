<?php

namespace App\Domain\Contracts;

use App\Models\User;

/**
 * Contrat des actes de procédure signables (PV, minutes, ordonnances...).
 *
 * Un acte signé devient immuable (§6.3, §7, §8) : toute correction passe par
 * un acte rectificatif référencé, jamais par une modification de l'original.
 * Les générateurs d'actes respectent ce contrat commun et sont donc
 * interchangeables par type d'acte (§10.1-L).
 */
interface Signable
{
    public function estSigne(): bool;

    /**
     * Scelle l'acte : horodatage de confiance + identité du signataire.
     * Doit lever une exception si l'acte est déjà signé.
     */
    public function signer(User $signataire): void;
}
