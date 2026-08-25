<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Actes durant la mesure de garde à vue (§6.1) : auditions, examens
// médicaux, entretiens avocat, confrontations.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gav_actes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesure_id')->constrained('mesures_garde_a_vue')->cascadeOnDelete();
            $table->enum('type', ['audition', 'examen_medical', 'entretien_avocat', 'confrontation', 'repos']);
            $table->timestamp('debut_at');
            $table->timestamp('fin_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('enregistre_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gav_actes');
    }
};
