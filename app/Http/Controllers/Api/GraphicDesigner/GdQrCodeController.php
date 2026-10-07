<?php

namespace App\Http\Controllers\Api\GraphicDesigner;

use App\Http\Controllers\Controller;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * JSON twin of GraphicDesigner\QrCodeController. The website renders the QR
 * in the browser (QRCodeJS canvas + Download/Print buttons); the app has no
 * canvas, so the server renders the same URL with the same settings the
 * Blade page uses (256px-class PNG, ECC level H) and returns it as a
 * data: URI the RN <Image> can display directly. Download/Print remain
 * browser-only actions on the website.
 */
class GdQrCodeController extends Controller
{
    private const QR_URL = 'https://world-choice-perfume.onrender.com/';

    public function qrCode()
    {
        try {
            $options = new QROptions;
            $options->outputInterface = QRGdImagePNG::class;
            $options->scale = 10;
            $options->eccLevel = EccLevel::H;
            $options->outputBase64 = true;

            $image = (new QRCode($options))->render(self::QR_URL);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'The QR code could not be generated right now.',
                'url' => self::QR_URL,
                'image' => null,
            ], 500);
        }

        return response()->json([
            'url' => self::QR_URL,
            'image' => $image,
        ]);
    }
}
