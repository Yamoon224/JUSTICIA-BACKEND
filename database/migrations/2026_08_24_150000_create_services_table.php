<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des services/institutions de la chaîne pénale (§6.13, §4).
 * Ex. Police, Gendarmerie, Parquet, Cabinet d'instruction, Juridiction,
 * Greffe, Administration pénitentiaire, Service du casier, DSI Justice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->enum('type', [
                'police',
                'gendarmerie',
                'parquet',
                'instruction',
                'juridiction',
                'greffe',
                'penitentiaire',
                'casier',
                'ministere',
                'dsi',
            ]);
            $table->foreignId('parent_id')->nullable()->constrained('services')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
