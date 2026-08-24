<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramétrage du moteur central des délais (§6.11, §9) : chaque type d'acte
 * générateur (placement en GAV, écrou provisoire, décision, signification...)
 * porte ici sa durée légale et ses seuils d'alerte. Modifiable lors des
 * réformes, avec date d'entrée en vigueur (§11) — sans toucher au code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delais_legaux', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('type_acte');
            $table->string('categorie_infraction')->nullable();
            $table->unsignedInteger('duree_heures')->nullable();
            $table->unsignedInteger('duree_jours')->nullable();
            $table->unsignedInteger('alerte_avant_heures')->nullable();
            $table->unsignedInteger('alerte_avant_minutes')->nullable();
            $table->date('date_entree_vigueur');
            $table->date('date_fin_vigueur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delais_legaux');
    }
};
