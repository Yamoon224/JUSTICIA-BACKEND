<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Travaux d'intérêt général (§6.9) : affectation et suivi des heures.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travaux_interet_general', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_execution_id')->unique()->constrained('dossiers_execution')->cascadeOnDelete();
            $table->unsignedInteger('heures_requises');
            $table->unsignedInteger('heures_effectuees')->default(0);
            $table->string('affecte_a')->nullable();
            $table->enum('statut', ['en_cours', 'terminee'])->default('en_cours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travaux_interet_general');
    }
};
