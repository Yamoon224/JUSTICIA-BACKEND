<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit central inviolable (§8, §9, §10.1) : append-only, horodaté,
 * scellé par chaînage cryptographique (chaque entrée porte le hash de la
 * précédente). Aucune mise à jour ni suppression ne doit jamais être exécutée
 * sur cette table — voir App\Domain\Audit\AuditService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->foreignId('ressort_id')->nullable()->constrained('ressorts')->nullOnDelete();
            $table->string('motif')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('hash', 64);
            // Pas d'updated_at : une trace d'audit ne se modifie jamais.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
