<?php

namespace App\Providers;

use App\Domain\Contracts\Horodatable;
use App\Domain\Support\HorodatageService;
use Illuminate\Support\ServiceProvider;

/**
 * Lie les contrats de domaine (§10.1-D) à leurs implémentations techniques
 * par défaut. Le cœur procédural dépend des interfaces sous
 * App\Domain\Contracts, jamais de ces implémentations directement.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Horodatable::class, HorodatageService::class);
    }
}
