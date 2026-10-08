<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }

    public function index(Request $request)
    {
        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'is_published' => 'eq.true',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]));

        // PHP-side branch join (PostgREST branch:branches expansion returns 0 rows)
        $branchIds = $posts->pluck('branch_id')->filter()->unique()->values()->toArray();
        $branches = [];
        if (!empty($branchIds)) {
            $branchesList = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $branchIds) . ')',
            ]);
            foreach ($branchesList as $b) {
                $branches[$b['id']] = $b['name'];
            }
        }

        $posts = $posts->map(function ($p) use ($branches) {
            $p['branch'] = isset($p['branch_id']) && isset($branches[$p['branch_id']])
                ? (object) ['id' => $p['branch_id'], 'name' => $branches[$p['branch_id']]]
                : null;
            return (object) $p;
        });

        // The website renders the Blade page; the mobile app asks for the
        // same posts as JSON (GET /api/news) without a second query path.
        if ($request->expectsJson()) {
            return response()->json(['posts' => $posts->values()]);
        }

        return view('customer.news', ['posts' => $posts]);
    }
}
