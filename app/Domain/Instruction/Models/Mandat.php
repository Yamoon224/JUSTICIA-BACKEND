<?php

namespace App\Domain\Instruction\Models;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_instruction_id', 'personne_id', 'type', 'emis_at', 'diffuse_at', 'execute_at', 'emis_par'])]
class Mandat extends Model
{
    protected function casts(): array
    {
        return [
            'emis_at' => 'datetime',
            'diffuse_at' => 'datetime',
            'execute_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierInstruction, $this>
     */
    public function dossierInstruction(): BelongsTo
    {
        return $this->belongsTo(DossierInstruction::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }
}
