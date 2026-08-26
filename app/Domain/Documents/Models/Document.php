<?php

namespace App\Domain\Documents\Models;

use App\Domain\Contracts\Auditable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pièce versée au dossier (§6.2, §6.3, §6.4) : polymorphe, rattachable à
 * une Personne (photo, pièce d'identité), une Affaire (pièce versée, avec
 * cotation) ou un Scelle (photo). Le contenu lui-même ne transite jamais
 * par ce modèle — voir VerserDocumentAction / RecupererDocumentAction.
 */
#[Fillable([
    'documentable_type', 'documentable_id', 'categorie', 'cote', 'nom_original',
    'mime_type', 'taille_octets', 'chemin_stockage', 'hash_integrite', 'verse_par',
])]
class Document extends Model implements Auditable
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verse_par');
    }

    public function auditableRepresentation(): array
    {
        return [
            'document_id' => $this->id,
            'categorie' => $this->categorie,
            'documentable_type' => $this->documentable_type,
            'documentable_id' => $this->documentable_id,
        ];
    }
}
