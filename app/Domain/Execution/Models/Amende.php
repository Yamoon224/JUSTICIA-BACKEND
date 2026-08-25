<?php

namespace App\Domain\Execution\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_execution_id', 'montant', 'statut', 'transmise_at', 'transmise_par'])]
class Amende extends Model
{
    protected function casts(): array
    {
        return [
            'transmise_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierExecution, $this>
     */
    public function dossierExecution(): BelongsTo
    {
        return $this->belongsTo(DossierExecution::class);
    }
}
