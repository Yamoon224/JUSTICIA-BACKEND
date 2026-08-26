<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §6.13, §7 : un agent (OPJ d'une unité éloignée, notamment) n'a pas
 * nécessairement d'adresse électronique institutionnelle — l'authentification
 * se fait par matricule (AuthentifierAgentAction), jamais par email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
