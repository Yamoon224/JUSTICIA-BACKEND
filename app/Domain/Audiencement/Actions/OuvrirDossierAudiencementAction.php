<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Contracts\Horodatable;

/**
 * Ouvre le dossier d'audiencement (§6.7) dès qu'une affaire atteint le
 * statut `audiencee` — appelée aussi bien par le parquet (citation directe,
 * comparution immédiate, §6.5) que par l'instruction (renvoi, §6.6), d'où
 * son extraction en action partagée plutôt que dupliquée dans les deux
 * modules appelants.
 */
class OuvrirDossierAudiencementAction
{
    public function __construct(
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Affaire $affaire): DossierAudiencement
    {
        return DossierAudiencement::query()->create([
            'affaire_id' => $affaire->id,
            'statut' => 'a_enroler',
            'cree_at' => $this->horodatage->maintenant(),
        ]);
    }
}
