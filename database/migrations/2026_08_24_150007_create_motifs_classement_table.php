<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Référentiel des motifs de classement sans suite (§6.5, §6.13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motifs_classement', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_classement');
    }
};
