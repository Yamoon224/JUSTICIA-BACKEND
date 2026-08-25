<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaîne de conservation des scellés (§6.4, chain of custody) : chaque
 * mouvement est tracé avec remettant, récepteur et horodatage. Table
 * append-only au même titre que audit_logs — un mouvement enregistré ne se
 * corrige pas, on en ajoute un nouveau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scelle_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scelle_id')->constrained('scelles')->cascadeOnDelete();
            $table->enum('type', ['depot', 'sortie_expertise', 'retour_expertise', 'restitution', 'confiscation', 'destruction']);
            $table->foreignId('remettant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recepteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motif')->nullable();
            $table->timestamp('horodatage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scelle_mouvements');
    }
};
