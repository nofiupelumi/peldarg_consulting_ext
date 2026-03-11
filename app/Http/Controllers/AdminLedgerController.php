<?php

namespace App\Http\Controllers;

use App\Models\CreditLedger;

class AdminLedgerController extends Controller
{
    public function index()
    {
        return CreditLedger::query()
            ->select([
                'id',
                'user_id',
                'document_id',
                'invoice_id',
                'action_type',
                'credits',
                'balance_before',
                'balance_after',
                'created_at',
            ])
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }
}
