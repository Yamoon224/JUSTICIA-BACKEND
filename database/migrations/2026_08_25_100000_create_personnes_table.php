<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fichier central des personnes mises en cause (§6.2) : identifiant unique
 * suivi de bout en bout, y compris personnes morales (entreprises) avec
 * représentant légal. Les alias/identités déclarées multiples sont stockés
 * en JSON plutôt qu'en table séparée pour rester simple tant qu'aucun cas
 * d'usage n'exige de les interroger individuellement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnes', function (Blueprint $table) {
            $table->id();
            $table->uuid('identifiant_unique')->unique();
            $table->enum('type', ['physique', 'morale'])->default('physique');

            // Personne physique.
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->json('alias')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->string('nom_pere')->nullable();
            $table->string('nom_mere')->nullable();

            // Personne morale.
            $table->string('raison_sociale')->nullable();
            $table->foreignId('representant_legal_id')->nullable()->constrained('personnes')->nullOnDelete();

            $table->string('adresse')->nullable();
            $table->string('signalement')->nullable();

            // Détection de doublons (§6.2) : fusion tracée, jamais de
            // suppression physique (§7 intégrité).
            $table->foreignId('fusionnee_dans_id')->nullable()->constrained('personnes')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nom', 'prenom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnes');
    }
};
