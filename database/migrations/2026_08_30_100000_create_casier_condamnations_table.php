<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Condamnation inscrite au casier judiciaire (§6.10), alimentée
 * automatiquement dès la mise à exécution d'une décision de condamnation
 * définitive (App\Domain\Execution\Actions\MettreAExecutionAction) — jamais
 * saisie manuellement. Les champs affaire/infraction/juridiction sont un
 * instantané au moment de l'inscription : le casier doit rester lisible et
 * stable même si le dossier source est ensuite modifié ou archivé.
 *
 * Le casier judiciaire est un registre national (§6.10), pas cloisonné par
 * ressort comme le reste du socle : une condamnation prononcée dans un
 * ressort doit être visible depuis n'importe quel autre lors d'une
 * consultation nominative — d'où l'absence de colonne ressort_id ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casier_condamnations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personne_id')->constrained('personnes')->restrictOnDelete();
            $table->foreignId('decision_id')->unique()->constrained('decisions')->restrictOnDelete();
            $table->string('numero_affaire');
            $table->string('juridiction_nom');
            $table->string('infraction_libelle');
            $table->enum('categorie_infraction', ['contravention', 'delit', 'crime']);
            $table->string('peine_principale')->nullable();
            $table->boolean('sursis')->default(false);
            $table->timestamp('condamnee_at');
            $table->enum('statut', ['active', 'rehabilitee', 'amnistiee'])->default('active');
            $table->timestamp('inscrite_at');
            $table->timestamps();

            $table->index(['personne_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casier_condamnations');
    }
};
