<?php

namespace App\Http\Controllers;

use App\Models\CreditAuditLog;

class AdminAuditController extends Controller
{
    public function index()
    {
        return CreditAuditLog::query()
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
                'credit_audit_logs.created_at',
            ])
            ->orderByDesc('credit_audit_logs.id')
            ->limit(200)
            ->get();
    }
}
