<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'libelle'])]
class MotifClassement extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "motif_classements".
    protected $table = 'motifs_classement';
}
