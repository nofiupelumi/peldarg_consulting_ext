<?php

namespace App\Services;

use App\Models\CreditInvoice;
use App\Models\CreditLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentHistoryService
{
    public function forUserEmail(string $userEmail, ?int $year = null, ?int $month = null, ?int $tzOffsetMinutes = null): array
    {
        $user = User::query()->where('email', $userEmail)->first();
        if (!$user) {
            throw ValidationException::withMessages(['user_email' => 'No peldarg account found for this email.']);
        }

        return $this->forUser($user, $year, $month, $tzOffsetMinutes);
    }

    public function forUser(User $user, ?int $year = null, ?int $month = null, ?int $tzOffsetMinutes = null): array
    {
        $paymentAtExpression = 'COALESCE(fulfilled_at, paid_at, reviewed_at, created_at)';
        $selectedYear = $year ?: (int) now()->year;
        $selectedMonth = $month ?: null;

        $historyQuery = CreditInvoice::query()
            ->where('user_id', $user->id)
            ->select([
                'id',
                'invoice_number',
                'requested_credits',
                'requested_amount_usd',
                'payment_reference',
                'payment_provider',
                'gateway_reference',
                'gateway_status',
                'status',
                'amount_ngn_kobo',
                'created_at',
                'paid_at',
                'fulfilled_at',
                'reviewed_at',
            ])
            ->selectRaw($paymentAtExpression . ' as payment_at');

        $filteredHistoryQuery = clone $historyQuery;

        // If a timezone offset is provided (minutes west of UTC, as returned by JS
        // Date.getTimezoneOffset()), compute the exact UTC range that corresponds to
        // the selected local month. This prevents UTC midnight crossings from showing
        // payments in the wrong month.
        if ($selectedMonth !== null && $tzOffsetMinutes !== null) {
            // $tzOffsetMinutes is minutes WEST of UTC (negative for east-of-UTC zones).
            // Local midnight = UTC midnight + $tzOffsetMinutes minutes.
            $offsetSeconds = $tzOffsetMinutes * 60;
            $periodStart = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, 'UTC')
                ->addSeconds($offsetSeconds);
            $periodEnd = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, 'UTC')
                ->addMonth()
                ->addSeconds($offsetSeconds);

            $filteredHistoryQuery->whereBetween(
                DB::raw($paymentAtExpression),
                [$periodStart->toDateTimeString(), $periodEnd->toDateTimeString()]
            );
        } else {
            $filteredHistoryQuery->whereYear(DB::raw($paymentAtExpression), $selectedYear);
            if ($selectedMonth !== null) {
                $filteredHistoryQuery->whereMonth(DB::raw($paymentAtExpression), $selectedMonth);
            }
        }

        $items = $filteredHistoryQuery
            ->orderByDesc('payment_at')
            ->limit(100)
            ->get();

        $invoiceIds = $items->pluck('id')->all();
        $ledgerByInvoice = CreditLedger::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->select([
                'id',
                'invoice_id',
                'action_type',
                'credits',
                'balance_before',
                'balance_after',
                'amount_usd',
                'created_at',
                'meta',
            ])
            ->orderBy('id')
            ->get()
            ->groupBy('invoice_id');

        $successfulBase = CreditInvoice::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhereNotNull('paid_at')
                    ->orWhereNotNull('fulfilled_at');
            });

        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        return [
            'user_email' => (string) $user->email,
            'filters' => [
                'year' => $selectedYear,
                'month' => $selectedMonth,
            ],
            'summary' => [
                'selected_period' => $this->summarize(clone $successfulBase, $paymentAtExpression, $selectedYear, $selectedMonth, $tzOffsetMinutes),
                'current_month' => $this->summarize(clone $successfulBase, $paymentAtExpression, $currentYear, $currentMonth, $tzOffsetMinutes),
                'current_year' => $this->summarize(clone $successfulBase, $paymentAtExpression, $currentYear, null, $tzOffsetMinutes),
            ],
            'items' => $items->map(function (CreditInvoice $invoice) use ($ledgerByInvoice) {
                return [
                    'invoice_id' => (int) $invoice->id,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'requested_credits' => (int) $invoice->requested_credits,
                    'requested_amount_usd' => (string) $invoice->requested_amount_usd,
                    'amount_ngn_kobo' => (int) ($invoice->amount_ngn_kobo ?? 0),
                    'payment_reference' => $invoice->payment_reference,
                    'payment_provider' => $invoice->payment_provider,
                    'gateway_reference' => $invoice->gateway_reference,
                    'gateway_status' => $invoice->gateway_status,
                    'status' => $invoice->status,
                    'payment_at' => optional($invoice->payment_at)->toIso8601String(),
                    'paid_at' => optional($invoice->paid_at)->toIso8601String(),
                    'fulfilled_at' => optional($invoice->fulfilled_at)->toIso8601String(),
                    'created_at' => optional($invoice->created_at)->toIso8601String(),
                    'ledger_entries' => $this->formatLedgerEntries($ledgerByInvoice->get($invoice->id) ?? collect()),
                ];
            })->values()->all(),
        ];
    }

    private function summarize($query, string $paymentAtExpression, int $year, ?int $month, ?int $tzOffsetMinutes = null): array
    {
        if ($month !== null && $tzOffsetMinutes !== null) {
            $offsetSeconds = $tzOffsetMinutes * 60;
            $start = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->addSeconds($offsetSeconds);
            $end = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->addMonth()->addSeconds($offsetSeconds);
            $query->whereBetween(DB::raw($paymentAtExpression), [$start->toDateTimeString(), $end->toDateTimeString()]);
        } else {
            $query->whereYear(DB::raw($paymentAtExpression), $year);
            if ($month !== null) {
                $query->whereMonth(DB::raw($paymentAtExpression), $month);
            }
        }

        return [
            'invoice_count' => (int) $query->count(),
            'requested_credits' => (int) ($query->sum('requested_credits') ?? 0),
            'requested_amount_usd' => (string) ($query->sum('requested_amount_usd') ?? '0'),
            'amount_ngn_kobo' => (int) ($query->sum('amount_ngn_kobo') ?? 0),
        ];
    }

    private function formatLedgerEntries(Collection $entries): array
    {
        return $entries->map(function (CreditLedger $ledger) {
            return [
                'id' => (int) $ledger->id,
                'action_type' => (string) $ledger->action_type,
                'credits' => (int) $ledger->credits,
                'balance_before' => (int) $ledger->balance_before,
                'balance_after' => (int) $ledger->balance_after,
                'amount_usd' => $ledger->amount_usd !== null ? (string) $ledger->amount_usd : null,
                'created_at' => optional($ledger->created_at)->toIso8601String(),
                'meta' => $ledger->meta,
            ];
        })->values()->all();
    }
}
