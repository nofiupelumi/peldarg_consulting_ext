<?php

namespace App\Http\Controllers;

use App\Models\PartnerExtractionAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class AdminReconciliationController extends Controller
{
    private function resolveRange(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $from = isset($validated['date_from'])
            ? Carbon::parse((string) $validated['date_from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($validated['date_to'])
            ? Carbon::parse((string) $validated['date_to'])->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function fetchPartnerSummary(string $from, string $to): array
    {
        $url = trim((string) config('services.partner.reconciliation_url', ''));
        $token = trim((string) config('services.partner.token', ''));

        if ($url === '' || $token === '') {
            return [
                'available' => false,
                'error' => 'Partner reconciliation endpoint is not configured.',
            ];
        }

        $response = Http::withHeaders([
                'X-Partner-Token' => $token,
                'Accept' => 'application/json',
            ])
            ->connectTimeout(5)
            ->timeout((int) config('services.partner.credit_sync_timeout', 10))
            ->post($url, [
                'date_from' => $from,
                'date_to' => $to,
            ]);

        if (!$response->successful()) {
            return [
                'available' => false,
                'error' => 'Partner reconciliation fetch failed.',
                'status' => $response->status(),
            ];
        }

        return array_merge(['available' => true], (array) $response->json());
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        $authorizations = PartnerExtractionAuthorization::query()
            ->whereBetween('created_at', [$from, $to]);

        $peldarg = [
            'authorization_count' => (int) (clone $authorizations)->count(),
            'pages_requested_total' => (int) ((clone $authorizations)->sum('pages_requested') ?? 0),
            'pages_processed_total' => (int) ((clone $authorizations)->sum('pages_processed') ?? 0),
            'reserved_credits_total' => (int) ((clone $authorizations)->sum('credits_reserved') ?? 0),
            'consumed_credits_total' => (int) ((clone $authorizations)->sum('credits_consumed') ?? 0),
            'refunded_credits_total' => (int) ((clone $authorizations)->sum('credits_refunded') ?? 0),
            'success_count' => (int) (clone $authorizations)->where('status', 'finalized')->count(),
            'failed_count' => (int) (clone $authorizations)->where('status', 'failed')->count(),
        ];

        $partner = $this->fetchPartnerSummary($from->toDateString(), $to->toDateString());
        $partnerProcessedPages = (int) ($partner['processed_pages_total'] ?? 0);

        return response()->json([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'peldarg' => $peldarg,
            'partner' => $partner,
            'variance' => [
                'processed_pages_delta' => $peldarg['pages_processed_total'] - $partnerProcessedPages,
                'consumed_vs_processed_delta' => $peldarg['consumed_credits_total'] - $partnerProcessedPages,
            ],
        ]);
    }
}