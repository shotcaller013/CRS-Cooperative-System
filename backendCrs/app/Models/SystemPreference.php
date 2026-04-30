<?php
// app/Models/SystemPreference.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemPreference extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }
}
