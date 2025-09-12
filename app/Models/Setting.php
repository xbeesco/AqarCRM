<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::find($key);
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        
        Cache::forget("setting.{$key}");
        Cache::put("setting.{$key}", $value, 3600);
    }

    /**
     * Get multiple settings at once
     */
    public static function getMany(array $keys): array
    {
        $result = [];
        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                $result[$default] = self::get($default);
            } else {
                $result[$key] = self::get($key, $default);
            }
        }
        return $result;
    }

    /**
     * Set multiple settings at once
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Delete a setting by key
     */
    public static function forget(string $key): bool
    {
        $deleted = self::where('key', $key)->delete();
        Cache::forget("setting.{$key}");
        return $deleted > 0;
    }

    /**
     * Clear all cached settings
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
    }
}
