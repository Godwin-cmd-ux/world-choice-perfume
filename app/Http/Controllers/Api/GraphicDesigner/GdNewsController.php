<?php

namespace App\Http\Controllers\Api\GraphicDesigner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStaffSessionApi;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use App\Services\SupabaseService;
use App\Support\BranchAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * JSON twin of GraphicDesigner\NewsController — the same author-scoped news
 * posts, HQ-branch assignment, Cloudinary upload and approval lifecycle
 * (pending on submit, pending again on edit, audit entry on delete); only
 * the responses differ: JSON messages instead of Blade redirects.
 */
class GdNewsController extends Controller
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

    private const HQ_BRANCH_NAME = BranchAccess::HEAD_QUARTERS;

    private function hqBranchId(): ?int
    {
        $rows = $this->supabase->query('branches', [
            'select' => 'id,name',
        ]);

        foreach ($rows as $branch) {
            if (mb_strtolower(trim($branch['name'] ?? '')) === mb_strtolower(trim(self::HQ_BRANCH_NAME))) {
                return (int) $branch['id'];
            }
        }

        return null;
    }

    private function normaliseStatus(array $p): string
    {
        $raw = $p['status'] ?? null;
        if (in_array($raw, ['approved', 'rejected', 'pending'], true)) {
            return $raw;
        }

        return ($p['is_published'] ?? false) ? 'approved' : 'pending';
    }

    private function authorId(Request $request): ?string
    {
        $sessionUser = EnsureStaffSessionApi::user($request);

        return isset($sessionUser['id']) ? (string) $sessionUser['id'] : null;
    }

    /** Own posts with branch names + the same counts the Blade index shows. */
    public function index(Request $request)
    {
        $userId = $this->authorId($request);

        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*',
            'author_id' => "eq.{$userId}",
            'order' => 'created_at.desc',
            'limit' => 100,
        ]));

        // PHP-side joins (PostgREST expansions return 0 rows due to RLS/FK issues)
        $branchIds = $posts->pluck('branch_id')->filter()->unique()->values()->toArray();

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

        $posts = $posts->map(function ($p) use ($branches) {
            $p['branch'] = isset($p['branch_id']) && isset($branches[$p['branch_id']])
                ? (object) ['id' => $p['branch_id'], 'name' => $branches[$p['branch_id']]] : null;
            $p['status'] = $this->normaliseStatus($p);
            $p['rejection_reason'] = $p['rejection_reason'] ?? null;

            return (object) $p;
        });

        $counts = [
            'total' => $posts->count(),
            'approved' => $posts->filter(fn ($p) => $p->status === 'approved')->count(),
            'pending' => $posts->filter(fn ($p) => $p->status === 'pending')->count(),
            'rejected' => $posts->filter(fn ($p) => $p->status === 'rejected')->count(),
            'today' => $posts->filter(function ($p) {
                if (! $p->created_at) {
                    return false;
                }

                return Carbon::parse($p->created_at)->setTimezone('Africa/Dar_es_Salaam')->isToday();
            })->count(),
        ];

        return response()->json(['posts' => $posts, 'counts' => $counts]);
    }

    /** Single post for the mobile edit form (404 when missing). */
    public function show(Request $request, $postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (! $post || (string) ($post['author_id'] ?? '') !== $this->authorId($request)) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $post['status'] = $this->normaliseStatus($post);
        $post['rejection_reason'] = $post['rejection_reason'] ?? null;

        return response()->json(['post' => (object) $post]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
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
            'branch_id' => $this->hqBranchId(),
            'author_id' => $this->authorId($request),
            'image_url' => $imageUrl,
            'is_published' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'pending';
        }

        $post = $this->supabase->insert('news_posts', $data);

        return response()->json([
            'message' => 'Post submitted for approval. It will appear on the site once approved by customer care.',
            'post' => $post,
        ], 201);
    }

    public function update(Request $request, $postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (! $post || (string) ($post['author_id'] ?? '') !== $this->authorId($request)) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_published' => false,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($this->hasStatusColumn()) {
            $data['status'] = 'pending';
            $data['rejection_reason'] = null;
        }

        $this->supabase->update('news_posts', $data, ['id' => $postId]);

        return response()->json(['message' => 'Post updated and re-submitted for approval.']);
    }

    public function destroy(Request $request, $postId)
    {
        $post = $this->supabase->find('news_posts', $postId);
        if (! $post || (string) ($post['author_id'] ?? '') !== $this->authorId($request)) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $this->supabase->delete('news_posts', ['id' => $postId]);

        (new AuditService)->recordCriticalAction(
            'content_deleted',
            'news_post_deleted',
            'News Post Deleted',
            'News post deleted: '.($post['title'] ?? "#{$postId}").'.',
            ['post_id' => $postId, 'title' => $post['title'] ?? null],
            'news_posts',
            (string) $postId,
            ['title' => $post['title'] ?? null],
            []
        );

        return response()->json(['message' => 'Post deleted.']);
    }
}
