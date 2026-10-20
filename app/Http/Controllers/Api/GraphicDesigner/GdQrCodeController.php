<?php

namespace App\Http\Controllers\Api\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;

/**
 * JSON twin of GraphicDesigner\QrCodeController — both tabs of the website's
 * QR page.
 *
 * The website draws the codes in the browser (QRCodeJS canvas + Download/
 * Print buttons); the app has no canvas and cannot print, so the server
 * renders the same URLs with the same settings the Blade page uses (256px-
 * class PNG, ECC level H) and returns them as data: URIs the RN <Image> can
 * display directly. The active catalogue rides along so the product tab can
 * list perfumes, and `?product_id=` renders that product's own code — its
 * address is built server-side from the named route, never taken from the
 * client, so a printed code can never point at an address the app made up.
 * Download/Print remain browser-only actions on the website.
 */
class GdQrCodeController extends Controller
{
    /**
     * The public address a printed code has to keep working at (same host as
     * the website's QR page, which encodes the deployed site rather than the
     * host the designer happens to be browsing from).
     */
    private const PUBLIC_BASE_URL = 'https://worldchoiceperfume.com';

    public function qrCode(Request $request)
    {
        // Tab 2 lets a designer point a code straight at one perfume, so the
        // product picker needs the active catalogue. A Supabase hiccup must
        // not take the general tab down with it — that tab needs no data.
        try {
            $products = collect((new SupabaseService)->query('products', [
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
        } catch (\Throwable $e) {
            $products = collect();
        }

        $url = self::PUBLIC_BASE_URL.'/';

        // The product tab encodes that product's own order page. The id is
        // only ever matched against the catalogue above, so the client cannot
        // point a printed code at an address it invented.
        $productId = $request->input('product_id');
        $productId = is_scalar($productId) ? trim((string) $productId) : '';

        if ($productId !== '') {
            $product = $products->first(fn ($p) => (string) $p->id === $productId);

            if (! $product) {
                return response()->json([
                    'message' => 'That product is not in the active catalogue.',
                    'url' => null,
                    'image' => null,
                    'products' => $products,
                ], 404);
            }

            $url = $product->url;
        }

        try {
            $options = new QROptions;
            $options->outputInterface = QRGdImagePNG::class;
            $options->scale = 10;
            $options->eccLevel = EccLevel::H;
            $options->outputBase64 = true;

            $image = (new QRCode($options))->render($url);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'The QR code could not be generated right now.',
                'url' => $url,
                'image' => null,
                'products' => $products,
            ], 500);
        }

        return response()->json([
            'url' => $url,
            'image' => $image,
            'products' => $products,
        ]);
    }
}
