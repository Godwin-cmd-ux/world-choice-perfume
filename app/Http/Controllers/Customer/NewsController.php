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

    /**
     * One published post, read whole — every card on the news page links here.
     *
     * The website renders the Blade detail page; the app asks for JSON
     * (GET /api/news/{post}) off the same read, so both always show the same
     * post. An unpublished or unknown id is a 404 on either side: the list only
     * ever links published ids, and a stale link must not surface a post the
     * news page no longer shows.
     */
    public function show(Request $request, $post)
    {
        $id = (string) $post;
        if (! ctype_digit($id)) {
            abort(404);
        }

        $row = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'id' => 'eq.' . $id,
            'is_published' => 'eq.true',
            'limit' => 1,
        ]))->first();

        if (! $row) {
            abort(404);
        }

        // Same PHP-side branch join as the list.
        $row['branch'] = null;
        if (! empty($row['branch_id'])) {
            $branch = collect($this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'eq.' . $row['branch_id'],
            ]))->first();
            if ($branch) {
                $row['branch'] = (object) ['id' => $branch['id'], 'name' => $branch['name']];
            }
        }

        $post = (object) $row;

        if ($request->expectsJson()) {
            return response()->json(['post' => $post]);
        }

        return view('customer.news-show', ['post' => $post]);
    }
}
