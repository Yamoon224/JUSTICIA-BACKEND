<?php

namespace App\Console\Commands;

use App\Domain\Casier\Actions\DetecterRehabilitationsDePleinDroitAction;
use Illuminate\Console\Command;

/**
 * Exécute le constat des réhabilitations de plein droit (§6.10, §6.11).
 * Planifiée quotidiennement (voir bootstrap/app.php) : contrairement aux
 * échéances de garde à vue ou de détention, rien ici n'exige une
 * vérification à la minute près.
 */
class VerifierRehabilitationsPleinDroit extends Command
{
    protected $signature = 'casier:verifier-rehabilitations';

    protected $description = 'Constate les réhabilitations de plein droit des condamnations dont le délai légal est écoulé';

    public function handle(DetecterRehabilitationsDePleinDroitAction $action): int
    {
        $rehabilitations = $action->executer();

        $this->info("{$rehabilitations->count()} réhabilitation(s) de plein droit constatée(s).");

        return self::SUCCESS;
    }
}
