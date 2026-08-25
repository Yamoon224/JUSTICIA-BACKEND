<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pièces d'identité présentées par une personne (§6.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personne_pieces_identite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personne_id')->constrained('personnes')->cascadeOnDelete();
            $table->string('type');
            $table->string('numero');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personne_pieces_identite');
    }
};
