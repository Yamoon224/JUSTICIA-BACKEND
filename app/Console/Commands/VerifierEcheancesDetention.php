<?php

namespace App\Console\Commands;

use App\Domain\Instruction\Actions\DetecterEcheancesDetentionAction;
use Illuminate\Console\Command;

/**
 * Exécute le moteur d'alertes des échéances de détention provisoire (§6.6,
 * §6.11). Manquait jusqu'ici à la planification (bootstrap/app.php) alors
 * que l'Action existait déjà : la détection n'était donc jamais exécutée en
 * dehors des tests.
 */
class VerifierEcheancesDetention extends Command
{
    protected $signature = 'instruction:verifier-echeances-detention';

    protected $description = 'Détecte les détentions provisoires proches de l\'échéance ou dépassées et alerte le juge affecté';

    public function handle(DetecterEcheancesDetentionAction $action): int
    {
        $alertes = $action->executer();

        $this->info("{$alertes->count()} alerte(s) de détention provisoire journalisée(s).");

        return self::SUCCESS;
    }
}
