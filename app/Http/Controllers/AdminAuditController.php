<?php

namespace App\Http\Controllers;

use App\Models\CreditAuditLog;
use App\Models\PartnerExtractionAuthorization;

class AdminAuditController extends Controller
{
    public function index()
    {
        $rows = CreditAuditLog::query()
            ->leftJoin('users as actor', 'actor.id', '=', 'credit_audit_logs.actor_user_id')
            ->leftJoin('users as target', 'target.id', '=', 'credit_audit_logs.target_user_id')
            ->select([
                'credit_audit_logs.id',
                'credit_audit_logs.event_key',
                'credit_audit_logs.actor_user_id',
                'actor.company_name as actor_company_name',
                'actor.name as actor_name',
                'credit_audit_logs.target_user_id',
                'target.company_name as target_company_name',
                'target.name as target_name',
                'credit_audit_logs.entity_type',
                'credit_audit_logs.entity_id',
                'credit_audit_logs.request_id',
                'credit_audit_logs.new_values',
                'credit_audit_logs.created_at',
            ])
            ->orderByDesc('credit_audit_logs.id')
            ->limit(200)
            ->get();

        $entityIds = $rows
            ->filter(fn ($row) => $row->entity_type === 'partner_extraction_authorization' && $row->entity_id)
            ->pluck('entity_id')
            ->unique()
            ->values();
        $requestIds = $rows->pluck('request_id')->filter()->unique()->values();

        $authorizations = PartnerExtractionAuthorization::query()
            ->whereIn('id', $entityIds)
            ->orWhereIn('partner_request_id', $requestIds)
            ->get();

        $byEntityId = $authorizations->keyBy('id');
        $byRequestId = $authorizations->keyBy('partner_request_id');

        return $rows->map(function ($row) use ($byEntityId, $byRequestId) {
            $newValues = (array) ($row->new_values ?? []);
            $authorization = $row->entity_type === 'partner_extraction_authorization'
                ? $byEntityId->get((int) $row->entity_id)
                : $byRequestId->get((string) ($row->request_id ?? ''));

            return [
                'id' => $row->id,
                'event_key' => $row->event_key,
                'actor_user_id' => $row->actor_user_id,
                'actor_company_name' => $row->actor_company_name,
                'actor_name' => $row->actor_name,
                'target_user_id' => $row->target_user_id,
                'target_company_name' => $row->target_company_name,
                'target_name' => $row->target_name,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id,
                'partner_domain' => $authorization?->partner_domain ?? ($newValues['partner_domain'] ?? null),
                'partner_user_id' => $authorization?->partner_user_reference ?? ($newValues['partner_user_id'] ?? null),
                'partner_request_id' => $authorization?->partner_request_id ?? ($newValues['partner_request_id'] ?? $row->request_id),
                'reserved_credits' => (int) ($authorization?->credits_reserved ?? $newValues['reserved_credits'] ?? 0),
                'consumed_credits' => (int) ($authorization?->credits_consumed ?? $newValues['consumed_credits'] ?? 0),
                'refunded_credits' => (int) ($authorization?->credits_refunded ?? $newValues['refunded_credits'] ?? 0),
                'created_at' => $row->created_at,
            ];
        });
    }
}
