<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Recouvrement des amendes (§6.9) : états transmis au Trésor public.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amendes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_execution_id')->unique()->constrained('dossiers_execution')->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->enum('statut', ['transmise_tresor', 'recouvree'])->default('transmise_tresor');
            $table->timestamp('transmise_at');
            $table->foreignId('transmise_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amendes');
    }
};
