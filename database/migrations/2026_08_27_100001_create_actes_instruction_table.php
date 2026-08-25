<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Actes d'instruction (§6.6) : interrogatoires, confrontations, transports,
// commissions rogatoires (suivi des retours), expertises (suivi des rapports).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actes_instruction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_instruction_id')->constrained('dossiers_instruction')->cascadeOnDelete();
            $table->enum('type', ['interrogatoire', 'confrontation', 'transport', 'commission_rogatoire', 'expertise']);
            $table->text('description')->nullable();
            $table->date('date_prevue')->nullable();
            $table->timestamp('date_realisation')->nullable();
            $table->enum('statut', ['en_attente', 'realise', 'retour_recu', 'rapport_depose'])->default('en_attente');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actes_instruction');
    }
};
