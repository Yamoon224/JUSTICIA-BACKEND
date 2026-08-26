<?php

use App\Http\Controllers\Api\V1\Administration\AgentController;
use App\Http\Controllers\Api\V1\Administration\HabilitationController;
use App\Http\Controllers\Api\V1\Administration\InfractionController;
use App\Http\Controllers\Api\V1\Affaires\AffaireController;
use App\Http\Controllers\Api\V1\Affaires\ProcesVerbalController;
use App\Http\Controllers\Api\V1\Affaires\ScelleController;
use App\Http\Controllers\Api\V1\Alertes\AlerteController;
use App\Http\Controllers\Api\V1\Audiencement\DecisionController;
use App\Http\Controllers\Api\V1\Audiencement\DossierAudiencementController;
use App\Http\Controllers\Api\V1\Audiencement\RecoursController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Casier\BulletinController;
use App\Http\Controllers\Api\V1\Casier\CondamnationController;
use App\Http\Controllers\Api\V1\Casier\ConsultationController;
use App\Http\Controllers\Api\V1\Documents\DocumentController;
use App\Http\Controllers\Api\V1\Execution\AmendeController;
use App\Http\Controllers\Api\V1\Execution\DossierExecutionController;
use App\Http\Controllers\Api\V1\Execution\EcrouController;
use App\Http\Controllers\Api\V1\Execution\MiseAExecutionController;
use App\Http\Controllers\Api\V1\Execution\MiseALEpreuveController;
use App\Http\Controllers\Api\V1\Execution\TigController;
use App\Http\Controllers\Api\V1\GardeAVue\MesureGardeAVueController;
use App\Http\Controllers\Api\V1\Instruction\ActeInstructionController;
use App\Http\Controllers\Api\V1\Instruction\DossierInstructionController;
use App\Http\Controllers\Api\V1\Instruction\MandatController;
use App\Http\Controllers\Api\V1\Instruction\MesureSureteController;
use App\Http\Controllers\Api\V1\Parquet\DossierParquetController;
use App\Http\Controllers\Api\V1\Personnes\PersonneController;
use App\Http\Controllers\Api\V1\Referentiels\ReferentielController;
use App\Http\Controllers\Api\V1\Statistiques\TableauDeBordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API JUSTICIA — v1
|--------------------------------------------------------------------------
|
| Consommée exclusivement par les interfaces « Web » (NextJS). Chaque
| module métier enregistre ses routes ici sous le même préfixe versionné.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // §6.1, §6.11 — Alertes personnelles (agenda de l'agent).
        Route::get('/alertes', [AlerteController::class, 'index'])->name('alertes.index');
        Route::post('/alertes/{alerte}/lire', [AlerteController::class, 'marquerLue'])->name('alertes.lire');

        // §6.13 — Référentiels (lecture seule, pour les listes de choix).
        Route::get('/referentiels/infractions', [ReferentielController::class, 'infractions'])->name('referentiels.infractions');
        Route::get('/referentiels/unites', [ReferentielController::class, 'unites'])->name('referentiels.unites');
        Route::get('/referentiels/services', [ReferentielController::class, 'services'])->name('referentiels.services');
        Route::get('/referentiels/motifs-classement', [ReferentielController::class, 'motifsClassement'])->name('referentiels.motifs-classement');
        Route::get('/referentiels/magistrats', [ReferentielController::class, 'magistrats'])->name('referentiels.magistrats');
        Route::get('/referentiels/juges-instruction', [ReferentielController::class, 'jugesInstruction'])->name('referentiels.juges-instruction');
        Route::get('/referentiels/juges-audience', [ReferentielController::class, 'jugesAudience'])->name('referentiels.juges-audience');
        Route::get('/referentiels/greffiers', [ReferentielController::class, 'greffiers'])->name('referentiels.greffiers');
        Route::get('/referentiels/juridictions', [ReferentielController::class, 'juridictions'])->name('referentiels.juridictions');
        Route::get('/referentiels/etablissements-penitentiaires', [ReferentielController::class, 'etablissementsPenitentiaires'])->name('referentiels.etablissements-penitentiaires');
        Route::get('/referentiels/ressorts', [ReferentielController::class, 'ressorts'])->name('referentiels.ressorts');

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
        Route::get('/proces-verbaux/{procesVerbal}/pdf', [ProcesVerbalController::class, 'telechargerPdf'])->name('proces-verbaux.pdf');

        // §6.4 — Pièces à conviction et scellés.
        Route::post('/affaires/{affaire}/scelles', [ScelleController::class, 'store'])->name('scelles.store');
        Route::post('/scelles/{scelle}/mouvements', [ScelleController::class, 'enregistrerMouvement'])->name('scelles.mouvements.store');

        // §6.2, §6.3, §6.4, §9 — Pièces versées (photos, pièces d'identité,
        // pièces d'affaire cotées, photos de scellé) : stockage chiffré,
        // jamais servi directement (cf. App\Domain\Contracts\StockageDocuments).
        Route::post('/personnes/{personne}/documents', [DocumentController::class, 'storePourPersonne'])->name('personnes.documents.store');
        Route::post('/affaires/{affaire}/documents', [DocumentController::class, 'storePourAffaire'])->name('affaires.documents.store');
        Route::post('/scelles/{scelle}/documents', [DocumentController::class, 'storePourScelle'])->name('scelles.documents.store');
        Route::get('/documents/{document}', [DocumentController::class, 'telecharger'])->name('documents.show');

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

        // §6.7, §6.8 — Audiencement : enrôlement, décisions, voies de recours.
        Route::get('/audiencement/dossiers', [DossierAudiencementController::class, 'index'])->name('audiencement.dossiers.index');
        Route::get('/audiencement/dossiers/{dossier}', [DossierAudiencementController::class, 'show'])->name('audiencement.dossiers.show');
        Route::post('/audiencement/dossiers/{dossier}/enroler', [DossierAudiencementController::class, 'enroler'])->name('audiencement.dossiers.enroler');
        Route::post('/audiencement/dossiers/{dossier}/renvoyer', [DossierAudiencementController::class, 'renvoyer'])->name('audiencement.dossiers.renvoyer');
        Route::post('/audiencement/dossiers/{dossier}/decisions', [DossierAudiencementController::class, 'enregistrerDecision'])->name('audiencement.dossiers.decisions.store');

        Route::post('/audiencement/decisions/{decision}/recours', [DecisionController::class, 'enregistrerRecours'])->name('audiencement.decisions.recours.store');
        Route::post('/audiencement/recours/{recours}/decision', [RecoursController::class, 'integrerDecision'])->name('audiencement.recours.decision');

        // §6.9 — Exécution des peines et détention.
        Route::post('/execution/decisions/{decision}/mettre-a-execution', [MiseAExecutionController::class, 'mettreAExecution'])->name('execution.decisions.mettre-a-execution');

        Route::get('/execution/decisions-a-executer', [DossierExecutionController::class, 'decisionsAExecuter'])->name('execution.decisions-a-executer');
        Route::get('/execution/dossiers', [DossierExecutionController::class, 'index'])->name('execution.dossiers.index');
        Route::get('/execution/dossiers/{dossier}', [DossierExecutionController::class, 'show'])->name('execution.dossiers.show');
        Route::post('/execution/dossiers/{dossier}/ecrouer', [DossierExecutionController::class, 'ecrouer'])->name('execution.dossiers.ecrouer');
        Route::post('/execution/dossiers/{dossier}/amende', [DossierExecutionController::class, 'transmettreAmende'])->name('execution.dossiers.amende');
        Route::post('/execution/dossiers/{dossier}/tig', [DossierExecutionController::class, 'affecterTig'])->name('execution.dossiers.tig');
        Route::post('/execution/dossiers/{dossier}/mise-a-l-epreuve', [DossierExecutionController::class, 'placerSousMiseALEpreuve'])->name('execution.dossiers.mise-a-l-epreuve');

        Route::post('/execution/ecrous/{ecrou}/remise-de-peine', [EcrouController::class, 'enregistrerRemiseDePeine'])->name('execution.ecrous.remise-de-peine');
        Route::post('/execution/ecrous/{ecrou}/liberer', [EcrouController::class, 'liberer'])->name('execution.ecrous.liberer');
        Route::post('/execution/ecrous/{ecrou}/transferer', [EcrouController::class, 'transferer'])->name('execution.ecrous.transferer');
        Route::post('/execution/ecrous/{ecrou}/amenagement', [EcrouController::class, 'decideAmenagement'])->name('execution.ecrous.amenagement');

        Route::post('/execution/amendes/{amende}/recouvree', [AmendeController::class, 'marquerRecouvree'])->name('execution.amendes.recouvree');
        Route::post('/execution/tig/{tig}/heures', [TigController::class, 'enregistrerHeures'])->name('execution.tig.heures');
        Route::post('/execution/mises-a-l-epreuve/{mise}/lever', [MiseALEpreuveController::class, 'lever'])->name('execution.mises-a-l-epreuve.lever');

        // §6.10 — Casier judiciaire. Registre national : pas de préfixe de
        // ressort dans ces routes (voir App\Policies\CondamnationPolicy).
        Route::get('/casier/personnes/{personne}/condamnations', [CondamnationController::class, 'index'])->name('casier.personnes.condamnations');
        Route::get('/casier/personnes/{personne}/bulletin', [BulletinController::class, 'generer'])->name('casier.personnes.bulletin');
        Route::get('/casier/personnes/{personne}/bulletin/pdf', [BulletinController::class, 'telechargerPdf'])->name('casier.personnes.bulletin.pdf');
        Route::get('/casier/personnes/{personne}/consultations', [ConsultationController::class, 'index'])->name('casier.personnes.consultations');
        Route::post('/casier/condamnations/{condamnation}/rehabiliter', [CondamnationController::class, 'rehabiliter'])->name('casier.condamnations.rehabiliter');
        Route::post('/casier/condamnations/{condamnation}/amnistier', [CondamnationController::class, 'amnistier'])->name('casier.condamnations.amnistier');

        // §6.11, §6.12 — Statistiques et pilotage.
        Route::get('/statistiques/tableau-de-bord', [TableauDeBordController::class, 'afficher'])->name('statistiques.tableau-de-bord');

        // §6.13 — Administration et habilitations.
        Route::get('/administration/agents', [AgentController::class, 'index'])->name('administration.agents.index');
        Route::post('/administration/agents', [AgentController::class, 'store'])->name('administration.agents.store');
        Route::post('/administration/agents/{agent}/valider', [AgentController::class, 'valider'])->name('administration.agents.valider');
        Route::post('/administration/agents/{agent}/suspendre', [AgentController::class, 'suspendre'])->name('administration.agents.suspendre');
        Route::post('/administration/agents/{agent}/reactiver', [AgentController::class, 'reactiver'])->name('administration.agents.reactiver');

        Route::get('/administration/roles', [HabilitationController::class, 'roles'])->name('administration.roles.index');
        Route::post('/administration/agents/{agent}/roles', [HabilitationController::class, 'assigner'])->name('administration.agents.roles');

        Route::post('/administration/infractions', [InfractionController::class, 'store'])->name('administration.infractions.store');
    });
});
