<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier d'affaire (§6.3) : numéro unique attribué dès l'origine et
 * conservé tout au long de la chaîne. Le ressort porté ici est la base du
 * cloisonnement des habilitations (§8) — voir App\Policies\AffairePolicy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affaires', function (Blueprint $table) {
            $table->id();
            $table->string('numero_affaire')->unique();
            $table->foreignId('unite_id')->nullable()->constrained('unites')->nullOnDelete();
            $table->foreignId('ressort_id')->constrained('ressorts')->cascadeOnDelete();
            $table->enum('statut', [
                'ouverte',
                'transmise_parquet',
                'classee_sans_suite',
                'information_ouverte',
                'audiencee',
                'jugee',
                'cloturee',
            ])->default('ouverte');
            $table->text('description')->nullable();
            $table->date('date_ouverture');
            $table->foreignId('affaire_jointe_a_id')->nullable()->constrained('affaires')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affaires');
    }
};
