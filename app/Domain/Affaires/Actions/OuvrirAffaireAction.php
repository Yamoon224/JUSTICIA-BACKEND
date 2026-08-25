<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Ouvre un nouveau dossier d'affaire (§6.3) avec un numéro unique attribué
 * dès l'origine et conservé tout au long de la chaîne. Le ressort de
 * l'affaire est celui de l'agent qui l'ouvre — c'est ce qui la rend
 * visible/invisible ensuite via App\Policies\AffairePolicy (§8).
 */
class OuvrirAffaireAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributs
     */
    public function executer(array $attributs, User $agent): Affaire
    {
        $affaire = Affaire::query()->create([
            ...$attributs,
            'numero_affaire' => $this->genererNumero($agent),
            'ressort_id' => $agent->ressort_id,
            // Le référentiel des unités (police/gendarmerie) est distinct de
            // celui des services (App\Models\Service) : pas de repli implicite
            // possible depuis $agent->service_id, l'unité doit être précisée
            // explicitement si elle est connue.
            'unite_id' => $attributs['unite_id'] ?? null,
            'statut' => 'ouverte',
            'date_ouverture' => $attributs['date_ouverture'] ?? now()->toDateString(),
            'created_by' => $agent->id,
        ]);

        $this->audit->consigner('affaires.ouverture', auditable: $affaire, acteur: $agent);

        return $affaire;
    }

    /**
     * Numérotation par comptage simple, suffisante pour le socle. Sous forte
     * concurrence (plusieurs unités ouvrant des affaires simultanément), une
     * séquence dédiée par ressort (table ou SELECT ... FOR UPDATE) devra la
     * remplacer avant la généralisation (§7 volumétrie).
     */
    private function genererNumero(User $agent): string
    {
        $annee = now()->year;
        $sequence = Affaire::query()->whereYear('created_at', $annee)->count() + 1;

        return sprintf('AFF-%d-%s-%06d', $annee, $agent->ressort_id ?? '00', $sequence);
    }
}
