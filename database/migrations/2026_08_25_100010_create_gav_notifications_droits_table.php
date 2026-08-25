<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notification des droits en garde à vue (§6.1), tracée et horodatée.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gav_notifications_droits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesure_id')->constrained('mesures_garde_a_vue')->cascadeOnDelete();
            $table->enum('droit', ['silence', 'avocat', 'medecin', 'information_proche']);
            $table->timestamp('notifie_at')->nullable();
            $table->string('mode_de_remise')->nullable();
            $table->foreignId('enregistre_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['mesure_id', 'droit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gav_notifications_droits');
    }
};
