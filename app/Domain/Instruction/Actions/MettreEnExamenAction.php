<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use InvalidArgumentException;

/**
 * Mise en examen ou statut de témoin assisté (§6.6), avec date tracée. Le
 * statut vit sur le pivot affaire_personne (§6.2 : jamais sur la fiche
 * personne elle-même) — une nouvelle ligne est ajoutée plutôt que
 * d'écraser un statut antérieur, pour garder l'historique complet.
 */
class MettreEnExamenAction
{
    private const STATUTS_VALIDES = ['mis_en_examen', 'temoin_assiste'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierInstruction $dossier, Personne $personne, string $statut, User $acteur): void
    {
        if (! $dossier->estEnCours()) {
            throw new InvalidArgumentException('Ce dossier d\'information est clôturé.');
        }

        if (! in_array($statut, self::STATUTS_VALIDES, true)) {
            throw new InvalidArgumentException("Statut inconnu : {$statut}.");
        }

        $dossier->affaire->personnes()->attach($personne->id, [
            'statut' => $statut,
            'depuis' => now(),
        ]);

        $this->audit->consigner('instruction.mise_en_examen', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'personne_id' => $personne->id,
            'statut' => $statut,
        ]);
    }
}
