<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Référentiel des types de peines (§6.9, §6.13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_peines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->enum('categorie', [
                'emprisonnement',
                'amende',
                'sursis',
                'tig',
                'complementaire',
                'dispense',
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_peines');
    }
};
