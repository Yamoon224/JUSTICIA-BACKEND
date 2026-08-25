<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\Recours;
use App\Domain\Audit\AuditService;
use App\Domain\Contracts\Horodatable;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use InvalidArgumentException;

/**
 * Enregistrement d'un recours au greffe (§6.8) : appel, opposition, pourvoi
 * en cassation. La recevabilité est **calculée**, jamais déclarée — elle
 * dépend uniquement du délai de la décision visée, pour qu'aucun recours
 * hors délai ne puisse être enregistré comme recevable par erreur ou abus.
 */
class EnregistrerRecoursAction
{
    private const TYPES_VALIDES = ['appel', 'opposition', 'pourvoi_cassation'];

    /**
     * Effet suspensif par défaut selon le type de recours — valeurs
     * d'exemple à valider par la chancellerie avant recette (§11), le
     * pourvoi en cassation ne suspendant généralement pas l'exécution en
     * matière correctionnelle contrairement à l'appel et l'opposition.
     *
     * @var array<string, bool>
     */
    private const EFFET_SUSPENSIF_PAR_DEFAUT = [
        'appel' => true,
        'opposition' => true,
        'pourvoi_cassation' => false,
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Decision $decision, string $type, ?Personne $formePar, User $acteur): Recours
    {
        if (! in_array($type, self::TYPES_VALIDES, true)) {
            throw new InvalidArgumentException("Type de recours inconnu : {$type}.");
        }

        $formeAt = $this->horodatage->maintenant();
        $recevable = $formeAt->lessThanOrEqualTo($decision->delai_recours_expire_at);

        $recours = $decision->recours()->create([
            'type' => $type,
            'formee_par_personne_id' => $formePar?->id,
            'formee_at' => $formeAt,
            'recevable' => $recevable,
            'effet_suspensif' => $recevable && self::EFFET_SUSPENSIF_PAR_DEFAUT[$type],
            'enregistre_par' => $acteur->id,
        ]);

        $this->audit->consigner('audiencement.recours', auditable: $recours, acteur: $acteur, payloadSupplementaire: [
            'decision_id' => $decision->id,
            'type' => $type,
            'recevable' => $recevable,
        ]);

        return $recours;
    }
}
