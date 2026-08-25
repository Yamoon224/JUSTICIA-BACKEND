<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre de garde à vue digital (§6.1) : placement horodaté, échéance
 * légale calculée à la création (duree_heures issue du référentiel
 * delais_legaux — voir App\Domain\GardeAVue\Actions\PlacerEnGardeAVueAction),
 * prolongations autorisées par le parquet, issue obligatoire à la clôture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesures_garde_a_vue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->foreignId('unite_id')->constrained('unites')->restrictOnDelete();

            $table->timestamp('debut_at');
            $table->unsignedInteger('duree_heures');
            $table->timestamp('fin_prevue_at');

            $table->unsignedInteger('prolongation_heures')->nullable();
            $table->foreignId('prolongation_autorisee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prolongation_at')->nullable();

            $table->enum('statut', ['en_cours', 'prolongee', 'terminee'])->default('en_cours');
            $table->enum('issue', ['liberation', 'convocation', 'deferement'])->nullable();
            $table->timestamp('fin_reelle_at')->nullable();

            // Régime spécifique mineurs (§6.1).
            $table->boolean('mineur')->default(false);
            $table->timestamp('avis_representant_legal_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['statut', 'fin_prevue_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesures_garde_a_vue');
    }
};
