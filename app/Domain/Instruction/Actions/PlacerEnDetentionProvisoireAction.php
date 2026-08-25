<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Instruction\Models\MesureSurete;
use App\Domain\Personnes\Models\Personne;
use App\Domain\Support\ResolveurDelaiLegal;
use App\Models\User;

/**
 * Détention provisoire (§6.6) : exceptionnelle et strictement limitée dans
 * le temps — le délai maximal est résolu depuis le référentiel
 * `delais_legaux` (comme la garde à vue), jamais codé en dur.
 */
class PlacerEnDetentionProvisoireAction
{
    private const DUREE_DEFAUT_JOURS = 120;

    public function __construct(
        private readonly AuditService $audit,
        private readonly ResolveurDelaiLegal $delais,
    ) {}

    public function executer(DossierInstruction $dossier, Personne $personne, User $juge): MesureSurete
    {
        $debutAt = now();
        $dureeJours = $this->delais->dureeJours('detention_provisoire', $dossier->affaire) ?? self::DUREE_DEFAUT_JOURS;

        $mesure = $dossier->mesuresSurete()->create([
            'personne_id' => $personne->id,
            'type' => 'detention_provisoire',
            'debut_at' => $debutAt,
            'duree_jours' => $dureeJours,
            'fin_prevue_at' => $debutAt->clone()->addDays($dureeJours),
            'autorise_par' => $juge->id,
            // Explicite plutôt que de compter sur le défaut SQL : create()
            // ne relit pas la ligne insérée pour la réponse API immédiate.
            'statut' => 'en_cours',
        ]);

        $this->audit->consigner('instruction.detention_provisoire', auditable: $mesure, acteur: $juge, payloadSupplementaire: [
            'personne_id' => $personne->id,
            'duree_jours' => $dureeJours,
        ]);

        return $mesure;
    }
}
