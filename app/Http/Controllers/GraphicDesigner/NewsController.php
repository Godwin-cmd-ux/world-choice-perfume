<?php

namespace App\Http\Controllers\GraphicDesigner;

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

    private function hasStatusColumn(): bool
    {
        return $this->supabase->tableHasColumn('news_posts', 'status');
    }

    private function normaliseStatus(array $p): string
    {
        $raw = $p['status'] ?? null;
        if (in_array($raw, ['approved', 'rejected', 'pending'], true)) {
            return $raw;
        }
        return ($p['is_published'] ?? false) ? 'approved' : 'pending';
    }

    public function index()
    {
        $userId = auth()->user()->supabase_id ?? auth()->id();

        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'author_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]));

        // PHP-side joins (PostgREST expansions return 0 rows due to RLS/FK issues)
        $branchIds = $posts->pluck('branch_id')->filter()->unique()->values()->toArray();

        $branches = [];
        if (!empty($branchIds)) {
            $branchesList = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.(' . implode(',', $branchIds) . ')',
            ]);
            foreach ($branchesList as $b) $branches[$b['id']] = $b['name'];
        }

        $posts = $posts->map(function ($p) use ($branches) {
            $p['branch'] = isset($p['branch_id']) && isset($branches[$p['branch_id']])
                ? (object) ['id' => $p['branch_id'], 'name' => $branches[$p['branch_id']]] : null;
            $p['status'] = $this->normaliseStatus($p);
            $p['rejection_reason'] = $p['rejection_reason'] ?? null;
            return (object) $p;
        });

        $counts = [
            'total' => $posts->count(),
            'approved' => $posts->filter(fn($p) => $p->status === 'approved')->count(),
            'pending' => $posts->filter(fn($p) => $p->status === 'pending')->count(),
            'rejected' => $posts->filter(fn($p) => $p->status === 'rejected')->count(),
            'today' => $posts->filter(function ($p) {
                if (!$p->created_at) return false;
                return \Carbon\Carbon::parse($p->created_at)->setTimezone('Africa/Dar_es_Salaam')->isToday();
            })->count(),
        ];

        return view('graphic-designer.news.index', ['posts' => $posts, 'counts' => $counts]);
    }

    public function create()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        return view('graphic-designer.news.create', ['branches' => $branches]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
            'image' => 'nullable|image|max:2048',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $cloudinaryService = new \App\Services\CloudinaryService();
            $imageUrl = $cloudinaryService->upload($request->file('image'), 'news');
        }

        $userId = auth()->user()->supabase_id ?? auth()->id();

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'author_id' => $userId,
            'image_url' => $imageUrl,
            'is_published' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'pending';
        }

        $this->supabase->insert('news_posts', $data);

        return redirect()->route('graphic-designer.news.index')
            ->with('success', 'Post submitted for approval. It will appear on the site once approved by customer care.');
    }

    public function edit($postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (!$post) abort(404);

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        return view('graphic-designer.news.edit', ['post' => (object) $post, 'branches' => $branches]);
    }

    public function update(Request $request, $postId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
        ]);

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'is_published' => false,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'pending';
            $data['rejection_reason'] = null;
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return redirect()->route('graphic-designer.news.index')
            ->with('success', 'Post updated and re-submitted for approval.');
    }

    public function destroy($postId)
    {
        $post = $this->supabase->find('news_posts', $postId);

        $this->supabase->delete('news_posts', ['id' => $postId]);

        (new \App\Services\AuditService())->recordCriticalAction(
            'content_deleted',
            'news_post_deleted',
            'News Post Deleted',
            "News post deleted: " . ($post['title'] ?? "#{$postId}") . ".",
            ['post_id' => $postId, 'title' => $post['title'] ?? null],
            'news_posts',
            (string) $postId,
            ['title' => $post['title'] ?? null],
            []
        );

        return redirect()->route('graphic-designer.news.index')->with('success', 'Post deleted.');
    }
}