<?php

namespace App\Domain\Execution\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ecrou_id', 'jours', 'motif', 'decide_par', 'decide_at'])]
class RemisePeine extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "remise_peines" ; la
    // migration nomme la table au pluriel sur "remises", pas "peine".
    protected $table = 'remises_peine';

    protected function casts(): array
    {
        return [
            'decide_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ecrou, $this>
     */
    public function ecrou(): BelongsTo
    {
        return $this->belongsTo(Ecrou::class);
    }
}
