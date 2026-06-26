<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class QRCodeService
{
    public function genererBase64(string $contenu): string
    {
        // Essayer l'API externe
        try {
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($contenu);
            $context = stream_context_create([
                'http' => [
                    'timeout'     => 8,
                    'user_agent'  => 'EventSecure/1.0',
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $imageData = @file_get_contents($url, false, $context);

            if ($imageData !== false && strlen($imageData) > 100) {
                return base64_encode($imageData);
            }
        } catch (\Exception $e) {
            Log::warning('API QR Code échouée : ' . $e->getMessage());
        }

        // Fallback GD
        return $this->genererAvecGD($contenu);
    }

    public function genererDataUri(string $contenu): string
    {
        return 'data:image/png;base64,' . $this->genererBase64($contenu);
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