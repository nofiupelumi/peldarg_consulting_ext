<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CreditLedger;
use App\Models\User;
use Illuminate\Http\Request;

class UserCreditController extends Controller
{
    public function summary(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $user = User::query()->select(['id', 'credit_balance', 'credit_cap'])->findOrFail($userId);
        $settings = AppSetting::current();

        return response()->json([
            'credit_balance' => (int) ($user->credit_balance ?? 0),
            'credit_cap' => (int) ($user->credit_cap ?? 0),
            'unit_price_usd' => (string) $settings->unit_price_usd,
            'fx_rate_ngn' => (string) $settings->fx_rate_ngn,
        ]);
    }

    public function ledger(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');

        return CreditLedger::query()
            ->where('user_id', $userId)
            ->select([
                'id',
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
