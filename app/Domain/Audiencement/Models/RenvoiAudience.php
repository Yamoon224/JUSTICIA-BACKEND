<?php

namespace App\Domain\Audiencement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_audiencement_id', 'ancienne_date_audience', 'nouvelle_date_audience', 'motif', 'decide_par'])]
class RenvoiAudience extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "renvoi_audiences" ;
    // la migration nomme la table au pluriel sur "renvois", pas "audience".
    protected $table = 'renvois_audience';

    protected function casts(): array
    {
        return [
            'ancienne_date_audience' => 'datetime',
            'nouvelle_date_audience' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierAudiencement, $this>
     */
    public function dossierAudiencement(): BelongsTo
    {
        return $this->belongsTo(DossierAudiencement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decide_par');
    }
}
