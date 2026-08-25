<?php

namespace App\Domain\Instruction\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_instruction_id', 'type', 'description', 'date_prevue', 'date_realisation', 'statut', 'created_by'])]
class ActeInstruction extends Model
{
    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
            'date_realisation' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierInstruction, $this>
     */
    public function dossierInstruction(): BelongsTo
    {
        return $this->belongsTo(DossierInstruction::class);
    }
}
