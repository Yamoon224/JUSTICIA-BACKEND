<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §6.13 : « création/suspension de comptes à double validation ». Le compte
 * créé par un premier administrateur reste inactif (`actif = false`,
 * héritage de la colonne posée par add_agent_fields_to_users_table) tant
 * qu'un second administrateur — distinct du créateur — ne l'a pas validé
 * (ValiderCompteAction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cree_par')->nullable()->after('suspendu_at')->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->after('cree_par')->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable()->after('valide_par');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cree_par');
            $table->dropConstrainedForeignId('valide_par');
            $table->dropColumn('valide_at');
        });
    }
};
