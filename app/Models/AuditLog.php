<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Entrée du journal d'audit inviolable (§8, §9). Append-only : toute
 * tentative de modification ou de suppression échoue délibérément — la seule
 * voie d'écriture légitime est App\Domain\Audit\AuditService::consigner().
 */
#[Fillable([
    'uuid', 'user_id', 'action', 'auditable_type', 'auditable_id',
    'ressort_id', 'motif', 'ip_address', 'user_agent', 'payload',
    'previous_hash', 'hash',
])]
class AuditLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Le journal d\'audit est en append-only : aucune entrée ne peut être modifiée.');
        });

        static::deleting(function (): never {
            throw new LogicException('Le journal d\'audit est en append-only : aucune entrée ne peut être supprimée.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Ressort, $this>
     */
    public function ressort(): BelongsTo
    {
        return $this->belongsTo(Ressort::class);
    }
}
