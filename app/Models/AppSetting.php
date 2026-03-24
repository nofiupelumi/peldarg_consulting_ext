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

    public static function phpUploadLimitMb(): int
    {
        $uploadLimit = static::iniSizeToMb((string) ini_get('upload_max_filesize'));
        $postLimit = static::iniSizeToMb((string) ini_get('post_max_size'));

        return max(1, min($uploadLimit, $postLimit));
    }

    public function effectiveMaxUploadMb(): int
    {
        return max(1, min((int) $this->max_upload_mb, static::phpUploadLimitMb()));
    }

    private static function iniSizeToMb(string $value): int
    {
        $normalized = trim(strtolower($value));
        if ($normalized === '') {
            return 1;
        }

        $unit = substr($normalized, -1);
        $number = (float) $normalized;

        if (ctype_alpha($unit)) {
            $number = (float) substr($normalized, 0, -1);
        } else {
            $unit = 'b';
        }

        $bytes = match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };

        return max(1, (int) floor($bytes / 1024 / 1024));
    }
}
