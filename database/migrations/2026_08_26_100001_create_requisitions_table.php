<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Réquisitions du parquet consignées aux différentes étapes (§6.5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_parquet_id')->constrained('dossiers_parquet')->cascadeOnDelete();
            $table->string('type');
            $table->text('contenu');
            $table->foreignId('magistrat_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
