<?php

namespace App\Http\Controllers\GraphicDesigner;

use App\Http\Controllers\Controller;

class QrCodeController extends Controller
{
    public function qrCode()
    {
        $url = 'https://world-choice-perfume.onrender.com/';
        return view('graphic-designer.qr-code', ['url' => $url]);
    }
}
