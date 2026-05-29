<?php

namespace App\Http\Controllers;

use App\Models\PartnerExtractionAuthorization;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartnerTrackingController extends Controller
{
    private function assertPartnerToken(Request $request): void
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }
    }

    public function progress(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'partner_request_id' => 'required|uuid',
            'user_email' => 'required|email',
        ]);

        $authorization = PartnerExtractionAuthorization::query()
            ->with('user:id,email')
            ->where('partner_request_id', $data['partner_request_id'])
            ->first();

        if (!$authorization || strtolower((string) optional($authorization->user)->email) !== strtolower((string) $data['user_email'])) {
            throw ValidationException::withMessages(['partner_request_id' => 'No matching extraction tracking record found.']);
        }

        $status = (string) $authorization->status;
        $phase = match ($status) {
            'authorized' => 'processing',
            'finalized' => 'completed',
            'failed' => 'failed',
            default => 'processing',
        };

        $pagesRequested = (int) ($authorization->pages_requested ?? 0);
        $pagesProcessed = (int) ($authorization->pages_processed ?? 0);
        $progressPercent = $pagesRequested > 0
            ? min(100, (int) round(($pagesProcessed / $pagesRequested) * 100))
            : ($status === 'finalized' ? 100 : 0);

        if ($status === 'authorized' && $progressPercent === 0) {
            $progressPercent = 10;
        }

        return response()->json([
            'partner_request_id' => (string) $authorization->partner_request_id,
            'status' => $status,
            'phase' => $phase,
            'pages_requested' => $pagesRequested,
            'pages_processed' => $pagesProcessed,
            'pages_with_results' => (int) ($authorization->pages_with_results ?? 0),
            'credits_reserved' => (int) ($authorization->credits_reserved ?? 0),
            'credits_consumed' => (int) ($authorization->credits_consumed ?? 0),
            'credits_refunded' => (int) ($authorization->credits_refunded ?? 0),
            'failed_reason' => $authorization->failed_reason,
            'progress_percent' => $progressPercent,
            'created_at' => optional($authorization->created_at)->toIso8601String(),
            'finalized_at' => optional($authorization->finalized_at)->toIso8601String(),
            'expires_at' => optional($authorization->expires_at)->toIso8601String(),
        ]);
    }
}
