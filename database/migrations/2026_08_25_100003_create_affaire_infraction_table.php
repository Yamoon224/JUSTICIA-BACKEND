<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Infractions retenues sur une affaire (§6.3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affaire_infraction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('infraction_id')->constrained('infractions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['affaire_id', 'infraction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affaire_infraction');
    }
};
