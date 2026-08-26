<?php

namespace App\Domain\Contracts;

/**
 * Contrat du stockage physique des pièces versées au dossier (§6.2, §6.3,
 * §6.4, §9) : photos, pièces d'identité, scellés numérisés. Le cœur
 * procédural (VerserDocumentAction, RecupererDocumentAction) ignore où et
 * comment le contenu est effectivement conservé (§10.1-D) — disque local
 * aujourd'hui, objet distant demain, sans toucher au métier.
 *
 * Le chiffrement du contenu est la responsabilité de l'implémentation, pas
 * du disque sous-jacent (§8 : chiffrement au repos) : même un disque non
 * chiffré nativement (S3 basique, disque local) protège le contenu tant que
 * la clé applicative reste secrète.
 */
interface StockageDocuments
{
    /**
     * Chiffre et écrit le contenu ; retourne le chemin de stockage —
     * opaque pour l'appelant, jamais reconstruit manuellement, toujours
     * repris tel quel pour une lecture ultérieure.
     */
    public function ecrire(string $contenu, string $extension): string;

    /**
     * Lit et déchiffre le contenu à l'emplacement donné.
     */
    public function lire(string $chemin): string;
}
