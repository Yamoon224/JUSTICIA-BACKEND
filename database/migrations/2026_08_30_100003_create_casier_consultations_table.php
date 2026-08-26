<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace de chaque consultation nominative du casier (§6.10 : « consultation
 * nominative journalisée et motivée ») — en plus du journal d'audit général
 * (App\Domain\Audit), une table dédiée directement interrogeable pour
 * répondre à « qui a consulté le dossier de telle personne, et pourquoi ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casier_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personne_id')->constrained('personnes')->restrictOnDelete();
            $table->enum('type_bulletin', ['b1', 'b2', 'b3']);
            $table->string('motif');
            $table->timestamp('consultee_at');
            $table->foreignId('consultee_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['personne_id', 'consultee_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casier_consultations');
    }
};
