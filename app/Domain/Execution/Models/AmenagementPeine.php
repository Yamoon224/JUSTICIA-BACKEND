<?php

namespace App\Domain\Execution\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ecrou_id', 'type', 'decide_at', 'decide_par'])]
class AmenagementPeine extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "amenagement_peines" ;
    // la migration nomme la table au pluriel sur "amenagements", pas "peine".
    protected $table = 'amenagements_peine';

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
