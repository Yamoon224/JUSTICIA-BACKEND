<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pièces versées au dossier (§6.2 photos/pièces d'identité, §6.3 pièces
 * externes versées à l'affaire avec cotation automatique, §6.4 photos de
 * scellé) — table unique, polymorphe, plutôt qu'une table par module
 * porteur : le stockage physique et l'empreinte d'intégrité (§9) sont les
 * mêmes quel que soit le dossier auquel la pièce est rattachée.
 *
 * `chemin_stockage` est un identifiant opaque vers le disque `pieces`
 * (config/filesystems.php) : le contenu y est chiffré, jamais servi
 * directement (voir App\Domain\Contracts\StockageDocuments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('categorie');
            // Cotation automatique (§6.3) : uniquement pour les pièces
            // versées à une affaire — nulle pour les autres catégories
            // (photo de personne, de scellé), qui n'en ont pas besoin.
            $table->unsignedInteger('cote')->nullable();
            $table->string('nom_original');
            $table->string('mime_type');
            $table->unsignedBigInteger('taille_octets');
            $table->string('chemin_stockage');
            $table->string('hash_integrite', 64);
            $table->foreignId('verse_par')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['documentable_type', 'documentable_id', 'cote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
