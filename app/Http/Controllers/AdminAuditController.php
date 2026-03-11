<?php

namespace App\Http\Controllers;

use App\Models\CreditAuditLog;

class AdminAuditController extends Controller
{
    public function index()
    {
        return CreditAuditLog::query()
            ->select([
                'id',
                'event_key',
                'actor_user_id',
                'target_user_id',
                'entity_type',
                'entity_id',
                'created_at',
            ])
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }
}
