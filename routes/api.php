<?php

use App\Http\Controllers\Api\V1\Affaires\AffaireController;
use App\Http\Controllers\Api\V1\Affaires\ProcesVerbalController;
use App\Http\Controllers\Api\V1\Affaires\ScelleController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\GardeAVue\MesureGardeAVueController;
use App\Http\Controllers\Api\V1\Instruction\ActeInstructionController;
use App\Http\Controllers\Api\V1\Instruction\DossierInstructionController;
use App\Http\Controllers\Api\V1\Instruction\MandatController;
use App\Http\Controllers\Api\V1\Instruction\MesureSureteController;
use App\Http\Controllers\Api\V1\Parquet\DossierParquetController;
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
| Restent à venir (Phases 5+) : audiencement, exécution, casier.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // §6.13 — Référentiels (lecture seule, pour les listes de choix).
        Route::get('/referentiels/infractions', [ReferentielController::class, 'infractions'])->name('referentiels.infractions');
        Route::get('/referentiels/unites', [ReferentielController::class, 'unites'])->name('referentiels.unites');
        Route::get('/referentiels/motifs-classement', [ReferentielController::class, 'motifsClassement'])->name('referentiels.motifs-classement');
        Route::get('/referentiels/magistrats', [ReferentielController::class, 'magistrats'])->name('referentiels.magistrats');
        Route::get('/referentiels/juges-instruction', [ReferentielController::class, 'jugesInstruction'])->name('referentiels.juges-instruction');

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

        // §6.5 — Parquet : bureau des arrivées et orientation des poursuites.
        Route::get('/parquet/dossiers', [DossierParquetController::class, 'index'])->name('parquet.dossiers.index');
        Route::get('/parquet/dossiers/{dossier}', [DossierParquetController::class, 'show'])->name('parquet.dossiers.show');
        Route::post('/parquet/dossiers/{dossier}/affecter', [DossierParquetController::class, 'affecter'])->name('parquet.dossiers.affecter');
        Route::post('/parquet/dossiers/{dossier}/orienter', [DossierParquetController::class, 'orienter'])->name('parquet.dossiers.orienter');
        Route::post('/parquet/dossiers/{dossier}/requisitions', [DossierParquetController::class, 'enregistrerRequisition'])->name('parquet.dossiers.requisitions.store');

        // §6.6 — Instruction : dossier d'information, actes, mandats, mesures de sûreté.
        Route::get('/instruction/dossiers', [DossierInstructionController::class, 'index'])->name('instruction.dossiers.index');
        Route::get('/instruction/dossiers/{dossier}', [DossierInstructionController::class, 'show'])->name('instruction.dossiers.show');
        Route::post('/instruction/dossiers/{dossier}/affecter', [DossierInstructionController::class, 'affecter'])->name('instruction.dossiers.affecter');
        Route::post('/instruction/dossiers/{dossier}/mise-en-examen', [DossierInstructionController::class, 'mettreEnExamen'])->name('instruction.dossiers.mise-en-examen');
        Route::post('/instruction/dossiers/{dossier}/actes', [DossierInstructionController::class, 'enregistrerActe'])->name('instruction.dossiers.actes.store');
        Route::post('/instruction/dossiers/{dossier}/mandats', [DossierInstructionController::class, 'emettreMandat'])->name('instruction.dossiers.mandats.store');
        Route::post('/instruction/dossiers/{dossier}/controle-judiciaire', [DossierInstructionController::class, 'placerSousControleJudiciaire'])->name('instruction.dossiers.controle-judiciaire');
        Route::post('/instruction/dossiers/{dossier}/detention-provisoire', [DossierInstructionController::class, 'placerEnDetentionProvisoire'])->name('instruction.dossiers.detention-provisoire');
        Route::post('/instruction/dossiers/{dossier}/ordonnance', [DossierInstructionController::class, 'rendreOrdonnance'])->name('instruction.dossiers.ordonnance');

        Route::post('/instruction/actes/{acte}/statut', [ActeInstructionController::class, 'mettreAJour'])->name('instruction.actes.statut');
        Route::post('/instruction/mandats/{mandat}/etape', [MandatController::class, 'mettreAJour'])->name('instruction.mandats.etape');
        Route::post('/instruction/mesures-surete/{mesure}/renouveler', [MesureSureteController::class, 'renouveler'])->name('instruction.mesures-surete.renouveler');
        Route::post('/instruction/mesures-surete/{mesure}/lever', [MesureSureteController::class, 'lever'])->name('instruction.mesures-surete.lever');
    });
});
