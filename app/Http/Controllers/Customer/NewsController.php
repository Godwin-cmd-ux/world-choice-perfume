<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;

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
            'select' => '*, branch:branches(id,name)',
            'is_published' => 'eq.true',
            'order' => 'created_at.desc',
            'limit' => 50,
        ]))->map(function ($p) {
            if (isset($p['branch']) && is_array($p['branch'])) $p['branch'] = (object) $p['branch'];
            return (object) $p;
        });

        return view('customer.news', ['posts' => $posts]);
    }
}
