<?php

namespace App\Http\Controllers\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Exception;

class QrCodeController extends Controller
{
    /**
     * The public address a printed code has to keep working at.
     *
     * A QR code leaves the browser the moment it is generated — it is printed,
     * stickered onto a bottle or a shelf and scanned months later, on a phone
     * that has never seen this site. Encoding the host the designer happened to
     * be browsing from would bake a localhost (or a preview) address into the
     * label, so both tabs encode the deployed site instead.
     */
    private const PUBLIC_BASE_URL = 'https://world-choice-perfume.onrender.com';

    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function qrCode()
    {
        // Tab 2 lets a designer point a code straight at one perfume, so the
        // product picker needs the active catalogue. A Supabase hiccup must not
        // take the general tab down with it — that tab needs no data at all.
        try {
            $products = collect($this->supabase->query('products', [
                'select' => 'id,name,brand,category,sex_category',
                'is_active' => 'eq.true',
                'order' => 'name.asc',
            ]))->map(fn ($product) => (object) [
                'id' => $product['id'],
                'name' => $product['name'],
                'brand' => $product['brand'] ?? null,
                'category' => $product['category'] ?? null,
                // Built from the named route, so a change to the product URL
                // cannot silently leave printed codes pointing at a 404.
                'url' => self::PUBLIC_BASE_URL.route('customer.products.show', $product['id'], false),
            ])->values();
        } catch (Exception $e) {
            $products = collect();
        }

        return view('graphic-designer.qr-code', [
            'url' => self::PUBLIC_BASE_URL.'/',
            'products' => $products,
        ]);
    }
}
