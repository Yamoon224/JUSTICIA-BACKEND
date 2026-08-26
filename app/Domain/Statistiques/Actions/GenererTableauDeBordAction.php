<?php

namespace App\Domain\Statistiques\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Execution\Models\Ecrou;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Instruction\Models\MesureSurete;
use App\Domain\Parquet\Models\DossierParquet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tableau de bord agrégé (§6.11, §6.12) : uniquement des lectures dérivées
 * des tables existantes, aucune écriture, aucun état persisté propre à ce
 * module — un simple instantané calculé à la demande. Le frontend n'y
 * décide jamais rien (§10.2), il affiche des chiffres déjà calculés ici.
 *
 * Le casier judiciaire (§6.10) est un registre national sans colonne
 * ressort_id (décision assumée en Phase 7) : ses compteurs restent donc
 * toujours nationaux, même dans la vue d'un ressort particulier — signalé
 * explicitement dans la réponse plutôt que silencieusement incohérent.
 */
class GenererTableauDeBordAction
{
    /**
     * @return array<string, mixed>
     */
    public function executer(?int $ressortId): array
    {
        return [
            'ressort_id' => $ressortId,
            'affaires' => $this->statsAffaires($ressortId),
            'garde_a_vue' => $this->statsGardeAVue($ressortId),
            'parquet' => $this->statsParquet($ressortId),
            'instruction' => $this->statsInstruction($ressortId),
            'audiencement' => $this->statsAudiencement($ressortId),
            'execution' => $this->statsExecution($ressortId),
            'casier' => $this->statsCasier(),
            'delais_moyens_jours' => $this->delaisMoyens($ressortId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsAffaires(?int $ressortId): array
    {
        $base = Affaire::query()->when($ressortId, fn ($q) => $q->where('ressort_id', $ressortId));

        return [
            'total' => (clone $base)->count(),
            'par_statut' => (clone $base)->selectRaw('statut, count(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsGardeAVue(?int $ressortId): array
    {
        $base = MesureGardeAVue::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        return [
            'en_cours' => (clone $base)->where('statut', '!=', 'terminee')->count(),
            'echeances_depassees' => (clone $base)->where('statut', '!=', 'terminee')->where('fin_prevue_at', '<', now())->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsParquet(?int $ressortId): array
    {
        $base = DossierParquet::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        return [
            'en_attente_orientation' => (clone $base)->whereNull('orientation')->count(),
            'orientations_par_type' => (clone $base)->whereNotNull('orientation')
                ->selectRaw('orientation, count(*) as total')
                ->groupBy('orientation')
                ->pluck('total', 'orientation'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsInstruction(?int $ressortId): array
    {
        $base = DossierInstruction::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        $detention = MesureSurete::query()
            ->where('type', 'detention_provisoire')
            ->when($ressortId, fn ($q) => $q->whereHas('dossierInstruction.affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        return [
            'dossiers_ouverts' => (clone $base)->where('statut', 'en_cours')->count(),
            'detention_provisoire_en_cours' => (clone $detention)->where('statut', 'en_cours')->count(),
            'detention_provisoire_echeances_depassees' => (clone $detention)->where('statut', 'en_cours')->where('fin_prevue_at', '<', now())->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsAudiencement(?int $ressortId): array
    {
        $base = DossierAudiencement::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        return [
            'a_enroler' => (clone $base)->where('statut', 'a_enroler')->count(),
            'enrole' => (clone $base)->where('statut', 'enrole')->count(),
            'jugee' => (clone $base)->where('statut', 'jugee')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsExecution(?int $ressortId): array
    {
        $base = DossierExecution::query()
            ->when($ressortId, fn ($q) => $q->whereHas('decision.dossierAudiencement.affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        $ecrous = Ecrou::query()
            ->when($ressortId, fn ($q) => $q->whereHas('dossierExecution.decision.dossierAudiencement.affaire', fn ($a) => $a->where('ressort_id', $ressortId)));

        return [
            'dossiers_en_cours' => (clone $base)->where('statut', 'en_cours')->count(),
            'ecroues_en_detention' => (clone $ecrous)->where('statut', 'en_detention')->count(),
            'echeances_liberation_depassees' => (clone $ecrous)->where('statut', 'en_detention')->where('date_fin_prevue', '<', now())->count(),
        ];
    }

    /**
     * Registre national (§6.10) : jamais filtré par ressort, y compris
     * lorsque le tableau de bord affiché l'est pour un ressort particulier.
     *
     * @return array<string, mixed>
     */
    private function statsCasier(): array
    {
        return [
            'total' => Condamnation::query()->count(),
            'actives' => Condamnation::query()->where('statut', 'active')->count(),
            'rehabilitees' => Condamnation::query()->where('statut', 'rehabilitee')->count(),
            'amnistiees' => Condamnation::query()->where('statut', 'amnistiee')->count(),
        ];
    }

    /**
     * Délais moyens de traitement (§6.11), calculés en PHP plutôt qu'en SQL
     * (DATEDIFF n'est pas portable entre MySQL et SQLite, ce dernier servant
     * de moteur de test) — acceptable à l'échelle d'un socle, à revoir si le
     * volume de dossiers clos devient significatif.
     *
     * @return array<string, float|null>
     */
    private function delaisMoyens(?int $ressortId): array
    {
        $gardeAVue = MesureGardeAVue::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)))
            ->where('statut', 'terminee')
            ->whereNotNull('fin_reelle_at')
            ->get(['debut_at', 'fin_reelle_at']);

        $instruction = DossierInstruction::query()
            ->when($ressortId, fn ($q) => $q->whereHas('affaire', fn ($a) => $a->where('ressort_id', $ressortId)))
            ->whereNotNull('ordonnance_at')
            ->get(['ouvert_at', 'ordonnance_at']);

        $jugement = Decision::query()
            ->whereHas('dossierAudiencement', fn ($q) => $q->when($ressortId, fn ($a) => $a->whereHas('affaire', fn ($aa) => $aa->where('ressort_id', $ressortId))))
            ->with('dossierAudiencement:id,cree_at')
            ->get(['id', 'dossier_audiencement_id', 'rendue_at']);

        return [
            'garde_a_vue_heures' => $this->moyenneEnHeures($gardeAVue->map(fn ($m) => [$m->debut_at, $m->fin_reelle_at])),
            'instruction_jours' => $this->moyenneEnJours($instruction->map(fn ($d) => [$d->ouvert_at, $d->ordonnance_at])),
            'jugement_jours' => $this->moyenneEnJours($jugement->map(fn ($d) => [$d->dossierAudiencement->cree_at, $d->rendue_at])),
        ];
    }

    /**
     * @param  Collection<int, array{0: Carbon, 1: Carbon}>  $paires
     */
    private function moyenneEnHeures(Collection $paires): ?float
    {
        return $this->moyenne($paires, fn ($debut, $fin) => $debut->diffInHours($fin));
    }

    /**
     * @param  Collection<int, array{0: Carbon, 1: Carbon}>  $paires
     */
    private function moyenneEnJours(Collection $paires): ?float
    {
        return $this->moyenne($paires, fn ($debut, $fin) => $debut->diffInDays($fin));
    }

    /**
     * @param  Collection<int, array{0: Carbon, 1: Carbon}>  $paires
     */
    private function moyenne(Collection $paires, callable $diff): ?float
    {
        if ($paires->isEmpty()) {
            return null;
        }

        return round($paires->avg(fn ($paire) => $diff($paire[0], $paire[1])), 1);
    }
}
