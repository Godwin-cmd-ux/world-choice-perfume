<?php

namespace App\Http\Controllers;

use App\Services\NavigationCountsService;
use Illuminate\Http\JsonResponse;

class LiveCountsController extends Controller
{
    public function __construct(private NavigationCountsService $counts) {}

    /**
     * The sidebar counts for the signed-in staff member. Polled by the live
     * update script, so it returns only numbers — never records.
     */
    public function index(): JsonResponse
    {
        return response()
            ->json([
                'counts' => $this->counts->forUser(auth()->user()),
                'at' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
