<?php
// app/Models/CoopProfile.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoopProfile extends Model
{
    protected $table = 'coop_profile';

    protected $fillable = [
        'name', 'cda_reg_no', 'address', 'contact', 'email',
        'website', 'hr_signatory', 'coop_signatory', 'logo_url',
        'fiscal_year_start',
    ];

    public static function current(): static
    {
        return static::firstOrCreate(['id' => 1], ['name' => 'CRS Cooperative']);
    }
}
