<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur d'alertes des échéances de garde à vue (§6.11) : hiérarchise les
 * mesures actives selon leur proximité d'expiration — information (2h),
 * avertissement (30 min), dépassement. Les seuils par défaut ci-dessous
 * reprennent les valeurs citées en exemple au §6.1 ; un raffinement futur
 * les rendra paramétrables par type de délai plutôt que globaux.
 *
 * Se contente ici de qualifier et journaliser (`gav.alerte`) — le
 * routage effectif vers l'OPJ et le chef d'unité (email, SMS, notification
 * temps réel) est un canal technique à brancher séparément (§10.1-D) sans
 * toucher à cette classification.
 */
class DetecterEcheancesGardeAVueAction
{
    private const SEUIL_INFORMATION_HEURES = 2;

    private const SEUIL_AVERTISSEMENT_MINUTES = 30;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @return Collection<int, array{mesure: MesureGardeAVue, niveau: string}>
     */
    public function executer(): Collection
    {
        $maintenant = now();

        return MesureGardeAVue::query()
            ->whereIn('statut', ['en_cours', 'prolongee'])
            ->get()
            ->map(function (MesureGardeAVue $mesure) use ($maintenant) {
                $niveau = $this->qualifier($mesure, $maintenant);

                return $niveau ? ['mesure' => $mesure, 'niveau' => $niveau] : null;
            })
            ->filter()
            ->values()
            ->each(function (array $alerte) {
                $this->audit->consigner('gav.alerte', auditable: $alerte['mesure'], payloadSupplementaire: [
                    'niveau' => $alerte['niveau'],
                ]);
            });
    }

    private function qualifier(MesureGardeAVue $mesure, CarbonInterface $maintenant): ?string
    {
        // Arithmétique sur timestamps plutôt que Carbon::diffInX(..., false) :
        // le signe de ce dernier dépend du sens de l'appel et prête à
        // confusion — un timestamp restant négatif est sans ambiguïté.
        $secondesRestantes = $mesure->fin_prevue_at->getTimestamp() - $maintenant->getTimestamp();

        return match (true) {
            $secondesRestantes <= 0 => 'depassement',
            $secondesRestantes <= self::SEUIL_AVERTISSEMENT_MINUTES * 60 => 'avertissement',
            $secondesRestantes <= self::SEUIL_INFORMATION_HEURES * 3600 => 'information',
            default => null,
        };
    }
}
