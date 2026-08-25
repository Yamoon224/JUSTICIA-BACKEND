<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Renvois d'audience motivés, avec nouvelle date immédiate (§6.7).
// Append-only : un renvoi ne se corrige pas, on en trace un nouveau.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renvois_audience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_audiencement_id')->constrained('dossiers_audiencement')->cascadeOnDelete();
            $table->timestamp('ancienne_date_audience')->nullable();
            $table->timestamp('nouvelle_date_audience');
            $table->string('motif');
            $table->foreignId('decide_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renvois_audience');
    }
};
