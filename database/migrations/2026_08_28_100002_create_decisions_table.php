<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Décision par prévenu (§6.7) : condamnation, relaxe, acquittement,
 * dispense de peine. Le délai de recours est résolu depuis le référentiel
 * `delais_legaux` (comme les autres délais du système) et détermine le
 * caractère définitif — voir Decision::estDefinitive().
 *
 * Une seule décision par personne et par dossier dans ce périmètre : le
 * détail par infraction (§6.7) est un raffinement futur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_audiencement_id')->constrained('dossiers_audiencement')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->enum('decision', ['condamnation', 'relaxe', 'acquittement', 'dispense_de_peine']);
            $table->string('peine_principale')->nullable();
            $table->boolean('sursis')->default(false);
            $table->text('interets_civils')->nullable();
            $table->timestamp('rendue_at');
            $table->unsignedInteger('delai_recours_jours');
            $table->timestamp('delai_recours_expire_at');
            $table->foreignId('rendue_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['dossier_audiencement_id', 'personne_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
