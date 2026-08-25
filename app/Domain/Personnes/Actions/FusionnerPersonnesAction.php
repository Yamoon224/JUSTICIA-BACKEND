<?php

namespace App\Domain\Personnes\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fusionne deux fiches détectées comme doublons (§6.2) : le rapprochement
 * est toujours proposé, jamais automatique — cette action matérialise la
 * validation explicite d'un OPJ. La fiche absorbée n'est jamais supprimée
 * (§7 intégrité) : elle est marquée `fusionnee_dans_id` et ses
 * rattachements aux affaires sont repris par la fiche conservée.
 */
class FusionnerPersonnesAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Personne $conservee, Personne $absorbee, User $agent): Personne
    {
        if ($conservee->is($absorbee)) {
            throw new InvalidArgumentException('Une fiche ne peut pas être fusionnée avec elle-même.');
        }

        DB::transaction(function () use ($conservee, $absorbee) {
            foreach ($absorbee->affaires()->withPivot('statut', 'depuis')->get() as $affaire) {
                $conservee->affaires()->syncWithoutDetaching([
                    $affaire->id => [
                        'statut' => $affaire->pivot->statut,
                        'depuis' => $affaire->pivot->depuis,
                    ],
                ]);
            }

            $absorbee->piecesIdentite()->update(['personne_id' => $conservee->id]);
            $absorbee->update(['fusionnee_dans_id' => $conservee->id]);
        });

        $this->audit->consigner('personnes.fusion', auditable: $conservee, acteur: $agent, payloadSupplementaire: [
            'personne_absorbee_id' => $absorbee->id,
            'personne_absorbee_identifiant_unique' => $absorbee->identifiant_unique,
        ]);

        return $conservee->refresh();
    }
}
