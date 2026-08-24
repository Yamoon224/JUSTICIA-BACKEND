<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des ressorts territoriaux (§6.13, §8) : national, cour d'appel,
 * tribunal. Sert de base au cloisonnement des habilitations ("un OPJ ne voit
 * que les affaires de son unité ; un greffier, celles de sa juridiction").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ressorts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->enum('type', ['national', 'cour_appel', 'tribunal']);
            $table->foreignId('parent_id')->nullable()->constrained('ressorts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ressorts');
    }
};
