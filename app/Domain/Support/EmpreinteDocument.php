<?php

namespace App\Domain\Support;

/**
 * Empreinte numérique d'un document édité (§8 : « transition = signature
 * manuscrite + empreinte numérique »). Un simple sha256 des faits qui
 * composent le document — pas une preuve cryptographique opposable comme un
 * certificat de signature électronique (cible non atteinte, cf. §8), mais
 * un moyen de détecter qu'une copie imprimée a été altérée après édition.
 */
class EmpreinteDocument
{
    /**
     * @param  array<string, mixed>  $donnees
     */
    public static function calculer(array $donnees): string
    {
        return hash('sha256', json_encode($donnees, JSON_THROW_ON_ERROR));
    }
}
