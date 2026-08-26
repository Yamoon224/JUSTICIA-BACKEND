<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertes personnelles (§6.1, §6.11) : le moteur de délais qualifiait déjà
 * les échéances (DetecterEcheancesGardeAVueAction, ...Detention...) et se
 * contentait de les journaliser — cette table est le premier canal de
 * routage effectif vers un destinataire humain (§10.1-D : un canal
 * technique de plus, le calcul du niveau ne change pas).
 *
 * Polymorphe (alertable_type/id) : une même mesure peut générer plusieurs
 * alertes successives (information puis avertissement puis dépassement),
 * jamais réécrites — l'historique fait foi (§7 : pas de suppression
 * physique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->enum('niveau', ['information', 'avertissement', 'depassement']);
            $table->string('message');
            $table->morphs('alertable');
            $table->foreignId('destinataire_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('lue_at')->nullable();
            $table->timestamps();

            $table->index(['destinataire_id', 'lue_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
