<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    private static ?Collection $settingsCache = null;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Register Spatie MediaLibrary collections for template files.
     * Without this, addMedia() to 'template' collection will silently fail.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('template')
            ->singleFile(); // Hanya simpan 1 file per setting key; file lama otomatis tergantikan
    }

    /**
     * Load all settings into static cache (1 query total).
     */
    private static function loadCache(): void
    {
        if (self::$settingsCache === null) {
            self::$settingsCache = static::all()->keyBy('key');
        }
    }

    /**
     * Clear settings cache (call after set() or direct DB changes).
     */
    public static function clearCache(): void
    {
        self::$settingsCache = null;
    }

    /**
     * Get a setting value by key — uses in-memory cache (1 query for all keys).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadCache();

        /** @var self|null $setting */
        $setting = self::$settingsCache->get($key);

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $processedValue, 'type' => $type]
        );

        self::clearCache();
    }

    /**
     * Set multiple setting values at once using bulk upsert.
     * Expects an array of [key => ['value' => mixed, 'type' => string]] or [key => value] (assumes string).
     */
    public static function setMany(array $settings): void
    {
        if (empty($settings)) {
            return;
        }

        $records = [];
        $now = now();

        foreach ($settings as $key => $data) {
            $value = is_array($data) && array_key_exists('value', $data) ? $data['value'] : $data;
            $type = is_array($data) && array_key_exists('type', $data) ? $data['type'] : 'string';

            $processedValue = match ($type) {
                'boolean' => $value ? '1' : '0',
                'json' => json_encode($value),
                default => (string) $value,
            };

            $records[] = [
                'id' => Str::uuid()->toString(),
                'key' => $key,
                'value' => $processedValue,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        static::upsert($records, ['key'], ['value', 'type', 'updated_at']);
        self::clearCache();
    }
}
