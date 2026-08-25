<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier d'audiencement (§6.7), ouvert automatiquement quand une affaire
 * atteint le statut `audiencee` — citation directe ou comparution
 * immédiate décidées par le parquet (§6.5), ou renvoi ordonné par le juge
 * d'instruction (§6.6). L'enrôlement effectif (juridiction, chambre, date,
 * composition) reste un acte du greffe, jamais automatique (§3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_audiencement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->unique()->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('juridiction_id')->nullable()->constrained('juridictions')->nullOnDelete();
            $table->string('chambre')->nullable();
            $table->timestamp('date_audience')->nullable();
            $table->foreignId('president_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('greffier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['a_enroler', 'enrole', 'jugee'])->default('a_enroler');
            $table->timestamp('cree_at');
            $table->timestamps();

            $table->index(['statut', 'date_audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_audiencement');
    }
};
