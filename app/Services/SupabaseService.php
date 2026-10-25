<?php

namespace App\Services;

/**
 * Data access for the whole application.
 *
 * The app was written against Supabase's PostgREST API, and this class still
 * exposes exactly that surface (query/find/insert/update/delete/count plus the
 * storage helpers), so no call site had to change when the data moved home.
 *
 * Which engine answers is decided once per instance:
 *
 *  - SUPABASE_URL configured  → SupabaseRestService (HTTP/PostgREST).
 *  - no SUPABASE_URL          → LocalPostgresService (the local database
 *                               configured by DB_* in .env).
 *
 * With the Supabase credentials removed from .env every read and write goes to
 * the local Postgres database through the normal Laravel connection.
 */
class SupabaseService
{
    private SupabaseRestService|LocalPostgresService|null $driver = null;

    public function __construct()
    {
    }

    private function driver(): SupabaseRestService|LocalPostgresService
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $url = (string) config('services.supabase.url');
        $key = (string) config('services.supabase.service_role_key');

        return $this->driver = ($url !== '' && $key !== '')
            ? new SupabaseRestService()
            : new LocalPostgresService();
    }

    public function query(string $table, array $params = []): array
    {
        return $this->driver()->query($table, $params);
    }

    public function queryFresh(string $table, array $params = []): array
    {
        return $this->driver()->queryFresh($table, $params);
    }

    public function queryWithCount(string $table, array $params = []): array
    {
        return $this->driver()->queryWithCount($table, $params);
    }

    public function tableHasColumn(string $table, string $column): bool
    {
        return $this->driver()->tableHasColumn($table, $column);
    }

    public function find(string $table, int|string $id, string $select = '*'): ?array
    {
        return $this->driver()->find($table, $id, $select);
    }

    public function findOne(string $table, array $conditions, string $select = '*'): ?array
    {
        return $this->driver()->findOne($table, $conditions, $select);
    }

    public function insert(string $table, array|object $data): ?array
    {
        return $this->driver()->insert($table, $data);
    }

    public function insertOrFail(string $table, array|object $data): array
    {
        return $this->driver()->insertOrFail($table, $data);
    }

    public function insertMany(string $table, array $records): ?array
    {
        return $this->driver()->insertMany($table, $records);
    }

    public function insertManyOrFail(string $table, array $records): array
    {
        return $this->driver()->insertManyOrFail($table, $records);
    }

    public function update(string $table, array|object $data, array $conditions): array
    {
        return $this->driver()->update($table, $data, $conditions);
    }

    public function updateOrFail(string $table, array|object $data, array $conditions): array
    {
        return $this->driver()->updateOrFail($table, $data, $conditions);
    }

    public function delete(string $table, array $conditions): bool
    {
        return $this->driver()->delete($table, $conditions);
    }

    public function count(string $table, array $conditions = []): int
    {
        return $this->driver()->count($table, $conditions);
    }

    public function rpc(string $functionName, array $params = []): mixed
    {
        return $this->driver()->rpc($functionName, $params);
    }

    public function parallel(array $callbacks): array
    {
        return $this->driver()->parallel($callbacks);
    }

    public function lastErrorMessage(): ?string
    {
        return $this->driver()->lastErrorMessage();
    }

    public function ensureStorageBucket(string $bucket): bool
    {
        return $this->driver()->ensureStorageBucket($bucket);
    }

    public function storagePut(string $bucket, string $path, string $contents, string $contentType): bool
    {
        return $this->driver()->storagePut($bucket, $path, $contents, $contentType);
    }

    public function storageGet(string $bucket, string $path): ?string
    {
        return $this->driver()->storageGet($bucket, $path);
    }

    public function storageDownloadTo(string $bucket, string $path, string $destination): bool
    {
        return $this->driver()->storageDownloadTo($bucket, $path, $destination);
    }

    public function storageDelete(string $bucket, string $path): bool
    {
        return $this->driver()->storageDelete($bucket, $path);
    }

    /**
     * Clear the request-level caches of both engines.
     */
    public static function clearCache(): void
    {
        SupabaseRestService::clearCache();
        LocalPostgresService::clearCache();
    }
}
