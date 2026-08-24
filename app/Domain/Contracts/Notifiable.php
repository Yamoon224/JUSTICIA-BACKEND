<?php

namespace App\Domain\Contracts;

/**
 * Contrat des actes de procédure soumis à une obligation légale de
 * notification (§6.1, §6.7, §6.8, §6.11) : notification des droits en garde
 * à vue, convocations, citations, signification d'une décision...
 *
 * Distinct du contrat Notifiable d'Illuminate (canaux d'envoi techniques) :
 * celui-ci porte la sémantique procédurale — le suivi de remise conditionne
 * la régularité de la procédure et doit donc rester traçable indépendamment
 * du canal effectivement utilisé.
 */
interface Notifiable
{
    public function estNotifie(): bool;

    /**
     * Enregistre la notification effective de l'acte (destinataire, mode de
     * remise, horodatage) — indépendamment du canal technique d'envoi.
     */
    public function marquerNotifie(string $modeDeRemise): void;
}
