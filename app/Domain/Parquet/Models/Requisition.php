<?php

namespace App\Domain\Parquet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_parquet_id', 'type', 'contenu', 'magistrat_id'])]
class Requisition extends Model
{
    /**
     * @return BelongsTo<DossierParquet, $this>
     */
    public function dossierParquet(): BelongsTo
    {
        return $this->belongsTo(DossierParquet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function magistrat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'magistrat_id');
    }
}
