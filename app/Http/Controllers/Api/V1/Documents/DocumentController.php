<?php

namespace App\Http\Controllers\Api\V1\Documents;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\Scelle;
use App\Domain\Documents\Actions\RecupererDocumentAction;
use App\Domain\Documents\Actions\VerserDocumentAction;
use App\Domain\Documents\Models\Document;
use App\Domain\Personnes\Models\Personne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\TelechargerDocumentRequest;
use App\Http\Requests\Documents\VerserDocumentAffaireRequest;
use App\Http\Requests\Documents\VerserDocumentPersonneRequest;
use App\Http\Requests\Documents\VerserDocumentScelleRequest;
use App\Http\Resources\DocumentResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    /**
     * Reflète les types acceptés au versement (VerserDocumentPersonneRequest
     * et consorts) : une seconde barrière, indépendante de cette validation,
     * avant de renvoyer quoi que ce soit au navigateur — un mime_type
     * inattendu (bug de validation, colonne modifiée directement) ne doit
     * jamais être répercuté tel quel dans l'en-tête Content-Type (confusion
     * MIME / XSS sur l'origine de l'API, cookie de session inclus).
     */
    private const MIME_SURS = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function storePourPersonne(VerserDocumentPersonneRequest $request, Personne $personne, VerserDocumentAction $action): JsonResponse
    {
        $document = $action->executer(
            $personne,
            $request->file('fichier'),
            $request->string('categorie')->toString(),
            $request->user(),
        );

        return DocumentResource::make($document)->response()->setStatusCode(201);
    }

    public function storePourAffaire(VerserDocumentAffaireRequest $request, Affaire $affaire, VerserDocumentAction $action): JsonResponse
    {
        $document = $action->executer($affaire, $request->file('fichier'), 'piece_versee', $request->user());

        return DocumentResource::make($document)->response()->setStatusCode(201);
    }

    public function storePourScelle(VerserDocumentScelleRequest $request, Scelle $scelle, VerserDocumentAction $action): JsonResponse
    {
        $document = $action->executer($scelle, $request->file('fichier'), 'photo', $request->user());

        return DocumentResource::make($document)->response()->setStatusCode(201);
    }

    /**
     * Retourne le contenu déchiffré (§9) après vérification de
     * l'habilitation sur le dossier porteur et de l'intégrité du fichier
     * (RecupererDocumentAction) — jamais d'accès direct au disque `pieces`.
     */
    public function telecharger(TelechargerDocumentRequest $request, Document $document, RecupererDocumentAction $action): Response
    {
        Gate::authorize('view', $this->cibleAutorisation($document));

        $contenu = $action->executer($document, $request->user(), $request->string('motif')->toString() ?: null);

        $mimeType = in_array($document->mime_type, self::MIME_SURS, true) ? $document->mime_type : 'application/octet-stream';
        // Les images restent affichables en ligne (aperçu photo, §6.2/§6.4) ;
        // tout le reste (PDF, ou un mime_type non reconnu) est forcé en
        // téléchargement.
        $disposition = str_starts_with($mimeType, 'image/') ? 'inline' : 'attachment';
        $nomFichier = rawurlencode($document->nom_original);

        return response($contenu, 200)
            ->header('Content-Type', $mimeType)
            // Empêche le navigateur de réinterpréter le contenu contre le
            // type déclaré (attaque par confusion MIME).
            ->header('X-Content-Type-Options', 'nosniff')
            // Filet de sécurité supplémentaire si le contenu était malgré
            // tout rendu comme un document actif (HTML/SVG) : aucun script,
            // aucune soumission de formulaire, aucune popup.
            ->header('Content-Security-Policy', 'sandbox')
            ->header('Content-Disposition', "{$disposition}; filename=\"{$nomFichier}\"; filename*=UTF-8''{$nomFichier}");
    }

    /**
     * Le Scelle n'a pas de Policy propre : son habilitation est celle de
     * l'affaire qui le porte (cf. ScelleController, qui délègue déjà à
     * l'Affaire via EnregistrerScelleRequest).
     */
    private function cibleAutorisation(Document $document): Model
    {
        $cible = $document->documentable;

        return $cible instanceof Scelle ? $cible->affaire : $cible;
    }
}
