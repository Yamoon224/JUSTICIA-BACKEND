<?php

namespace App\Domain\Support;

use App\Domain\Affaires\Models\Affaire;
use App\Models\DelaiLegal;

/**
 * Résolution des délais légaux paramétrés (§6.11, §9) depuis le
 * référentiel `delais_legaux`, selon la catégorie la plus grave des
 * infractions retenues sur l'affaire — jamais codée en dur dans une Action
 * métier, pour que les réformes s'intègrent par paramétrage (§11).
 * Partagé entre les modules GardeAVue et Instruction, qui appliquent tous
 * deux ce même principe à des types d'actes différents.
 */
class ResolveurDelaiLegal
{
    /**
     * Ordre de gravité décroissant — le plus sévère détermine le délai.
     *
     * @var list<string>
     */
    private const ORDRE_GRAVITE = ['crime', 'delit', 'contravention'];

    public function dureeHeures(string $typeActe, Affaire $affaire): ?int
    {
        return $this->trouver($typeActe, $affaire)?->duree_heures;
    }

    public function dureeJours(string $typeActe, Affaire $affaire): ?int
    {
        return $this->trouver($typeActe, $affaire)?->duree_jours;
    }

    private function trouver(string $typeActe, Affaire $affaire): ?DelaiLegal
    {
        $categories = $affaire->infractions()->pluck('categorie');
        $categorie = collect(self::ORDRE_GRAVITE)->first(fn (string $c) => $categories->contains($c));

        $today = now()->toDateString();

        // whereDate() plutôt que where() : une colonne au cast 'date' est
        // néanmoins écrite avec l'heure par Eloquent — sans troncature
        // explicite côté SQL, une comparaison texte peut échouer selon le
        // pilote (SQLite ne tronque pas silencieusement comme MySQL).
        $enVigueur = fn ($query) => $query
            ->where('type_acte', $typeActe)
            ->whereDate('date_entree_vigueur', '<=', $today)
            ->where(fn ($q) => $q->whereNull('date_fin_vigueur')->orWhereDate('date_fin_vigueur', '>=', $today));

        // Certains délais dépendent de la catégorie de l'infraction la plus
        // grave (garde à vue, détention provisoire) ; d'autres s'appliquent
        // uniformément (délai de recours) et sont paramétrés sans catégorie
        // — le premier type prime s'il existe, le second sert de repli.
        return DelaiLegal::query()->tap($enVigueur)->where('categorie_infraction', $categorie)->first()
            ?? DelaiLegal::query()->tap($enVigueur)->whereNull('categorie_infraction')->first();
    }
}
