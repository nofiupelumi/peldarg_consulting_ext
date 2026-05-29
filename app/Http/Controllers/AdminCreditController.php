<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CreditService;
use App\Services\PartnerCreditSyncService;
use Illuminate\Http\Request;

class AdminCreditController extends Controller
{
    public function __construct(
        private CreditService $creditService,
        private PartnerCreditSyncService $partnerCreditSyncService,
    )
    {
    }

    public function add(Request $request, User $user)
    {
        $data = $request->validate([
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->creditService->adminAddCredits(
            targetUserId: $user->id,
            credits: (int) $data['credits'],
            actorUserId: (int) $request->session()->get('user_id'),
            reason: $data['reason'] ?? null,
        );

        $user->refresh();
        $this->partnerCreditSyncService->notifyCreditUpdated($user, 'admin_credit_add', [
            'credits' => (int) $data['credits'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json($result);
    }

    public function deduct(Request $request, User $user)
    {
        $data = $request->validate([
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->creditService->adminDeductCredits(
            targetUserId: $user->id,
            credits: (int) $data['credits'],
            actorUserId: (int) $request->session()->get('user_id'),
            reason: $data['reason'] ?? null,
        );

        $user->refresh();
        $this->partnerCreditSyncService->notifyCreditUpdated($user, 'admin_credit_deduct', [
            'credits' => (int) $data['credits'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json($result);
    }

    public function setCap(Request $request, User $user)
    {
        $data = $request->validate([
            'credit_cap' => 'required|integer|min:0',
        ]);

        $result = $this->creditService->setCreditCap(
            targetUserId: $user->id,
            cap: (int) $data['credit_cap'],
            actorUserId: (int) $request->session()->get('user_id'),
        );

        return response()->json($result);
    }
}
