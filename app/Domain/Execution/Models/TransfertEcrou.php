<?php

namespace App\Domain\Execution\Models;

use App\Models\EtablissementPenitentiaire;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ecrou_id', 'etablissement_origine_id', 'etablissement_destination_id', 'motif', 'transfere_at', 'transfere_par'])]
class TransfertEcrou extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "transfert_ecrous" ;
    // la migration nomme la table au pluriel sur "transferts", pas "ecrou".
    protected $table = 'transferts_ecrou';

    protected function casts(): array
    {
        return [
            'transfere_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ecrou, $this>
     */
    public function ecrou(): BelongsTo
    {
        return $this->belongsTo(Ecrou::class);
    }

    /**
     * @return BelongsTo<EtablissementPenitentiaire, $this>
     */
    public function etablissementOrigine(): BelongsTo
    {
        return $this->belongsTo(EtablissementPenitentiaire::class, 'etablissement_origine_id');
    }

    /**
     * @return BelongsTo<EtablissementPenitentiaire, $this>
     */
    public function etablissementDestination(): BelongsTo
    {
        return $this->belongsTo(EtablissementPenitentiaire::class, 'etablissement_destination_id');
    }
}
