<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Aménagements de peine (§6.9) : libération conditionnelle, semi-liberté,
// placement à l'extérieur.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenagements_peine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecrou_id')->constrained('ecrous')->cascadeOnDelete();
            $table->enum('type', ['liberation_conditionnelle', 'semi_liberte', 'placement_exterieur']);
            $table->timestamp('decide_at');
            $table->foreignId('decide_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenagements_peine');
    }
};
