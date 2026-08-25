<?php

namespace App\Domain\GardeAVue\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mesure_id', 'type', 'debut_at', 'fin_at', 'notes', 'enregistre_par'])]
class GavActe extends Model
{
    protected function casts(): array
    {
        return [
            'debut_at' => 'datetime',
            'fin_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MesureGardeAVue, $this>
     */
    public function mesure(): BelongsTo
    {
        return $this->belongsTo(MesureGardeAVue::class, 'mesure_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}
