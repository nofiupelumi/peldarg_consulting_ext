<?php

namespace App\Http\Controllers;

use App\Services\ExtractionActivityService;
use Illuminate\Http\Request;

class PartnerActivityController extends Controller
{
    private function assertPartnerToken(Request $request): void
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }
    }

    public function ingest(Request $request, ExtractionActivityService $activityService)
    {
        $this->assertPartnerToken($request);

        $payload = $request->validate([
            'partner_request_id' => 'required|uuid',
            'event_key' => 'required|string|max:120',
            'sequence' => 'nullable|integer|min:1',
            'event_at' => 'nullable|date',
            'status' => 'nullable|string|max:60',
            'phase' => 'nullable|string|max:60',
            'partner_name' => 'nullable|string|max:100',
            'partner_domain' => 'nullable|string|max:255',
            'user_email' => 'nullable|email',
            'extraction_type' => 'nullable|string|max:60',
            'run_id' => 'nullable|string|max:200',
            'doc_id' => 'nullable|integer|min:1',
            'pages_requested' => 'nullable|integer|min:0',
            'pages_processed' => 'nullable|integer|min:0',
            'pages_with_results' => 'nullable|integer|min:0',
            'credits_reserved' => 'nullable|integer|min:0',
            'credits_consumed' => 'nullable|integer|min:0',
            'credits_refunded' => 'nullable|integer|min:0',
            'credit_outcome' => 'nullable|string|max:60',
            'failed_reason' => 'nullable|string|max:1000',
            'dedupe_key' => 'nullable|string|max:255',
        ]);

        $result = $activityService->ingest($payload);

        return response()->json([
            'ok' => true,
            'reused' => (bool) ($result['reused'] ?? false),
            'stream_id' => (int) $result['stream']->id,
            'event_id' => (int) $result['event']->id,
            'normalized_sequence' => (int) ($result['normalized_sequence'] ?? 0),
            'partner_request_id' => (string) $result['stream']->partner_request_id,
            'latest_status' => (string) $result['stream']->status,
            'latest_phase' => (string) $result['stream']->phase,
        ]);
    }
}
