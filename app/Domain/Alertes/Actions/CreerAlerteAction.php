<?php

namespace App\Domain\Alertes\Actions;

use App\Domain\Alertes\Models\Alerte;
use App\Domain\Audit\AuditService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Route une alerte qualifiée (DetecterEcheancesGardeAVueAction,
 * DetecterEcheancesDetentionAction, ...) vers un destinataire humain (§6.1,
 * §6.11). Idempotente par (alertable, destinataire, niveau) : le moteur qui
 * l'appelle tourne chaque minute (GAV) ou chaque jour (détention) sur les
 * mêmes mesures tant qu'elles restent actives — sans ce garde-fou, une seule
 * mesure en dépassement inonderait son destinataire d'une alerte identique à
 * chaque exécution. Une même mesure peut en revanche générer plusieurs
 * alertes successives à mesure qu'elle s'aggrave (information puis
 * avertissement puis dépassement) : c'est un niveau différent à chaque
 * fois, donc jamais un doublon au sens de cette règle.
 */
class CreerAlerteAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Model $alertable, string $type, string $niveau, string $message, User $destinataire): ?Alerte
    {
        $dejaRoutee = Alerte::query()
            ->where('alertable_type', $alertable->getMorphClass())
            ->where('alertable_id', $alertable->getKey())
            ->where('destinataire_id', $destinataire->id)
            ->where('niveau', $niveau)
            ->exists();

        if ($dejaRoutee) {
            return null;
        }

        $alerte = Alerte::query()->create([
            'type' => $type,
            'niveau' => $niveau,
            'message' => $message,
            'alertable_type' => $alertable->getMorphClass(),
            'alertable_id' => $alertable->getKey(),
            'destinataire_id' => $destinataire->id,
        ]);

        $this->audit->consigner('alertes.creation', auditable: $alerte, payloadSupplementaire: [
            'niveau' => $niveau,
            'destinataire_id' => $destinataire->id,
        ]);

        return $alerte;
    }
}
