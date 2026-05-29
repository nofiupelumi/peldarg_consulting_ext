<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerAllowlist extends Model
{
    protected $table = 'partner_allowlist';
    protected $fillable = [
        'partner_name',
        'partner_domain',
        'allowed_ips',
        'current_secret_key',
        'current_secret_key_id',
        'secret_rotated_at',
        'secret_expires_at',
        'active',
        'audit_note',
    ];

    protected $casts = [
        'secret_rotated_at' => 'datetime',
        'secret_expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    public static function findByPartnerName(string $partnerName): ?self
    {
        return self::where('partner_name', $partnerName)->where('active', true)->first();
    }

    public function isIpAllowed(string $ip): bool
    {
        if (!$this->allowed_ips) {
            return true;
        }

        $allowedIps = array_map('trim', explode(',', $this->allowed_ips));
        return in_array($ip, $allowedIps, true);
    }

    public function isSecretExpired(): bool
    {
        return $this->secret_expires_at && $this->secret_expires_at->isPast();
    }
}
