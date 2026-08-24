<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étend la table users pour porter les attributs d'un agent habilité JUSTICIA
 * (§4, §8) : matricule nominatif, rattachement à un service et un ressort
 * (base du cloisonnement des habilitations), statut du compte.
 *
 * Le rôle applicatif (OPJ, procureur, greffier...) est porté par
 * spatie/laravel-permission (table model_has_roles), pas par une colonne ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('matricule')->unique()->after('id');
            $table->string('nom')->after('matricule');
            $table->string('prenom')->after('nom');
            $table->foreignId('service_id')->nullable()->after('prenom')->constrained('services')->nullOnDelete();
            $table->foreignId('ressort_id')->nullable()->after('service_id')->constrained('ressorts')->nullOnDelete();
            $table->boolean('actif')->default(true)->after('ressort_id');
            $table->timestamp('suspendu_at')->nullable()->after('actif');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('ressort_id');
            $table->dropColumn(['matricule', 'nom', 'prenom', 'actif', 'suspendu_at']);
            $table->string('name')->after('id');
        });
    }
};
