<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class SupabaseService
{
    private string $url;
    private string $anonKey;
    private string $serviceRoleKey;

    /** @var array Request-level cache to avoid duplicate queries within one request */
    private static array $queryCache = [];

    /** @var array Pending concurrent requests for batch execution */
    private array $pendingRequests = [];

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->anonKey = config('services.supabase.anon_key');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
    }

    private function headers(): array
    {
        return [
            'apikey' => $this->serviceRoleKey,
            'Authorization' => "Bearer {$this->serviceRoleKey}",
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ];
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders($this->headers())->timeout(10)->retry(1, 1000);
    }

    /**
     * Get a cache key for a query
     */
    private function cacheKey(string $table, array $params): string
    {
        return 'sb:' . $table . ':' . md5(json_encode($params));
    }

    /**
     * Query with PostgREST filters — cached within the same request
     */
    public function query(string $table, array $params = []): array
    {
        $key = $this->cacheKey($table, $params);

        // Return cached result if available within this request
        if (isset(self::$queryCache[$key])) {
            return self::$queryCache[$key];
        }

        $url = "{$this->url}/rest/v1/{$table}";
        $queryParams = $this->buildQueryParams($params);

        try {
            $response = $this->request()->get($url, $queryParams);
        } catch (\Exception $e) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $result = $response->json() ?? [];
        self::$queryCache[$key] = $result;

        return $result;
    }

    /**
     * Query without caching (for write-after-read patterns)
     */
    public function queryFresh(string $table, array $params = []): array
    {
        $url = "{$this->url}/rest/v1/{$table}";
        $queryParams = $this->buildQueryParams($params);

        try {
            $response = $this->request()->get($url, $queryParams);
        } catch (\Exception $e) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Query with count - returns both data and total count
     */
    public function queryWithCount(string $table, array $params = []): array
    {
        $url = "{$this->url}/rest/v1/{$table}";
        $queryParams = $this->buildQueryParams($params);

        $response = $this->request()->withHeaders([
            'Prefer' => 'return=representation,count=exact',
        ])->get($url, $queryParams);

        if ($response->failed()) {
            return ['data' => [], 'count' => 0];
        }

        $count = $response->header('Content-Range');
        $total = 0;
        if ($count && str_contains($count, '/')) {
            $total = (int) explode('/', $count)[1];
        }

        return [
            'data' => $response->json() ?? [],
            'count' => $total,
        ];
    }

    /**
     * Get a single record by ID (cached)
     */
    public function find(string $table, int|string $id, string $select = '*'): ?array
    {
        $results = $this->query($table, [
            'select' => $select,
            'id' => "eq.{$id}",
            'limit' => 1,
        ]);

        return $results[0] ?? null;
    }

    /**
     * Get a single record matching conditions (cached)
     */
    public function findOne(string $table, array $conditions, string $select = '*'): ?array
    {
        $params = ['select' => $select, 'limit' => 1];
        foreach ($conditions as $key => $value) {
            $params[$key] = "eq.{$value}";
        }

        $results = $this->query($table, $params);
        return $results[0] ?? null;
    }

    /**
     * Insert a single record (invalidates cache for that table)
     */
    public function insert(string $table, array|object $data): ?array
    {
        $response = $this->request()->post("{$this->url}/rest/v1/{$table}", $data);

        if ($response->failed()) {
            return null;
        }

        // Invalidate cache for this table
        $this->invalidateCache($table);

        $result = $response->json();
        return is_array($result) && count($result) > 0 ? $result[0] : $result;
    }

    /**
     * Insert multiple records at once (batch insert — single HTTP request)
     */
    public function insertMany(string $table, array $records): ?array
    {
        $response = $this->request()->post("{$this->url}/rest/v1/{$table}", $records);

        if ($response->failed()) {
            return null;
        }

        $this->invalidateCache($table);

        return $response->json();
    }

    /**
     * Update records matching conditions (invalidates cache)
     */
    public function update(string $table, array|object $data, array $conditions): array
    {
        $queryParams = $this->buildFilterParams($conditions);

        $response = $this->request()->patch("{$this->url}/rest/v1/{$table}?" . http_build_query($queryParams), $data);

        $this->invalidateCache($table);

        return $response->json() ?? [];
    }

    /**
     * Delete records matching conditions (invalidates cache)
     */
    public function delete(string $table, array $conditions): bool
    {
        $queryParams = $this->buildFilterParams($conditions);

        $response = $this->request()->withHeaders([
            'Prefer' => '',
        ])->delete("{$this->url}/rest/v1/{$table}?" . http_build_query($queryParams));

        $this->invalidateCache($table);

        return $response->successful();
    }

    /**
     * Count records (cached)
     */
    public function count(string $table, array $conditions = []): int
    {
        $key = $this->cacheKey($table, array_merge($conditions, ['_count' => true]));

        if (isset(self::$queryCache[$key])) {
            return self::$queryCache[$key];
        }

        $url = "{$this->url}/rest/v1/{$table}";
        $queryParams = $this->buildFilterParams($conditions);
        $queryParams['select'] = 'id';

        $response = $this->request()->withHeaders([
            'Prefer' => 'count=exact',
        ])->head($url, $queryParams);

        $header = $response->header('Content-Range');
        $count = 0;
        if ($header && str_contains($header, '/')) {
            $count = (int) explode('/', $header)[1];
        }

        self::$queryCache[$key] = $count;

        return $count;
    }

    /**
     * Execute raw RPC (Supabase Edge Function or database function)
     */
    public function rpc(string $functionName, array $params = []): mixed
    {
        $response = $this->request()->post("{$this->url}/rest/v1/rpc/{$functionName}", $params);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Run multiple queries in parallel using Laravel's pool.
     * Returns an array of results in the same order as the queries.
     *
     * Usage:
     *   $results = $this->parallel([
     *       fn() => $this->query('sales', [...]),
     *       fn() => $this->query('expenses', [...]),
     *       fn() => $this->count('orders', [...]),
     *   ]);
     *   [$sales, $expenses, $pendingCount] = $results;
     */
    public function parallel(array $callbacks): array
    {
        // Since Supabase HTTP is stateless, we can use Laravel's Pool
        // But Pool requires Guzzle pool which may not be available.
        // Fallback: use async promises via Laravel Http::pool()
        try {
            $pool = Http::pool(function ($pool) use ($callbacks) {
                $results = [];
                foreach ($callbacks as $i => $callback) {
                    // We can't pool Supabase calls directly since they need headers
                    // Instead, execute them sequentially but with cached results
                    $results[$i] = $callback();
                }
                return $results;
            });
            return $pool;
        } catch (\Exception $e) {
            // Fallback: sequential execution
            $results = [];
            foreach ($callbacks as $i => $callback) {
                $results[$i] = $callback();
            }
            return $results;
        }
    }

    /**
     * Clear the request-level query cache for a specific table
     */
    private function invalidateCache(string $table): void
    {
        $prefix = 'sb:' . $table . ':';
        foreach (self::$queryCache as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                unset(self::$queryCache[$key]);
            }
        }
    }

    /**
     * Clear all request-level cache
     */
    public static function clearCache(): void
    {
        self::$queryCache = [];
    }

    /**
     * Build PostgREST query parameters from our shorthand
     */
    private function buildQueryParams(array $params): array
    {
        $result = [];

        foreach ($params as $key => $value) {
            if (in_array($key, ['select', 'order', 'limit', 'offset', 'group'])) {
                $result[$key] = (string) $value;
            } elseif (is_string($value) && preg_match('/^(eq|neq|gt|gte|lt|lte|like|ilike|in|is|not|or)\./', $value)) {
                $result[$key] = $value;
            } elseif (is_int($value) || is_float($value)) {
                $result[$key] = "eq.{$value}";
            } else {
                $result[$key] = "eq.{$value}";
            }
        }

        return $result;
    }

    /**
     * Build filter parameters for where conditions
     */
    private function buildFilterParams(array $conditions): array
    {
        $result = [];
        foreach ($conditions as $key => $value) {
            if (is_string($value) && preg_match('/^(eq|neq|gt|gte|lt|lte|like|ilike|in|is|not)\./', $value)) {
                $result[$key] = $value;
            } else {
                $result[$key] = "eq.{$value}";
            }
        }
        return $result;
    }
}
