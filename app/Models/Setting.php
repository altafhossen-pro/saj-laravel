<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group'];

    protected static $cachedSettings = null;

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        if (self::$cachedSettings === null) {
            self::$cachedSettings = \Illuminate\Support\Facades\Cache::rememberForever('global_settings_all', function () {
                return self::all()->keyBy('key');
            });
        }

        if (!self::$cachedSettings->has($key)) {
            return $default;
        }

        $setting = self::$cachedSettings->get($key);

        if ($setting->type === 'boolean') {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key
     */
    public static function set($key, $value, $type = 'text', $group = 'general')
    {
        $result = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );
        \Illuminate\Support\Facades\Cache::forget('global_settings_all');
        self::$cachedSettings = null;
        return $result;
    }
}
