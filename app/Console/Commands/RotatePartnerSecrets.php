<?php

namespace App\Console\Commands;

use App\Services\PartnerSecretRotationService;
use Illuminate\Console\Command;

class RotatePartnerSecrets extends Command
{
    protected $signature = 'partners:rotate-secrets {partner? : Partner name to rotate, or leave empty for all expired}';
    protected $description = 'Rotate partner integration secrets on schedule or by incident';

    public function handle(): int
    {
        $partnerName = $this->argument('partner');

        if ($partnerName) {
            // Rotate specific partner
            $result = PartnerSecretRotationService::rotateSecret(
                $partnerName,
                reason: 'manual',
                rotatedByUserId: auth()->user()?->id,
                reasonNote: 'Manually rotated via command'
            );

            if (!$result) {
                $this->error("Partner not found: {$partnerName}");
                return 1;
            }

            $this->info("✓ Secret rotated for partner: {$partnerName}");
            $this->info("  New Key ID: {$result->current_secret_key_id}");
            $this->info("  Rotated at: {$result->secret_rotated_at}");

            return 0;
        }

        // Rotate all expired secrets
        $count = PartnerSecretRotationService::rotateExpiredSecrets();
        $this->info("✓ Rotated secrets for {$count} expired partner(s)");

        return 0;
    }
}
