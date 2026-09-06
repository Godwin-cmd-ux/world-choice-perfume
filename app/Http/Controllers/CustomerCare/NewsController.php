<?php

namespace App\Http\Controllers\CustomerCare;

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

    public function index()
    {
        $posts = collect($this->supabase->query('news_posts', [
            'select' => '*, branch:branches(id,name), author:users(id,name)',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]))->map(function ($p) {
            if (isset($p['branch']) && is_array($p['branch'])) $p['branch'] = (object) $p['branch'];
            if (isset($p['author']) && is_array($p['author'])) $p['author'] = (object) $p['author'];
            return (object) $p;
        });

        return view('customer-care.news.index', ['posts' => $posts]);
    }

    public function create()
    {
        $branches = collect($this->supabase->query('branches', [
            'select' => 'id,name',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);

        return view('customer-care.news.create', ['branches' => $branches]);
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

        $this->supabase->insert('news_posts', [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'author_id' => $userId,
            'image_url' => $imageUrl,
            'is_published' => true,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('customer-care.news.index')->with('success', 'News post published successfully!');
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

        return view('customer-care.news.edit', ['post' => (object) $post, 'branches' => $branches]);
    }

    public function update(Request $request, $postId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'branch_id' => 'required',
        ]);

        $this->supabase->update('news_posts', [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'branch_id' => (int) $validated['branch_id'],
            'updated_at' => now()->toIso8601String(),
        ], ['id' => $postId]);

        return redirect()->route('customer-care.news.index')->with('success', 'Post updated successfully!');
    }

    public function destroy($postId)
    {
        $this->supabase->delete('news_posts', ['id' => $postId]);
        return redirect()->route('customer-care.news.index')->with('success', 'Post deleted.');
    }
}
