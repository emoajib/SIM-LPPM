<?php

namespace App\Services;

use App\Models\NotificationSetting;

class NotificationConfigService
{
    public function get(string $key, $default = null)
    {
        $db = NotificationSetting::get($key);
        if ($db !== null && $db !== '') {
            return $db;
        }
        return env($key, $default);
    }
}
