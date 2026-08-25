<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procès-verbaux (§6.3) : un PV signé devient immuable — toute correction
 * passe par un PV rectificatif référencé (`rectifie_par_pv_id`), jamais par
 * une modification de l'original (§7 intégrité, §10.1-L Signable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proces_verbaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires')->cascadeOnDelete();
            $table->string('cote')->unique();
            $table->enum('type', ['interpellation', 'audition', 'perquisition', 'constatation', 'rectificatif', 'autre']);
            $table->foreignId('rectifie_par_pv_id')->nullable()->constrained('proces_verbaux')->nullOnDelete();
            $table->longText('contenu');
            $table->foreignId('redige_par')->constrained('users')->restrictOnDelete();
            $table->foreignId('signe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signe_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proces_verbaux');
    }
};
