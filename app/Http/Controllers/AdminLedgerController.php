<?php

namespace App\Http\Controllers;

use App\Models\CreditLedger;
use App\Models\PartnerExtractionAuthorization;

class AdminLedgerController extends Controller
{
    public function index()
    {
        $rows = CreditLedger::query()
            ->leftJoin('users', 'users.id', '=', 'credit_ledgers.user_id')
            ->select([
                'credit_ledgers.id',
                'credit_ledgers.user_id',
                'users.company_name as user_company_name',
                'users.name as user_name',
                'credit_ledgers.document_id',
                'credit_ledgers.invoice_id',
                'credit_ledgers.action_type',
                'credit_ledgers.credits',
                'credit_ledgers.balance_before',
                'credit_ledgers.balance_after',
                'credit_ledgers.meta',
                'credit_ledgers.created_at',
            ])
            ->orderByDesc('credit_ledgers.id')
            ->limit(200)
            ->get();

        $partnerRequestIds = $rows
            ->map(fn ($row) => data_get((array) $row->meta, 'partner_request_id'))
            ->filter()
            ->unique()
            ->values();

        $authorizations = PartnerExtractionAuthorization::query()
            ->whereIn('partner_request_id', $partnerRequestIds)
            ->get()
            ->keyBy('partner_request_id');

        return $rows->map(function ($row) use ($authorizations) {
            $meta = (array) ($row->meta ?? []);
            $authorization = $authorizations->get((string) ($meta['partner_request_id'] ?? ''));

            return [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'user_company_name' => $row->user_company_name,
                'user_name' => $row->user_name,
                'document_id' => $row->document_id,
                'invoice_id' => $row->invoice_id,
                'action_type' => $row->action_type,
                'credits' => $row->credits,
                'balance_before' => $row->balance_before,
                'balance_after' => $row->balance_after,
                'partner_domain' => $authorization?->partner_domain ?? ($meta['partner_domain'] ?? null),
                'partner_user_id' => $authorization?->partner_user_reference ?? ($meta['partner_user_id'] ?? null),
                'partner_request_id' => $authorization?->partner_request_id ?? ($meta['partner_request_id'] ?? null),
                'reserved_credits' => (int) ($authorization?->credits_reserved ?? $meta['reserved_credits'] ?? 0),
                'consumed_credits' => (int) ($authorization?->credits_consumed ?? $meta['consumed_credits'] ?? 0),
                'refunded_credits' => (int) ($authorization?->credits_refunded ?? $meta['refunded_credits'] ?? 0),
                'created_at' => $row->created_at,
            ];
        });
    }
}
