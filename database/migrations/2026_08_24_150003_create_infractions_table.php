<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel légal des infractions (§6.13) : versionné, avec date d'effet,
 * pour permettre l'intégration des réformes du code de procédure pénale par
 * paramétrage plutôt que par modification du cœur applicatif (§11, §10.1-O).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infractions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->enum('categorie', ['contravention', 'delit', 'crime']);
            $table->string('texte_reference')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->date('date_entree_vigueur');
            $table->date('date_fin_vigueur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infractions');
    }
};
