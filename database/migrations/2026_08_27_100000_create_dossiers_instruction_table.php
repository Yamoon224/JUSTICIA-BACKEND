<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier d'information (§6.6), ouvert automatiquement quand le parquet
 * oriente une affaire vers une ouverture d'information
 * (App\Domain\Parquet\Actions\OrienterAction). L'ordonnance de règlement
 * (renvoi ou non-lieu) est la seule décision qui clôture le dossier — une
 * mise en liberté n'y met jamais fin, elle ne fait que lever une mesure de
 * sûreté (voir mesures_surete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossiers_instruction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->unique()->constrained('affaires')->cascadeOnDelete();
            $table->foreignId('juge_instruction_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ouvert_at');
            $table->enum('statut', ['en_cours', 'cloture'])->default('en_cours');

            $table->enum('ordonnance', ['renvoi', 'non_lieu'])->nullable();
            $table->timestamp('ordonnance_at')->nullable();
            $table->foreignId('ordonnance_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delai_recours_expire_at')->nullable();

            $table->timestamps();

            $table->index(['juge_instruction_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_instruction');
    }
};
