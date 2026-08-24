<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Référentiel des établissements pénitentiaires (§6.9, §6.13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissements_penitentiaires', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->foreignId('ressort_id')->constrained('ressorts')->cascadeOnDelete();
            $table->unsignedInteger('capacite')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements_penitentiaires');
    }
};
