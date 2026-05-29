<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PaymentHistoryService;
use Illuminate\Http\Request;

class AdminPaymentHistoryController extends Controller
{
    public function index(Request $request, PaymentHistoryService $paymentHistoryService)
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|min:1',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = isset($data['year']) ? (int) $data['year'] : null;
        $month = isset($data['month']) ? (int) $data['month'] : null;

        $query = User::query()
            ->select(['id', 'company_name', 'name', 'email'])
            ->whereNotNull('email')
            ->orderBy('company_name')
            ->orderBy('name')
            ->orderBy('email');

        if (!empty($data['user_id'])) {
            $query->where('id', (int) $data['user_id']);
        }

        $users = $query->limit(empty($data['user_id']) ? 25 : 1)->get();

        return response()->json([
            'filters' => [
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
                'year' => $year,
                'month' => $month,
            ],
            'users' => $users->map(function (User $user) use ($paymentHistoryService, $year, $month) {
                return [
                    'user_id' => (int) $user->id,
                    'user_name' => (string) ($user->company_name ?: $user->name ?: $user->email),
                    'user_email' => (string) $user->email,
                    'payment_history' => $paymentHistoryService->forUser($user, $year, $month),
                ];
            })->values()->all(),
        ]);
    }
}
