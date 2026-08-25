<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattachement d'une personne à une affaire avec son statut (§6.2, §3) :
 * une même personne peut être suspecte sur une affaire et victime sur une
 * autre — le statut n'est donc jamais porté par `personnes` mais par ce
 * pivot. La présomption d'innocence impose de ne jamais dériver un statut
 * "condamné" ailleurs que d'une décision définitive effective (Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affaire_personne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->enum('statut', [
                'suspect',
                'temoin',
                'temoin_assiste',
                'mis_en_examen',
                'prevenu',
                'accuse',
                'condamne',
                'relaxe',
                'acquitte',
                'non_lieu',
                'victime',
                'avocat_constitue',
            ]);
            $table->timestamp('depuis')->useCurrent();
            $table->timestamps();

            $table->unique(['affaire_id', 'personne_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affaire_personne');
    }
};
