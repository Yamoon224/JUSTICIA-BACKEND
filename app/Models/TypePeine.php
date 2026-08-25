<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'libelle', 'categorie'])]
class TypePeine extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait "type_peines".
    protected $table = 'types_peines';
}
