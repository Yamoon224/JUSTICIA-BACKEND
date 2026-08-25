<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\GavActe;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Enregistre un acte durant la mesure (§6.1) : audition, examen médical,
 * entretien avocat, confrontation, repos — avec heures de début/fin.
 */
class EnregistrerActeGardeAVueAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureGardeAVue $mesure, string $type, Carbon $debut, ?Carbon $fin, ?string $notes, User $agent): GavActe
    {
        $acte = $mesure->actes()->create([
            'type' => $type,
            'debut_at' => $debut,
            'fin_at' => $fin,
            'notes' => $notes,
            'enregistre_par' => $agent->id,
        ]);

        $this->audit->consigner('gav.acte', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'type' => $type,
            'acte_id' => $acte->id,
        ]);

        return $acte;
    }
}
