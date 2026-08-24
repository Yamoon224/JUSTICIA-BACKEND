<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Référentiel des juridictions de jugement (§6.13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juridictions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->string('type')->nullable();
            $table->foreignId('ressort_id')->constrained('ressorts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juridictions');
    }
};
