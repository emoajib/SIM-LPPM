<?php

namespace App\Interfaces;

/**
 * Interface for Setting repository.
 * Vetted by AI - Manual Review Required by Senior Engineer/Manager
 */
interface SettingRepositoryInterface
{
    /**
     * Get a setting value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $type = 'string'): void;

    /**
     * Get multiple settings by keys.
     *
     * @param  string[]  $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys): array;

    /**
     * Get all settings matching a pattern (like).
     *
     * @return array<string, mixed>
     */
    public function getByPattern(string $pattern): array;

    /**
     * Delete settings matching a pattern (like).
     */
    public function deleteByPattern(string $pattern): void;

    /**
     * Delete settings by specific keys.
     *
     * @param  string[]  $keys
     */
    public function deleteMany(array $keys): void;
}
