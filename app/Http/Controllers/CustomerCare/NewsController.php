<?php

namespace App\Http\Controllers\CustomerCare;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    private SupabaseService $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseService;
    }

    private function hasStatusColumn(): bool
    {
        return $this->supabase->tableHasColumn('news_posts', 'status');
    }

    private function reviewerId()
    {
        return auth()->user()->supabase_id ?? auth()->id();
    }

    private function loadPosts()
    {
        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 200,
        ]));

        // PHP-side joins (PostgREST expansions return 0 rows due to RLS/FK issues)
        $branchIds = $posts->pluck('branch_id')->filter()->unique()->values()->toArray();
        $authorIds = $posts->pluck('author_id')->filter()->unique()->values()->toArray();

        $branches = [];
        if (! empty($branchIds)) {
            $branchesList = $this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $branchIds).')',
            ]);
            foreach ($branchesList as $b) {
                $branches[$b['id']] = $b['name'];
            }
        }

        $authors = [];
        if (! empty($authorIds)) {
            $authorsList = $this->supabase->query('users', [
                'select' => 'id,name,role',
                'id' => 'in.('.implode(',', $authorIds).')',
            ]);
            foreach ($authorsList as $u) {
                $authors[$u['id']] = $u;
            }
        }

        return collect($posts)->map(function ($p) use ($branches, $authors) {
            $p['branch'] = isset($p['branch_id']) && isset($branches[$p['branch_id']])
                ? (object) ['id' => $p['branch_id'], 'name' => $branches[$p['branch_id']]] : null;

            $author = isset($p['author_id']) ? ($authors[$p['author_id']] ?? null) : null;
            $p['author'] = $author
                ? (object) ['id' => $author['id'], 'name' => $author['name'], 'role' => $author['role']] : null;

            $p['status'] = $this->normaliseStatus($p);
            $p['rejection_reason'] = $p['rejection_reason'] ?? null;

            return (object) $p;
        });
    }

    private function normaliseStatus(array $p): string
    {
        $raw = $p['status'] ?? null;
        if (in_array($raw, ['approved', 'rejected', 'pending'], true)) {
            return $raw;
        }

        return ($p['is_published'] ?? false) ? 'approved' : 'pending';
    }

    public function index(Request $request)
    {
        $posts = $this->loadPosts();

        $designerPosts = $posts->filter(fn ($p) => ($p->author->role ?? null) === 'graphic_designer')->values();
        $customPosts = $posts->reject(fn ($p) => ($p->author->role ?? null) === 'graphic_designer')->values();

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        $tab = in_array($request->query('tab'), ['designer', 'custom'], true) ? $request->query('tab') : 'designer';

        $counts = [
            'designer' => count($designerPosts),
            'custom' => count($customPosts),
            'pending' => $designerPosts->filter(fn ($p) => $p->status === 'pending')->count(),
            'rejected' => $designerPosts->filter(fn ($p) => $p->status === 'rejected')->count(),
            'approved' => $designerPosts->filter(fn ($p) => $p->status === 'approved')->count(),
        ];

        return view('customer-care.news.index', compact('designerPosts', 'customPosts', 'tab', 'counts', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
            'image' => 'nullable|image|max:51200',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $cloudinaryService = new CloudinaryService;
            $imageUrl = $cloudinaryService->upload($request->file('image'), 'news');
        }

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'author_id' => $this->reviewerId(),
            'image_url' => $imageUrl,
            'is_published' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'approved';
            $data['reviewed_by'] = $this->reviewerId();
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $this->supabase->insert('news_posts', $data);

        return redirect()->route('customer-care.news.index', ['tab' => 'custom'])
            ->with('success', 'Custom news published successfully.');
    }

    public function edit($postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404);
        }

        $post['status'] = $this->normaliseStatus($post);
        $post['rejection_reason'] = $post['rejection_reason'] ?? null;

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn ($b) => (object) $b);

        return view('customer-care.news.edit', ['post' => (object) $post, 'branches' => $branches]);
    }

    public function update(Request $request, $postId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
            'image' => 'nullable|image|max:51200',
        ]);

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'updated_at' => now()->toIso8601String(),
        ];

        if ($request->hasFile('image')) {
            $cloudinaryService = new CloudinaryService;
            $data['image_url'] = $cloudinaryService->upload($request->file('image'), 'news');
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return redirect()->route('customer-care.news.index')
            ->with('success', 'Post updated successfully.');
    }

    public function approve($postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404);
        }

        $data = [
            'is_published' => true,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'approved';
            $data['rejection_reason'] = null;
            $data['reviewed_by'] = $this->reviewerId();
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return redirect()->route('customer-care.news.index')
            ->with('success', "Post \"{$post['title']}\" approved and published.");
    }

    public function reject(Request $request, $postId)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404);
        }

        $data = [
            'is_published' => false,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'rejected';
            $data['rejection_reason'] = $validated['rejection_reason'];
            $data['reviewed_by'] = $this->reviewerId();
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return redirect()->route('customer-care.news.index')
            ->with('success', "Post \"{$post['title']}\" rejected.");
    }

    public function destroy($postId)
    {
        $post = $this->supabase->find('news_posts', $postId);

        $this->supabase->delete('news_posts', ['id' => $postId]);

        (new AuditService)->recordCriticalAction(
            'content_deleted',
            'news_post_deleted',
            'News Post Deleted',
            'News post deleted by customer care: '.($post['title'] ?? "#{$postId}").'.',
            ['post_id' => $postId, 'title' => $post['title'] ?? null],
            'news_posts',
            (string) $postId,
            ['title' => $post['title'] ?? null],
            []
        );

        return redirect()->route('customer-care.news.index')->with('success', 'Post deleted.');
    }
}
