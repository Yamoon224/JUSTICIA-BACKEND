<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voies de recours (§6.8) : appel, opposition, pourvoi en cassation — la
 * recevabilité est vérifiée automatiquement au regard du délai de la
 * décision visée, jamais laissée à la déclaration du demandeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained('decisions')->cascadeOnDelete();
            $table->enum('type', ['appel', 'opposition', 'pourvoi_cassation']);
            $table->foreignId('formee_par_personne_id')->nullable()->constrained('personnes')->nullOnDelete();
            $table->timestamp('formee_at');
            $table->boolean('recevable');
            $table->boolean('effet_suspensif')->default(true);
            $table->enum('decision_recours', ['confirmation', 'infirmation', 'cassation_avec_renvoi'])->nullable();
            $table->timestamp('decision_recours_at')->nullable();
            $table->foreignId('enregistre_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recours');
    }
};
