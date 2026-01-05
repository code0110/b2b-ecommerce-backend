<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'description'];

    /**
     * Get setting value by key, with optional default.
     * Automatically casts based on type.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value by key.
     */
    public static function set(string $key, $value)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            $setting->value = (string) $value; // Store as string
            $setting->save();
        }
    }

    public function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
