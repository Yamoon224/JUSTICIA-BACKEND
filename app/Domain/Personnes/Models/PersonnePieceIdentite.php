<?php

namespace App\Domain\Personnes\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['personne_id', 'type', 'numero'])]
class PersonnePieceIdentite extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait
    // "personne_piece_identites" ; la migration nomme la table au pluriel
    // sur "pieces", pas sur "identite".
    protected $table = 'personne_pieces_identite';

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }
}
