<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Remises de peine et grâces (§6.9), tracées individuellement plutôt que
// comme un simple ajustement de compteur — chacune reste opposable.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remises_peine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecrou_id')->constrained('ecrous')->cascadeOnDelete();
            $table->unsignedInteger('jours');
            $table->enum('motif', ['grace', 'reduction_peine']);
            $table->foreignId('decide_par')->constrained('users')->restrictOnDelete();
            $table->timestamp('decide_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remises_peine');
    }
};
