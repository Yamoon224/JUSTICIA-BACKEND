<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amnistie d'une condamnation (§6.10) : efface la mention de tous les
 * bulletins, y compris le B1 — toujours une décision légale/réglementaire
 * explicite (texte de référence tracé), jamais automatique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casier_amnisties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condamnation_id')->unique()->constrained('casier_condamnations')->cascadeOnDelete();
            $table->string('texte_reference');
            $table->timestamp('decidee_at');
            $table->foreignId('decidee_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casier_amnisties');
    }
};
