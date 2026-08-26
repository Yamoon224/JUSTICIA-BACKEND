<?php

namespace App\Domain\Documents\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Domain\Contracts\StockageDocuments;
use App\Domain\Documents\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Versement d'une pièce au dossier (§6.2 photos/pièces d'identité, §6.3
 * pièces externes versées à l'affaire, §6.4 photos de scellé). Calcule la
 * cotation automatique (§6.3) pour les pièces versées à une affaire — les
 * autres catégories (photo de personne, de scellé) n'en ont pas besoin.
 *
 * Empreinte d'intégrité (§9) : un sha256 du contenu en clair, vérifié à
 * chaque lecture (RecupererDocumentAction) pour détecter toute altération
 * du fichier chiffré sur le disque.
 */
class VerserDocumentAction
{
    public function __construct(
        private readonly StockageDocuments $stockage,
        private readonly AuditService $audit,
    ) {}

    public function executer(Model $documentable, UploadedFile $fichier, string $categorie, User $agent): Document
    {
        $contenu = $fichier->get();
        $extension = $fichier->extension() ?: $fichier->getClientOriginalExtension();
        $chemin = $this->stockage->ecrire($contenu, $extension);

        $document = Document::query()->create([
            'documentable_type' => $documentable->getMorphClass(),
            'documentable_id' => $documentable->getKey(),
            'categorie' => $categorie,
            'cote' => $documentable instanceof Affaire ? $this->prochaineCote($documentable) : null,
            'nom_original' => $fichier->getClientOriginalName(),
            'mime_type' => $fichier->getMimeType() ?? $fichier->getClientMimeType(),
            'taille_octets' => $fichier->getSize(),
            'chemin_stockage' => $chemin,
            'hash_integrite' => hash('sha256', $contenu),
            'verse_par' => $agent->id,
        ]);

        $this->audit->consigner('documents.versement', auditable: $document, acteur: $agent, payloadSupplementaire: [
            'categorie' => $categorie,
        ]);

        return $document;
    }

    private function prochaineCote(Affaire $affaire): int
    {
        return 1 + (int) Document::query()
            ->where('documentable_type', $affaire->getMorphClass())
            ->where('documentable_id', $affaire->id)
            ->max('cote');
    }
}
