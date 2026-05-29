<?php

namespace App\Services;

use App\Models\PartnerAllowlist;
use App\Models\PartnerSecretRotationLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PartnerSecretRotationService
{
    public const SECRET_LENGTH = 64;
    public const DEFAULT_EXPIRY_DAYS = 90;

    /**
     * Generate a new secret key with ID.
     */
    public static function generateSecret(): array
    {
        return [
            'key_id' => Str::uuid()->toString(),
            'secret_key' => Str::random(self::SECRET_LENGTH),
            'rotated_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(self::DEFAULT_EXPIRY_DAYS)->toIso8601String(),
        ];
    }

    /**
     * Rotate secret for partner (scheduled or incident-based).
     */
    public static function rotateSecret(
        string $partnerName,
        string $reason = 'scheduled',
        ?int $rotatedByUserId = null,
        ?string $reasonNote = null
    ): ?PartnerAllowlist {
        $partner = PartnerAllowlist::findByPartnerName($partnerName);
        if (!$partner) {
            return null;
        }

        // Generate new secret
        $newSecret = self::generateSecret();

        // Log rotation
        PartnerSecretRotationLog::create([
            'partner_name' => $partnerName,
            'old_key_id' => $partner->current_secret_key_id,
            'new_key_id' => $newSecret['key_id'],
            'reason' => $reason,
            'rotated_by_user_id' => $rotatedByUserId,
            'reason_note' => $reasonNote,
        ]);

        // Update partner with new secret
        $partner->update([
            'current_secret_key' => $newSecret['secret_key'],
            'current_secret_key_id' => $newSecret['key_id'],
            'secret_rotated_at' => now(),
            'secret_expires_at' => Carbon::parse($newSecret['expires_at']),
        ]);

        return $partner;
    }

    /**
     * Check if any partner secrets have expired and rotate them.
     */
    public static function rotateExpiredSecrets(): int
    {
        $expired = PartnerAllowlist::where('secret_expires_at', '<=', now())
            ->where('active', true)
            ->get();

        $count = 0;
        foreach ($expired as $partner) {
            self::rotateSecret($partner->partner_name, 'scheduled', audit_note: 'Automatic expiry rotation');
            $count++;
        }

        return $count;
    }

    /**
     * Get rotation history for a partner.
     */
    public static function getRotationHistory(string $partnerName, int $limit = 10): array
    {
        return PartnerSecretRotationLog::where('partner_name', $partnerName)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
