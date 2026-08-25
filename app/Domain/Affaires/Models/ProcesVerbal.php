<?php

namespace App\Domain\Affaires\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Contracts\Signable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Procès-verbal (§6.3). Un PV signé devient immuable : toute tentative de
 * modification échoue délibérément une fois `signe_at` renseigné — seule
 * une correction via un PV rectificatif référencé est possible
 * (RectifierProcesVerbalAction).
 */
#[Fillable(['affaire_id', 'cote', 'type', 'rectifie_par_pv_id', 'contenu', 'redige_par'])]
class ProcesVerbal extends Model implements Auditable, Signable
{
    // La pluralisation par défaut d'Eloquent donnerait "proces_verbals".
    protected $table = 'proces_verbaux';

    protected function casts(): array
    {
        return [
            'signe_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $pv): void {
            if ($pv->getOriginal('signe_at') !== null && $pv->isDirty('contenu')) {
                throw new LogicException('Un procès-verbal signé est immuable : passez par un PV rectificatif.');
            }
        });
    }

    /**
     * @return BelongsTo<Affaire, $this>
     */
    public function affaire(): BelongsTo
    {
        return $this->belongsTo(Affaire::class);
    }

    /**
     * @return BelongsTo<ProcesVerbal, $this>
     */
    public function rectifieParPv(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rectifie_par_pv_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function redacteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redige_par');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function signataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signe_par');
    }

    public function estSigne(): bool
    {
        return $this->signe_at !== null;
    }

    public function signer(User $signataire): void
    {
        if ($this->estSigne()) {
            throw new LogicException('Ce procès-verbal est déjà signé.');
        }

        $this->forceFill([
            'signe_par' => $signataire->id,
            'signe_at' => now(),
        ])->save();
    }

    public function auditableRepresentation(): array
    {
        return [
            'proces_verbal_id' => $this->id,
            'affaire_id' => $this->affaire_id,
            'cote' => $this->cote,
            'type' => $this->type,
        ];
    }
}
