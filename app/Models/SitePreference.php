<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitePreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'association_description',
        'logo_path',
        'website_url',
        'general_whatsapp_link',
    ];

    /**
     * Get the singleton instance of site preferences.
     */
    public static function instance(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }
}
