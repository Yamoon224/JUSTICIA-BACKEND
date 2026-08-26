<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La migration d'origine (2026_08_24_150003_create_infractions_table) porte
 * une colonne `version` et des dates d'effet explicitement pensées pour
 * plusieurs lignes d'un même `code` au fil des réformes (§6.13, §11) —
 * mais la contrainte unique sur `code` seul l'interdisait structurellement :
 * la toute première tentative de verser une nouvelle version échouait
 * (contrainte unique violée). Remplacée par une unicité sur (code, version).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infractions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('infractions', function (Blueprint $table) {
            $table->dropUnique(['code', 'version']);
            $table->unique('code');
        });
    }
};
