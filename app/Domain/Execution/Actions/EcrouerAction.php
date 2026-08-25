<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Execution\Models\Ecrou;
use App\Models\EtablissementPenitentiaire;
use App\Models\User;
use InvalidArgumentException;

/**
 * Écrou (§6.9) : registre digital, numéro unique attribué dès la mise sous
 * écrou. La détention provisoire imputée est saisie par l'agent (au vu de
 * la minute) plutôt que recalculée automatiquement — voir la migration
 * `ecrous` pour la justification.
 */
class EcrouerAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(
        DossierExecution $dossier,
        EtablissementPenitentiaire $etablissement,
        int $dureeJours,
        int $detentionProvisoireImputeeJours,
        User $acteur,
    ): Ecrou {
        if ($dossier->ecrou()->exists()) {
            throw new InvalidArgumentException('Un écrou existe déjà pour ce dossier.');
        }

        $dateEcrou = now();
        $joursRestants = max(0, $dureeJours - $detentionProvisoireImputeeJours);

        $ecrou = $dossier->ecrou()->create([
            'numero_ecrou' => $this->genererNumero($acteur),
            'personne_id' => $dossier->personne_id,
            'etablissement_id' => $etablissement->id,
            'date_ecrou' => $dateEcrou,
            'duree_jours' => $dureeJours,
            'detention_provisoire_imputee_jours' => $detentionProvisoireImputeeJours,
            'date_fin_prevue' => $dateEcrou->clone()->addDays($joursRestants),
            'statut' => 'en_detention',
            'ecroue_par' => $acteur->id,
        ]);

        $this->audit->consigner('execution.ecrou', auditable: $ecrou, acteur: $acteur, payloadSupplementaire: [
            'numero_ecrou' => $ecrou->numero_ecrou,
            'etablissement_id' => $etablissement->id,
        ]);

        return $ecrou;
    }

    /**
     * Numérotation par comptage simple (même limite qu'OuvrirAffaireAction,
     * §7 volumétrie — à remplacer par une séquence dédiée avant généralisation).
     */
    private function genererNumero(User $acteur): string
    {
        $annee = now()->year;
        $sequence = Ecrou::query()->whereYear('created_at', $annee)->count() + 1;

        return sprintf('ECR-%d-%s-%06d', $annee, $acteur->ressort_id ?? '00', $sequence);
    }
}
