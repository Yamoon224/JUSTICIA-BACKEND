<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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

        // Un champ oublié dans #[Fillable(...)] est sinon ignoré en silence
        // par ->update()/->create() — une donnée judiciaire perdue sans
        // erreur est précisément ce que §7 (intégrité) interdit. Détecté ici
        // en local/tests ; désactivé en production pour ne jamais faire
        // échouer une requête agent sur une régression déjà couverte par CI.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
