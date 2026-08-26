<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\Infraction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Versement d'une nouvelle version d'infraction au référentiel légal
 * (§6.13, §11) — jamais une modification d'une entrée existante : une
 * réforme s'intègre par une ligne datée de plus, l'ancienne restant
 * consultable pour qualifier des faits antérieurs à sa date d'effet
 * (Infraction::estEnVigueur()). Le numéro de version est calculé, jamais
 * saisi : la version précédente encore ouverte (sans date de fin) voit la
 * sienne fermée la veille de l'entrée en vigueur de la nouvelle — sans quoi
 * les deux resteraient simultanément « en vigueur » pour le même code.
 */
class CreerInfractionAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{code: string, libelle: string, categorie: string, texte_reference: ?string, date_entree_vigueur: string, date_fin_vigueur: ?string}  $donnees
     */
    public function executer(array $donnees, User $administrateur): Infraction
    {
        return DB::transaction(function () use ($donnees, $administrateur) {
            $versionPrecedente = Infraction::query()
                ->where('code', $donnees['code'])
                ->whereNull('date_fin_vigueur')
                ->latest('version')
                ->first();

            $versionPrecedente?->update([
                'date_fin_vigueur' => Carbon::parse($donnees['date_entree_vigueur'])->subDay()->toDateString(),
            ]);

            $infraction = Infraction::query()->create([
                ...$donnees,
                'version' => ($versionPrecedente?->version ?? 0) + 1,
            ]);

            $this->audit->consigner('administration.infraction_creee', acteur: $administrateur, payloadSupplementaire: [
                'infraction_id' => $infraction->id,
                'code' => $infraction->code,
                'version' => $infraction->version,
            ]);

            return $infraction;
        });
    }
}
