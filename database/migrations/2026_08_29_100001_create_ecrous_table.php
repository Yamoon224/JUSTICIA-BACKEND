<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre d'écrou digital (§6.9) : numéro unique, situation pénale
 * (durée, imputation de la détention provisoire, remises de peine), tenue
 * jusqu'à la levée d'écrou. La détention provisoire imputée est saisie
 * explicitement à l'écrou plutôt que recalculée depuis le module
 * Instruction (§6.6) : toutes les affaires n'y transitent pas (citation
 * directe, comparution immédiate), et une reprise automatique fiable
 * exigerait un rapprochement hors périmètre de ce socle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecrous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_execution_id')->unique()->constrained('dossiers_execution')->cascadeOnDelete();
            $table->string('numero_ecrou')->unique();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements_penitentiaires')->restrictOnDelete();
            $table->timestamp('date_ecrou');
            $table->unsignedInteger('duree_jours');
            $table->unsignedInteger('detention_provisoire_imputee_jours')->default(0);
            $table->timestamp('date_fin_prevue');
            $table->enum('statut', ['en_detention', 'libere'])->default('en_detention');
            $table->timestamp('date_liberation')->nullable();
            $table->enum('motif_liberation', ['terme', 'amenagement', 'grace'])->nullable();
            $table->foreignId('ecroue_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['statut', 'date_fin_prevue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecrous');
    }
};
