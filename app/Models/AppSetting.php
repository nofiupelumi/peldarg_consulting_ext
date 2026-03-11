<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'unit_price_usd',
        'fx_rate_ngn',
        'max_upload_mb',
        'admin_2fa_required',
    ];

    protected $casts = [
        'unit_price_usd' => 'decimal:4',
        'fx_rate_ngn' => 'decimal:2',
        'max_upload_mb' => 'integer',
        'admin_2fa_required' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([]);
    }
}
