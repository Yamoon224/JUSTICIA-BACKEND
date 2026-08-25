<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Scelle;
use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Enregistre un mouvement dans la chaîne de conservation d'un scellé
 * (§6.4) : sortie pour expertise, retour, restitution, confiscation,
 * destruction. Met aussi à jour le statut courant du scellé, dérivé du
 * dernier mouvement.
 */
class EnregistrerMouvementScelleAction
{
    private const STATUT_PAR_TYPE = [
        'depot' => 'en_depot',
        'sortie_expertise' => 'sorti_expertise',
        'retour_expertise' => 'en_depot',
        'restitution' => 'restitue',
        'confiscation' => 'confisque',
        'destruction' => 'detruit',
    ];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Scelle $scelle, string $type, ?User $remettant, ?User $recepteur, ?string $motif, User $agent): void
    {
        $scelle->mouvements()->create([
            'type' => $type,
            'remettant_id' => $remettant?->id,
            'recepteur_id' => $recepteur?->id,
            'motif' => $motif,
            'horodatage' => now(),
        ]);

        $scelle->update(['statut' => self::STATUT_PAR_TYPE[$type]]);

        $this->audit->consigner('affaires.scelle.mouvement', auditable: $scelle, acteur: $agent, motif: $motif, payloadSupplementaire: [
            'type' => $type,
        ]);
    }
}
