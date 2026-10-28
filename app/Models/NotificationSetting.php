<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class NotificationSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = ['is_encrypted' => 'boolean'];

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null && $value !== '') {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function set(string $key, $value, bool $encrypted = false): void
    {
        $row = static::firstOrNew(['key' => $key]);
        $row->is_encrypted = $encrypted;
        $row->value = $value;
        $row->save();
    }
}
