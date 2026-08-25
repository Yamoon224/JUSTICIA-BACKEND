<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pièces à conviction / scellés (§6.4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affaire_id')->constrained('affaires')->cascadeOnDelete();
            $table->string('numero_scelle')->unique();
            $table->string('description');
            $table->string('lieu_saisie')->nullable();
            $table->enum('statut', ['en_depot', 'sorti_expertise', 'restitue', 'confisque', 'detruit'])->default('en_depot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scelles');
    }
};
