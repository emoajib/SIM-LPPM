<?php

namespace App\Repositories;

use App\Interfaces\SettingRepositoryInterface;
use App\Models\Setting;

/**
 * Eloquent implementation of SettingRepositoryInterface.
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        Setting::set($key, $value, $type);
    }

    public function getMany(array $keys): array
    {
        return Setting::whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function getByPattern(string $pattern): array
    {
        return Setting::where('key', 'like', $pattern)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function deleteByPattern(string $pattern): void
    {
        Setting::where('key', 'like', $pattern)->delete();
        Setting::clearCache();
    }

    public function deleteMany(array $keys): void
    {
        Setting::whereIn('key', $keys)->delete();
        Setting::clearCache();
    }
}
