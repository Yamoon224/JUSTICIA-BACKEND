<?php

namespace App\Domain\Support;

use App\Domain\Contracts\GenerateurPdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

/**
 * Implémentation par défaut de GenerateurPdf : Dompdf, moteur PHP pur — pas
 * de binaire système à installer, cohérent avec l'hébergement souverain visé
 * (§8, §9). isRemoteEnabled reste désactivé : les gabarits peuvent afficher
 * du contenu saisi par un agent (contenu de PV...), jamais de quoi charger
 * une ressource distante depuis le serveur de rendu.
 */
class GenerateurPdfDompdf implements GenerateurPdf
{
    public function depuisVue(string $vue, array $donnees): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(View::make($vue, $donnees)->render());
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
