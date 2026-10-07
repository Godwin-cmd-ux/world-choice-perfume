<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Exception;

/**
 * JSON payload for the World Choice Perfume mobile app.
 *
 * Mirrors the data the public homepage (routes/web.php, `/`) already builds
 * from Supabase so the app shows the same branches, featured brands, category
 * images, reviews and contact details as the website. The queries and the
 * graceful empty-on-failure behaviour are the same as the homepage; nothing
 * here writes to the database, and the contact block comes from the same
 * config the website prints (config/contact.php, config/info_mail.php).
 */
class HomeController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    public function index()
    {
        $categoryImages = [];
        foreach (['male', 'female', 'unisex', 'accessories'] as $cat) {
            $categoryImages[$cat] = [];
        }

        $branches = collect();
        $remarks = collect();
        $featuredBrands = collect();

        try {
            // Same branch query as the homepage — every active branch.
            $branches = collect($this->supabase->query('branches', [
                'select' => 'id,name,address,latitude,longitude,is_active,profile_picture',
                'is_active' => 'eq.true',
                'order' => 'name.asc',
            ]))->map(fn ($b) => (object) $b)->values();

            $remarks = collect($this->supabase->query('inquiries', [
                'select' => 'email,subject,message,created_at',
                'is_featured' => 'eq.true',
                'order' => 'created_at.desc',
                'limit' => 6,
            ]))->map(fn ($r) => (object) $r);

            // Featured brands — the first 12 active brands, as on the homepage.
            $featuredBrands = collect($this->supabase->query('brands', [
                'select' => 'id,name,logo_url',
                'is_active' => 'eq.true',
                'order' => 'name.asc',
                'limit' => 12,
            ]))->map(fn ($b) => (object) $b);

            // Product images per sex category, for the category cards.
            $categoryProducts = collect($this->supabase->query('products', [
                'select' => 'sex_category,images:product_images(*)',
                'is_active' => 'eq.true',
                'order' => 'created_at.desc',
            ]));
            foreach ($categoryProducts as $p) {
                $cat = strtolower(trim($p['sex_category'] ?? ''));
                if (! isset($categoryImages[$cat])) {
                    continue;
                }
                foreach ($p['images'] ?? [] as $img) {
                    if (! empty($img['image_url'])) {
                        $categoryImages[$cat][] = $img['image_url'];
                    }
                }
            }
        } catch (Exception $e) {
            // Same graceful fallback as the homepage: empty lists, not a 500.
            $branches = collect();
            $remarks = collect();
            $featuredBrands = collect();
        }

        return response()->json([
            'branches' => $branches->values(),
            'reviews' => $remarks->values(),
            'featured_brands' => $featuredBrands->values(),
            'category_images' => $categoryImages,
            'contact' => [
                'phone' => config('contact.phone'),
                'dial' => config('contact.dial'),
                'whatsapp' => config('contact.whatsapp'),
                'whatsapp_link' => config('contact.whatsapp_link'),
                'hours' => config('contact.hours'),
                'email' => config('info_mail.address'),
            ],
        ]);
    }
}
