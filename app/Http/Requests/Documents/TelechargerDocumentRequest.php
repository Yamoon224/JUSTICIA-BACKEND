<?php

namespace App\Http\Requests\Documents;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Foundation\Http\FormRequest;

/**
 * CONSTAT DE SÉCURITÉ (revue du 2026-08-27) : le fichier des personnes est
 * national, non cloisonné par ressort (App\Policies\PersonnePolicy) — la
 * seule contrepartie documentée à ce large accès est que « toute
 * consultation individuelle reste journalisée avec motif » (même exigence
 * que ConsulterPersonneRequest et GenererBulletinRequest). Le téléchargement
 * d'un document lié à une personne (photo, pièce d'identité) doit donc
 * exiger un motif au même titre — jusqu'ici il était accepté nul, ce qui
 * vidait cette contrepartie de son sens sans qu'aucun contrôle d'accès ne
 * soit lui-même contourné.
 */
class TelechargerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $documentable = $this->route('document')?->documentable;

        return [
            'motif' => $documentable instanceof Personne ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
        ];
    }
}
