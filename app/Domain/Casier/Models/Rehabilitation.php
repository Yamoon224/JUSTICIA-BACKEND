<?php

namespace App\Domain\Casier\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['condamnation_id', 'type', 'decidee_at', 'decidee_par'])]
class Rehabilitation extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "rehabilitations" ;
    // la migration nomme la table avec le préfixe du module "casier_".
    protected $table = 'casier_rehabilitations';

    protected function casts(): array
    {
        return [
            'decidee_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Condamnation, $this>
     */
    public function condamnation(): BelongsTo
    {
        return $this->belongsTo(Condamnation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidee_par');
    }
}
