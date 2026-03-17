<?php

namespace App\Http\Controllers;

use App\Models\CreditLedger;

class AdminLedgerController extends Controller
{
    public function index()
    {
        return CreditLedger::query()
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
                'credit_ledgers.created_at',
            ])
            ->orderByDesc('credit_ledgers.id')
            ->limit(200)
            ->get();
    }
}
