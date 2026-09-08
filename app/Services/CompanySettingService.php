<?php

namespace App\Services;

class CompanySettingService
{
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $supabase = app(SupabaseService::class);
        $row = $supabase->findOne('company_settings', ['key' => $key], 'key,value');
        $value = $row['value'] ?? $default;

        self::$cache[$key] = $value;

        return $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $supabase = app(SupabaseService::class);
        $existing = $supabase->findOne('company_settings', ['key' => $key], 'key');

        $payload = ['key' => $key, 'value' => $value, 'updated_at' => now()->toIso8601String()];

        if ($existing) {
            $supabase->update('company_settings', ['value' => $value, 'updated_at' => now()->toIso8601String()], ['key' => $key]);
        } else {
            $supabase->insert('company_settings', $payload);
        }

        self::$cache[$key] = $value;
    }

    public static function getAll(): array
    {
        if (!empty(self::$cache) && count(self::$cache) >= 2) {
            return self::$cache;
        }

        $supabase = app(SupabaseService::class);
        $rows = $supabase->query('company_settings', ['select' => '*']);

        foreach ($rows as $row) {
            self::$cache[$row['key']] = $row['value'] ?? '';
        }

        return self::$cache;
    }
}