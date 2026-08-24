<?php

namespace App\Domain\Contracts;

/**
 * Contrat des entités dont toute consultation ou modification doit être
 * journalisée dans le journal d'audit inviolable (§6.2, §6.10, §8).
 *
 * Distinct du modèle "activity log" classique : ce contrat force chaque
 * agrégat sensible à déclarer explicitement ce qui apparaît dans la trace,
 * plutôt que de journaliser tous les attributs par défaut.
 */
interface Auditable
{
    /**
     * Représentation minimale et non sensible de l'entité pour la trace
     * d'audit (ex. identifiant, type, ressort) — jamais l'intégralité des
     * données personnelles.
     *
     * @return array<string, mixed>
     */
    public function auditableRepresentation(): array;
}
