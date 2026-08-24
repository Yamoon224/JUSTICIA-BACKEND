<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Référentiel des unités de police/gendarmerie (§6.13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->enum('type', ['police', 'gendarmerie']);
            $table->foreignId('ressort_id')->constrained('ressorts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites');
    }
};
