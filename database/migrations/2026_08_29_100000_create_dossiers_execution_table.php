<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier d'exécution (§6.9) : ouvert quand une condamnation devient
 * définitive et qu'un agent la met à exécution — un acte administratif,
 * jamais automatique (§3), même si la décision d'origine est
 * irrévocablement acquise. Porte ensuite 0 ou 1 écrou, amende, TIG ou mise
 * à l'épreuve selon la peine effectivement prononcée (§6.9 : « peine
 * principale, peines complémentaires, sursis »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_execution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->unique()->constrained('decisions')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->enum('statut', ['en_cours', 'terminee'])->default('en_cours');
            $table->timestamp('mise_a_execution_at');
            $table->foreignId('mise_a_execution_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_execution');
    }
};
