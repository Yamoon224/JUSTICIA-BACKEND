<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesures de sûreté (§6.6) : contrôle judiciaire (obligations suivies) ou
 * détention provisoire, dont le délai maximal est résolu depuis le
 * référentiel `delais_legaux` (comme la garde à vue) plutôt que codé en
 * dur — voir PlacerEnDetentionProvisoireAction. Une détention arrivant à
 * échéance sans décision est signalée en priorité absolue (§6.6, §6.11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesures_surete', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_instruction_id')->constrained('dossiers_instruction')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->enum('type', ['controle_judiciaire', 'detention_provisoire']);
            $table->timestamp('debut_at');
            $table->unsignedInteger('duree_jours')->nullable();
            $table->timestamp('fin_prevue_at')->nullable();
            $table->timestamp('renouvele_le')->nullable();
            $table->text('obligations')->nullable();
            $table->enum('statut', ['en_cours', 'terminee'])->default('en_cours');
            $table->timestamp('fin_reelle_at')->nullable();
            $table->enum('motif_fin', ['mise_en_liberte', 'echeance'])->nullable();
            $table->foreignId('autorise_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['statut', 'fin_prevue_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesures_surete');
    }
};
