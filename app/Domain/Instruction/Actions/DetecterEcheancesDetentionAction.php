<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Alertes\Actions\CreerAlerteAction;
use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\MesureSurete;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur d'alertes des échéances de détention provisoire (§6.6, §6.11) :
 * « une détention arrivant à échéance sans décision est signalée en
 * priorité absolue » — le niveau `depassement` domine donc toute autre
 * alerte du système (voir aussi DetecterEcheancesGardeAVueAction, dont
 * cette classe reprend la structure pour le même type de moteur appliqué
 * à un autre acte). Routée vers le juge d'instruction affecté au dossier ;
 * une mesure dont le dossier n'a pas encore de juge affecté n'a personne à
 * alerter — elle reste journaliée, sans destinataire.
 */
class DetecterEcheancesDetentionAction
{
    private const SEUIL_INFORMATION_JOURS = 15;

    private const SEUIL_AVERTISSEMENT_JOURS = 3;

    private const LIBELLES_NIVEAU = [
        'information' => 'approche de l\'échéance',
        'avertissement' => 'échéance imminente',
        'depassement' => 'échéance dépassée — priorité absolue',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly CreerAlerteAction $creerAlerte,
    ) {}

    /**
     * @return Collection<int, array{mesure: MesureSurete, niveau: string}>
     */
    public function executer(): Collection
    {
        $maintenant = now();

        return MesureSurete::query()
            ->where('type', 'detention_provisoire')
            ->where('statut', 'en_cours')
            ->with('dossierInstruction.jugeInstruction')
            ->get()
            ->map(function (MesureSurete $mesure) use ($maintenant) {
                $niveau = $this->qualifier($mesure, $maintenant);

                return $niveau ? ['mesure' => $mesure, 'niveau' => $niveau] : null;
            })
            ->filter()
            ->values()
            ->each(function (array $alerte) {
                $this->audit->consigner('instruction.alerte_detention', auditable: $alerte['mesure'], payloadSupplementaire: [
                    'niveau' => $alerte['niveau'],
                ]);

                $juge = $alerte['mesure']->dossierInstruction->jugeInstruction;
                if ($juge !== null) {
                    $message = "Détention provisoire #{$alerte['mesure']->id} — ".self::LIBELLES_NIVEAU[$alerte['niveau']];
                    $this->creerAlerte->executer($alerte['mesure'], 'detention_echeance', $alerte['niveau'], $message, $juge);
                }
            });
    }

    private function qualifier(MesureSurete $mesure, CarbonInterface $maintenant): ?string
    {
        if ($mesure->fin_prevue_at === null) {
            return null;
        }

        $secondesRestantes = $mesure->fin_prevue_at->getTimestamp() - $maintenant->getTimestamp();

        return match (true) {
            $secondesRestantes <= 0 => 'depassement',
            $secondesRestantes <= self::SEUIL_AVERTISSEMENT_JOURS * 86400 => 'avertissement',
            $secondesRestantes <= self::SEUIL_INFORMATION_JOURS * 86400 => 'information',
            default => null,
        };
    }
}
