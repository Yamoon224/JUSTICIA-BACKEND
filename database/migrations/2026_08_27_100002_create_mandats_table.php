<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mandats (§6.6) : comparution, amener, dépôt, arrêt — émission, diffusion
// et exécution tracées.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_instruction_id')->constrained('dossiers_instruction')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->enum('type', ['comparution', 'amener', 'depot', 'arret']);
            $table->timestamp('emis_at');
            $table->timestamp('diffuse_at')->nullable();
            $table->timestamp('execute_at')->nullable();
            $table->foreignId('emis_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandats');
    }
};
