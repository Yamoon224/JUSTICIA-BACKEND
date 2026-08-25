<?php

namespace App\Providers;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Domain\Contracts\Horodatable;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Parquet\Models\DossierParquet;
use App\Domain\Personnes\Models\Personne;
use App\Domain\Support\HorodatageService;
use App\Policies\AffairePolicy;
use App\Policies\DossierAudiencementPolicy;
use App\Policies\DossierExecutionPolicy;
use App\Policies\DossierInstructionPolicy;
use App\Policies\DossierParquetPolicy;
use App\Policies\PersonnePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Lie les contrats de domaine (§10.1-D) à leurs implémentations techniques
 * par défaut. Le cœur procédural dépend des interfaces sous
 * App\Domain\Contracts, jamais de ces implémentations directement.
 *
 * Enregistre aussi explicitement les Policies des modèles de domaine : la
 * convention de découverte automatique de Laravel (App\Models\X →
 * App\Policies\XPolicy) ne s'applique pas à nos modèles rangés sous
 * App\Domain\<Module>\Models.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Personne::class => PersonnePolicy::class,
        Affaire::class => AffairePolicy::class,
        DossierParquet::class => DossierParquetPolicy::class,
        DossierInstruction::class => DossierInstructionPolicy::class,
        DossierAudiencement::class => DossierAudiencementPolicy::class,
        DossierExecution::class => DossierExecutionPolicy::class,
    ];

    public function register(): void
    {
        $this->app->bind(Horodatable::class, HorodatageService::class);
    }

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
