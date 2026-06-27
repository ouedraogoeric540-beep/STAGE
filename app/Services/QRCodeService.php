<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class QRCodeService
{
    public function genererBase64(string $contenu): string
    {
        try {
            // Générer un SVG natif, qui ne nécessite pas ext-imagick et est parfait pour DomPDF
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(400)->margin(2)->generate($contenu);
            return base64_encode($svg);
        } catch (\Exception $e) {
            Log::warning('Erreur génération QrCode (SVG) : ' . $e->getMessage());
            // Fallback GD (qui renvoie du PNG base64)
            return $this->genererAvecGD($contenu);
        }
    }

    public function genererDataUri(string $contenu): string
    {
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(400)->margin(2)->generate($contenu);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Exception $e) {
            Log::warning('Erreur génération QrCode (SVG) : ' . $e->getMessage());
            // En cas d'erreur de la librairie (peu probable), on fallback sur GD (qui est du PNG)
            return 'data:image/png;base64,' . $this->genererAvecGD($contenu);
        }
    }

    private function genererAvecGD(string $contenu): string
    {
        $size  = 400;
        $img   = imagecreatetruecolor($size, $size);
        $blanc = imagecolorallocate($img, 255, 255, 255);
        $noir  = imagecolorallocate($img, 0, 0, 0);
        $bleu  = imagecolorallocate($img, 13, 110, 253);

        imagefill($img, 0, 0, $blanc);

        // Bordure
        imagerectangle($img, 2, 2, $size - 3, $size - 3, $bleu);

        // Texte centré
        $texte   = 'QR: ' . substr($contenu, 0, 20);
        $texte2  = substr($contenu, 20, 20);
        imagestring($img, 4, 20, 185, $texte,  $noir);
        imagestring($img, 4, 20, 210, $texte2, $noir);

        // Carré décoratif (simule QR)
        imagefilledrectangle($img, 30, 30, 100, 100, $noir);
        imagefilledrectangle($img, 40, 40, 90, 90, $blanc);
        imagefilledrectangle($img, 50, 50, 80, 80, $noir);

        imagefilledrectangle($img, 300, 30, 370, 100, $noir);
        imagefilledrectangle($img, 310, 40, 360, 90, $blanc);
        imagefilledrectangle($img, 320, 50, 350, 80, $noir);

        imagefilledrectangle($img, 30, 300, 100, 370, $noir);
        imagefilledrectangle($img, 40, 310, 90, 360, $blanc);
        imagefilledrectangle($img, 50, 320, 80, 350, $noir);

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return base64_encode($data);
    }
}