<?php

namespace App\Domain\Support;

use App\Domain\Contracts\StockageDocuments;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Implémentation par défaut de StockageDocuments : disque local dédié
 * (config('filesystems.disks.pieces')), jamais exposé au web (serve =>
 * false) — toute lecture passe par RecupererDocumentAction, qui journalise
 * la consultation (§8). Le contenu est chiffré avant écriture avec la clé
 * applicative (Crypt, AES-256-CBC) : une fuite du disque seul (sauvegarde
 * égarée, accès filesystem) ne révèle rien sans APP_KEY — remplaçable par
 * un stockage objet distant sans toucher au métier (§10.1-D).
 */
class StockageDocumentsChiffre implements StockageDocuments
{
    private const DISQUE = 'pieces';

    public function ecrire(string $contenu, string $extension): string
    {
        $chemin = Str::uuid()->toString().'.'.$extension.'.enc';

        Storage::disk(self::DISQUE)->put($chemin, Crypt::encryptString($contenu));

        return $chemin;
    }

    public function lire(string $chemin): string
    {
        return Crypt::decryptString(Storage::disk(self::DISQUE)->get($chemin));
    }
}
