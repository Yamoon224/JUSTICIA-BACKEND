<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API JUSTICIA — v1
|--------------------------------------------------------------------------
|
| Consommée exclusivement par les interfaces « Web » (NextJS). Chaque
| module métier (garde-à-vue, personnes, affaires, parquet, instruction,
| audiencement, exécution, casier...) enregistrera ses routes ici au fil
| des phases, sous le même préfixe versionné.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});
