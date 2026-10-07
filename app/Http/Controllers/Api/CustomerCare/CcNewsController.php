<?php

namespace App\Http\Controllers\Api\CustomerCare;

use App\Services\AuditService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

/**
 * JSON twin of the customer-care News moderation screens — Head
 * Quarters-Mikocheni only. Two lists (designer posts awaiting review and
 * custom posts the team writes itself), approve/reject with a reason, and
 * the same immediate-publish rule for custom posts the website follows.
 */
class CcNewsController extends CcBaseController
{
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

    private function loadPosts()
    {
        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'order' => 'created_at.desc',
            'limit' => 200,
        ]));

        // PHP-side joins (PostgREST expansions return 0 rows).
        $branchIds = $posts->pluck('branch_id')->filter()->unique()->values()->toArray();
        $authorIds = $posts->pluck('author_id')->filter()->unique()->values()->toArray();

        $branches = [];
        if (! empty($branchIds)) {
            foreach ($this->supabase->query('branches', [
                'select' => 'id,name',
                'id' => 'in.('.implode(',', $branchIds).')',
            ]) as $b) {
                $branches[$b['id']] = $b['name'];
            }
        }

        $authors = [];
        if (! empty($authorIds)) {
            foreach ($this->supabase->query('users', [
                'select' => 'id,name,role',
                'id' => 'in.('.implode(',', $authorIds).')',
            ]) as $u) {
                $authors[$u['id']] = $u;
            }
        }

        return collect($posts)->map(function ($p) use ($branches, $authors) {
            $p = (array) $p;
            $p['branch'] = isset($p['branch_id']) && isset($branches[$p['branch_id']])
                ? ['id' => $p['branch_id'], 'name' => $branches[$p['branch_id']]] : null;

            $author = isset($p['author_id']) ? ($authors[$p['author_id']] ?? null) : null;
            $p['author'] = $author
                ? ['id' => $author['id'], 'name' => $author['name'], 'role' => $author['role']] : null;

            $p['status'] = $this->normaliseStatus($p);
            $p['rejection_reason'] = $p['rejection_reason'] ?? null;

            return $p;
        });
    }

    public function index(Request $request)
    {
        $this->assertHq($request);

        $posts = $this->loadPosts();

        $designerPosts = $posts->filter(fn ($p) => ($p['author']['role'] ?? null) === 'graphic_designer')->values();
        $customPosts = $posts->reject(fn ($p) => ($p['author']['role'] ?? null) === 'graphic_designer')->values();

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->values()->all();

        $tab = in_array($request->query('tab'), ['designer', 'custom'], true) ? $request->query('tab') : 'designer';

        $counts = [
            'designer' => count($designerPosts),
            'custom' => count($customPosts),
            'pending' => $designerPosts->filter(fn ($p) => $p['status'] === 'pending')->count(),
            'rejected' => $designerPosts->filter(fn ($p) => $p['status'] === 'rejected')->count(),
            'approved' => $designerPosts->filter(fn ($p) => $p['status'] === 'approved')->count(),
        ];

        return response()->json([
            'designerPosts' => $designerPosts->all(),
            'customPosts' => $customPosts->all(),
            'tab' => $tab,
            'counts' => $counts,
            'branches' => $branches,
            'scope' => $this->scopePayload($request),
        ]);
    }

    /** Create a custom post — published immediately, exactly like the website. */
    public function store(Request $request)
    {
        $this->assertHq($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
            'image' => 'nullable|image|max:51200',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = (new CloudinaryService)->upload($request->file('image'), 'news');
        }

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'author_id' => $this->performingUserId($request),
            'image_url' => $imageUrl,
            'is_published' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'approved';
            $data['reviewed_by'] = $this->performingUserId($request);
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $post = $this->supabase->insert('news_posts', $data);

        return response()->json([
            'message' => 'Custom news published successfully.',
            'post' => $post,
        ]);
    }

    /** The edit form's data: the post plus the active branches. */
    public function edit(Request $request, int $postId)
    {
        $this->assertHq($request);

        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404, 'Post not found.');
        }

        $post['status'] = $this->normaliseStatus($post);
        $post['rejection_reason'] = $post['rejection_reason'] ?? null;

        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->values()->all();

        return response()->json([
            'post' => $post,
            'branches' => $branches,
        ]);
    }

    public function update(Request $request, int $postId)
    {
        $this->assertHq($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
            'image' => 'nullable|image|max:51200',
        ]);

        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404, 'Post not found.');
        }

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'updated_at' => now()->toIso8601String(),
        ];

        if ($request->hasFile('image')) {
            $data['image_url'] = (new CloudinaryService)->upload($request->file('image'), 'news');
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return response()->json(['message' => 'Post updated successfully.']);
    }

    public function approve(Request $request, int $postId)
    {
        $this->assertHq($request);

        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404, 'Post not found.');
        }

        $data = [
            'is_published' => true,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'approved';
            $data['rejection_reason'] = null;
            $data['reviewed_by'] = $this->performingUserId($request);
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return response()->json(['message' => 'Post "'.$post['title'].'" approved and published.']);
    }

    public function reject(Request $request, int $postId)
    {
        $this->assertHq($request);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $post = $this->supabase->find('news_posts', $postId);
        if (! $post) {
            abort(404, 'Post not found.');
        }

        $data = [
            'is_published' => false,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'rejected';
            $data['rejection_reason'] = $validated['rejection_reason'];
            $data['reviewed_by'] = $this->performingUserId($request);
            $data['reviewed_at'] = now()->toIso8601String();
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return response()->json(['message' => 'Post "'.$post['title'].'" rejected.']);
    }

    public function destroy(Request $request, int $postId)
    {
        $this->assertHq($request);

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

        return response()->json(['message' => 'Post deleted.']);
    }
}
