<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Ecrou;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur d'alertes des échéances de libération (§6.9, §6.11) : « aucune
 * détention au-delà du titre sans signalement immédiat » — même structure
 * que les moteurs d'alertes GAV et détention provisoire
 * (DetecterEcheancesGardeAVueAction, DetecterEcheancesDetentionAction),
 * appliquée ici à l'écrou.
 */
class DetecterEcheancesLiberationAction
{
    private const SEUIL_INFORMATION_JOURS = 15;

    private const SEUIL_AVERTISSEMENT_JOURS = 3;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @return Collection<int, array{ecrou: Ecrou, niveau: string}>
     */
    public function executer(): Collection
    {
        $maintenant = now();

        return Ecrou::query()
            ->where('statut', 'en_detention')
            ->get()
            ->map(function (Ecrou $ecrou) use ($maintenant) {
                $niveau = $this->qualifier($ecrou, $maintenant);

                return $niveau ? ['ecrou' => $ecrou, 'niveau' => $niveau] : null;
            })
            ->filter()
            ->values()
            ->each(function (array $alerte) {
                $this->audit->consigner('execution.alerte_liberation', auditable: $alerte['ecrou'], payloadSupplementaire: [
                    'niveau' => $alerte['niveau'],
                ]);
            });
    }

    private function qualifier(Ecrou $ecrou, CarbonInterface $maintenant): ?string
    {
        $secondesRestantes = $ecrou->date_fin_prevue->getTimestamp() - $maintenant->getTimestamp();

        return match (true) {
            $secondesRestantes <= 0 => 'depassement',
            $secondesRestantes <= self::SEUIL_AVERTISSEMENT_JOURS * 86400 => 'avertissement',
            $secondesRestantes <= self::SEUIL_INFORMATION_JOURS * 86400 => 'information',
            default => null,
        };
    }
}
