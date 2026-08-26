<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assouplit la contrainte unique (dossier_audiencement_id, personne_id)
 * posée par 2026_08_28_100002_create_decisions_table. Le parcours de
 * recette (§14) « appel avec infirmation → mise à jour exécution/casier »
 * exige qu'une juridiction saisie sur recours puisse rendre une nouvelle
 * décision sur le même dossier pour la même personne
 * (IntegrerDecisionRecoursAction) — ce que l'unicité stricte interdisait.
 *
 * La protection contre un doublon accidentel (même décision soumise deux
 * fois sans recours entre les deux) n'est plus assurée par la base : elle
 * est reportée sur EnregistrerDecisionAction, qui n'autorise une nouvelle
 * décision que si la précédente a été effectivement rouverte par un
 * recours résolu (cf. §6.8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decisions', function (Blueprint $table) {
            // dossier_audiencement_id n'a pas d'index propre : sa clé
            // étrangère s'appuie jusqu'ici sur l'index composite unique
            // supprimé ci-dessous — il faut lui en fournir un avant de le
            // supprimer, sous peine de MySQL error 1553.
            $table->index('dossier_audiencement_id');
        });

        Schema::table('decisions', function (Blueprint $table) {
            $table->dropUnique(['dossier_audiencement_id', 'personne_id']);
        });
    }

    public function down(): void
    {
        Schema::table('decisions', function (Blueprint $table) {
            $table->unique(['dossier_audiencement_id', 'personne_id']);
        });

        Schema::table('decisions', function (Blueprint $table) {
            $table->dropIndex(['dossier_audiencement_id']);
        });
    }
};
