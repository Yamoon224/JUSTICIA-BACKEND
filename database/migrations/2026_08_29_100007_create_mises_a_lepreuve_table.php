<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sursis avec mise à l'épreuve (§6.9) : obligations suivies.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mises_a_lepreuve', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_execution_id')->unique()->constrained('dossiers_execution')->cascadeOnDelete();
            $table->text('obligations');
            $table->enum('statut', ['en_cours', 'terminee'])->default('en_cours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mises_a_lepreuve');
    }
};
