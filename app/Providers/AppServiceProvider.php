<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API JUSTICIA : chaque endpoint compose explicitement la forme de
        // sa réponse (§10.1) ; l'enveloppe "data" automatique des resources
        // n'ajoute rien et casse la prévisibilité du contrat côté frontend.
        JsonResource::withoutWrapping();
    }
}
