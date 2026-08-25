<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bureau des arrivées du parquet (§6.5) : une affaire transmise crée un
 * dossier parquet, affecté à un magistrat, puis orienté. L'orientation
 * n'est jamais automatique — elle consigne toujours la décision effective
 * d'un magistrat (§3 : le système ne décide jamais à la place de
 * l'autorité compétente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_parquet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->unique()->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('magistrat_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recu_at');
            $table->timestamp('affecte_at')->nullable();

            $table->enum('orientation', [
                'classement_sans_suite',
                'rappel_a_la_loi',
                'mediation_penale',
                'composition_penale',
                'citation_directe',
                'ouverture_information',
                'comparution_immediate',
            ])->nullable();
            $table->foreignId('motif_classement_id')->nullable()->constrained('motifs_classement')->nullOnDelete();
            $table->timestamp('oriente_at')->nullable();
            $table->foreignId('oriente_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['magistrat_id', 'oriente_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_parquet');
    }
};
