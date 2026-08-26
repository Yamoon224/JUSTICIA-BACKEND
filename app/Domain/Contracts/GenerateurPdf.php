<?php

namespace App\Domain\Contracts;

/**
 * Contrat de génération des documents aux formats légaux (§6.1 registre,
 * §6.3 PV, §6.7 minute, §6.10 bulletin) : le cœur procédural compose une vue
 * (gabarit) et des données, sans jamais dépendre du moteur de rendu PDF
 * effectif (§10.1-D) — remplaçable par un service d'édition externe sans
 * toucher aux contrôleurs.
 */
interface GenerateurPdf
{
    /**
     * Rend la vue Blade donnée en PDF et retourne le contenu binaire.
     *
     * @param  array<string, mixed>  $donnees
     */
    public function depuisVue(string $vue, array $donnees): string;
}
