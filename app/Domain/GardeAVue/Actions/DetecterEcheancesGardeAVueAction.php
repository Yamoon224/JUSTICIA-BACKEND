<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Alertes\Actions\CreerAlerteAction;
use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Moteur d'alertes des échéances de garde à vue (§6.11) : hiérarchise les
 * mesures actives selon leur proximité d'expiration — information (2h),
 * avertissement (30 min), dépassement. Les seuils par défaut ci-dessous
 * reprennent les valeurs citées en exemple au §6.1 ; un raffinement futur
 * les rendra paramétrables par type de délai plutôt que globaux.
 *
 * Journalise (`gav.alerte`) et route désormais effectivement vers l'OPJ
 * ayant placé la mesure et le(s) chef(s) d'unité du même ressort (§6.1 :
 * « à l'OPJ et au chef d'unité »), via CreerAlerteAction — le canal
 * technique (ici une alerte en base, consultable par l'agent) reste
 * substituable (§10.1-D) sans toucher à cette classification.
 */
class DetecterEcheancesGardeAVueAction
{
    private const SEUIL_INFORMATION_HEURES = 2;

    private const SEUIL_AVERTISSEMENT_MINUTES = 30;

    private const LIBELLES_NIVEAU = [
        'information' => 'approche de l\'échéance',
        'avertissement' => 'échéance imminente',
        'depassement' => 'échéance dépassée',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly CreerAlerteAction $creerAlerte,
    ) {}

    /**
     * @return Collection<int, array{mesure: MesureGardeAVue, niveau: string}>
     */
    public function executer(): Collection
    {
        $maintenant = now();

        return MesureGardeAVue::query()
            ->whereIn('statut', ['en_cours', 'prolongee'])
            ->with('affaire')
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

                $this->routerVersDestinataires($alerte['mesure'], $alerte['niveau']);
            });
    }

    private function routerVersDestinataires(MesureGardeAVue $mesure, string $niveau): void
    {
        $message = "Garde à vue #{$mesure->id} — ".self::LIBELLES_NIVEAU[$niveau];

        foreach ($this->destinataires($mesure) as $destinataire) {
            $this->creerAlerte->executer($mesure, 'gav_echeance', $niveau, $message, $destinataire);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function destinataires(MesureGardeAVue $mesure): Collection
    {
        $chefsUnite = User::query()
            ->role('chef_unite')
            ->where('ressort_id', $mesure->affaire->ressort_id)
            ->get();

        return $mesure->creePar
            ? $chefsUnite->push($mesure->creePar)->unique('id')
            : $chefsUnite;
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
