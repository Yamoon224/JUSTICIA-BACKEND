<?php

namespace App\Domain\Parquet\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Contracts\Horodatable;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Parquet\Models\DossierParquet;
use App\Models\MotifClassement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orientation des poursuites (§6.5) : décision du magistrat, jamais
 * automatique (§3). Répercute sur l'affaire les issues qui sortent
 * définitivement du périmètre parquet à ce stade — classement sans suite
 * (§6.3, statut `affaires`) et ouverture d'information, qui ouvre aussitôt
 * le dossier d'information correspondant (§6.6, module Instruction). Les
 * autres orientations (citation directe, comparution immédiate,
 * composition, médiation, rappel à la loi) seront reliées à
 * l'audiencement en Phase 5.
 */
class OrienterAction
{
    private const ORIENTATIONS_VALIDES = [
        'classement_sans_suite',
        'rappel_a_la_loi',
        'mediation_penale',
        'composition_penale',
        'citation_directe',
        'ouverture_information',
        'comparution_immediate',
    ];

    /**
     * @var array<string, string> orientation => nouveau statut de l'affaire
     */
    private const TRANSITIONS_AFFAIRE = [
        'classement_sans_suite' => 'classee_sans_suite',
        'ouverture_information' => 'information_ouverte',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(
        DossierParquet $dossier,
        string $orientation,
        ?int $motifClassementId,
        User $acteur,
    ): DossierParquet {
        if (! in_array($orientation, self::ORIENTATIONS_VALIDES, true)) {
            throw new InvalidArgumentException("Orientation inconnue : {$orientation}.");
        }

        if ($dossier->orientation !== null) {
            throw new InvalidArgumentException('Ce dossier a déjà été orienté.');
        }

        if ($orientation === 'classement_sans_suite' && ! $motifClassementId) {
            throw new InvalidArgumentException('Un classement sans suite exige un motif (§6.5).');
        }

        if ($motifClassementId) {
            MotifClassement::query()->findOrFail($motifClassementId);
        }

        DB::transaction(function () use ($dossier, $orientation, $motifClassementId, $acteur) {
            $dossier->update([
                'orientation' => $orientation,
                'motif_classement_id' => $motifClassementId,
                'oriente_at' => $this->horodatage->maintenant(),
                'oriente_par' => $acteur->id,
            ]);

            if ($nouveauStatut = self::TRANSITIONS_AFFAIRE[$orientation] ?? null) {
                $dossier->affaire->update(['statut' => $nouveauStatut]);
            }

            if ($orientation === 'ouverture_information') {
                DossierInstruction::query()->create([
                    'affaire_id' => $dossier->affaire_id,
                    'ouvert_at' => $this->horodatage->maintenant(),
                    // Explicite plutôt que de compter sur le défaut SQL :
                    // create() ne relit pas la ligne insérée, un futur appel
                    // exposant ce dossier fraîchement créé sans requête
                    // intermédiaire aurait sinon un statut absent.
                    'statut' => 'en_cours',
                ]);
            }
        });

        $this->audit->consigner('parquet.orientation', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'orientation' => $orientation,
            'motif_classement_id' => $motifClassementId,
        ]);

        return $dossier->refresh();
    }
}
