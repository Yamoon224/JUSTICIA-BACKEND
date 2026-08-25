<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Transferts entre établissements pénitentiaires, tracés (§6.9).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferts_ecrou', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecrou_id')->constrained('ecrous')->cascadeOnDelete();
            $table->foreignId('etablissement_origine_id')->constrained('etablissements_penitentiaires')->restrictOnDelete();
            $table->foreignId('etablissement_destination_id')->constrained('etablissements_penitentiaires')->restrictOnDelete();
            $table->string('motif')->nullable();
            $table->timestamp('transfere_at');
            $table->foreignId('transfere_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts_ecrou');
    }
};
