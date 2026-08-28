<?php

namespace App\Domain\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Nettoie le HTML soumis par l'éditeur enrichi du frontend (contenu de PV,
 * réquisitions, description d'affaire, obligations, notes — voir
 * web/src/components/rich-text-editor.tsx) avant tout enregistrement.
 *
 * Le schéma restreint de l'éditeur (Tiptap/ProseMirror) ne protège que
 * l'interface qui l'utilise : rien n'empêche un appel direct à l'API avec un
 * autre client. La sécurité ne peut donc pas reposer sur « seul cet éditeur
 * produit ce HTML » — elle se joue ici, au moment de l'écriture, pour que
 * toute lecture ultérieure (affichage web, PDF du PV) soit sûre par
 * construction sans avoir à re-filtrer à chaque consommateur.
 */
class TexteEnrichiSanitizer
{
    public static function nettoyer(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p[style],strong,em,u,s,ul,ol,li,blockquote,br');
        $config->set('CSS.AllowedProperties', 'text-align');
        $config->set('Cache.DefinitionImpl', null);

        return (new HTMLPurifier($config))->purify($html);
    }
}
