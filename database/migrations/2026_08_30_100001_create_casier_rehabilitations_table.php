<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réhabilitation d'une condamnation (§6.10) : de plein droit (automatique,
 * après le délai légal sans nouvelle condamnation active — §6.11, moteur
 * planifié) ou judiciaire (décision d'une juridiction, saisie par un agent
 * habilité). Efface la mention des bulletins B2/B3, jamais du B1 (§6.10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casier_rehabilitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condamnation_id')->unique()->constrained('casier_condamnations')->cascadeOnDelete();
            $table->enum('type', ['plein_droit', 'judiciaire']);
            $table->timestamp('decidee_at');
            // Null pour une réhabilitation de plein droit : constatée par le
            // moteur planifié, pas décidée par un agent (§6.11).
            $table->foreignId('decidee_par')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casier_rehabilitations');
    }
};
