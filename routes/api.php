<?php

use App\Http\Controllers\Api\V1\Affaires\AffaireController;
use App\Http\Controllers\Api\V1\Affaires\ProcesVerbalController;
use App\Http\Controllers\Api\V1\Affaires\ScelleController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\GardeAVue\MesureGardeAVueController;
use App\Http\Controllers\Api\V1\Personnes\PersonneController;
use App\Http\Controllers\Api\V1\Referentiels\ReferentielController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API JUSTICIA — v1
|--------------------------------------------------------------------------
|
| Consommée exclusivement par les interfaces « Web » (NextJS). Chaque
| module métier enregistre ses routes ici sous le même préfixe versionné.
| Restent à venir (Phases 4+) : parquet, instruction, audiencement,
| exécution, casier.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // §6.13 — Référentiels (lecture seule, pour les listes de choix).
        Route::get('/referentiels/infractions', [ReferentielController::class, 'infractions'])->name('referentiels.infractions');
        Route::get('/referentiels/unites', [ReferentielController::class, 'unites'])->name('referentiels.unites');

        // §6.2 — Identification des personnes.
        Route::get('/personnes', [PersonneController::class, 'index'])->name('personnes.index');
        Route::post('/personnes', [PersonneController::class, 'store'])->name('personnes.store');
        Route::get('/personnes/{personne}', [PersonneController::class, 'show'])->name('personnes.show');
        Route::post('/personnes/{personne}/fusionner', [PersonneController::class, 'fusionner'])->name('personnes.fusionner');

        // §6.3 — Dossier d'affaire et procès-verbaux.
        Route::get('/affaires', [AffaireController::class, 'index'])->name('affaires.index');
        Route::post('/affaires', [AffaireController::class, 'store'])->name('affaires.store');
        Route::get('/affaires/{affaire}', [AffaireController::class, 'show'])->name('affaires.show');
        Route::post('/affaires/{affaire}/personnes', [AffaireController::class, 'rattacherPersonne'])->name('affaires.rattacher-personne');
        Route::post('/affaires/{affaire}/transmettre-parquet', [AffaireController::class, 'transmettreAuParquet'])->name('affaires.transmettre-parquet');

        Route::post('/affaires/{affaire}/proces-verbaux', [ProcesVerbalController::class, 'store'])->name('proces-verbaux.store');
        Route::get('/proces-verbaux/{procesVerbal}', [ProcesVerbalController::class, 'show'])->name('proces-verbaux.show');
        Route::post('/proces-verbaux/{procesVerbal}/signer', [ProcesVerbalController::class, 'signer'])->name('proces-verbaux.signer');
        Route::post('/proces-verbaux/{procesVerbal}/rectifier', [ProcesVerbalController::class, 'rectifier'])->name('proces-verbaux.rectifier');

        // §6.4 — Pièces à conviction et scellés.
        Route::post('/affaires/{affaire}/scelles', [ScelleController::class, 'store'])->name('scelles.store');
        Route::post('/scelles/{scelle}/mouvements', [ScelleController::class, 'enregistrerMouvement'])->name('scelles.mouvements.store');

        // §6.1 — Interpellation et garde à vue.
        Route::post('/gav/mesures', [MesureGardeAVueController::class, 'store'])->name('gav.mesures.store');
        Route::get('/gav/mesures/{mesure}', [MesureGardeAVueController::class, 'show'])->name('gav.mesures.show');
        Route::post('/gav/mesures/{mesure}/prolonger', [MesureGardeAVueController::class, 'prolonger'])->name('gav.mesures.prolonger');
        Route::post('/gav/mesures/{mesure}/droits', [MesureGardeAVueController::class, 'notifierDroit'])->name('gav.mesures.droits.store');
        Route::post('/gav/mesures/{mesure}/avis-representant-legal', [MesureGardeAVueController::class, 'aviserRepresentantLegal'])->name('gav.mesures.avis-representant-legal');
        Route::post('/gav/mesures/{mesure}/actes', [MesureGardeAVueController::class, 'enregistrerActe'])->name('gav.mesures.actes.store');
        Route::post('/gav/mesures/{mesure}/cloturer', [MesureGardeAVueController::class, 'cloturer'])->name('gav.mesures.cloturer');
    });
});
