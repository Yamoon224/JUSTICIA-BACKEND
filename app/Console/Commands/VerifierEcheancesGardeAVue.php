<?php

namespace App\Console\Commands;

use App\Domain\GardeAVue\Actions\DetecterEcheancesGardeAVueAction;
use Illuminate\Console\Command;

/**
 * Exécute le moteur d'alertes des échéances de garde à vue (§6.11).
 * Planifiée en continu (voir bootstrap/app.php) : une détention/mesure
 * arrivant à échéance sans décision doit être signalée sans délai.
 */
class VerifierEcheancesGardeAVue extends Command
{
    protected $signature = 'gav:verifier-echeances';

    protected $description = "Détecte les mesures de garde à vue proches de l'échéance ou dépassées et journalise une alerte";

    public function handle(DetecterEcheancesGardeAVueAction $action): int
    {
        $alertes = $action->executer();

        $this->info("{$alertes->count()} alerte(s) de garde à vue journalisée(s).");

        return self::SUCCESS;
    }
}
