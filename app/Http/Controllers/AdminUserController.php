<?php

namespace App\Http\Controllers;

use App\Models\CreditAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    private const API_TIERS = ['paid_1', 'paid_2', 'paid_3'];

    public function index()
    {
        return User::query()
            ->select(['id', 'company_name', 'name', 'email', 'is_admin', 'status', 'credit_balance', 'credit_cap', 'must_change_password', 'allowed_api_tiers', 'created_at'])
            ->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'company_name' => 'required|string|max:255|unique:users,company_name',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:8|max:255',
            'is_admin' => 'nullable|boolean',
            'credit_cap' => 'nullable|integer|min:0',
            'credit_balance' => 'nullable|integer|min:0',
            'allowed_api_tiers' => 'nullable|array',
            'allowed_api_tiers.*' => 'string|in:paid_1,paid_2,paid_3',
        ]);

        $initialPassword = trim((string) ($payload['password'] ?? ''));
        if ($initialPassword === '') {
            $initialPassword = Str::password(12);
        }

        $isAdmin = (bool) ($payload['is_admin'] ?? false);
        $allowedApiTiers = $this->resolveAllowedApiTiers(
            raw: $payload['allowed_api_tiers'] ?? null,
            isAdmin: $isAdmin,
        );

        $user = User::create([
            'name' => $payload['company_name'],
            'company_name' => $payload['company_name'],
            'email' => $payload['email'],
            'password' => Hash::make($initialPassword),
            'is_admin' => $isAdmin,
            'status' => 'active',
            'credit_cap' => (int) ($payload['credit_cap'] ?? 0),
            'credit_balance' => (int) ($payload['credit_balance'] ?? 0),
            'must_change_password' => true,
            'allowed_api_tiers' => $allowedApiTiers,
            'email_verified_at' => now(),
        ]);

        $loginUrl = url('/login');
        Mail::raw(
            "Your Peldarg Extraction account is ready.\n\nCompany: {$user->company_name}\nLogin: {$loginUrl}\nEmail: {$user->email}\nPassword: {$initialPassword}\n\nPlease sign in and change your password immediately.",
            fn ($message) => $message->to($user->email)->subject('Peldarg Extractor Login Credentials')
        );

        return response()->json([
            'id' => $user->id,
            'company_name' => $user->company_name,
            'email' => $user->email,
            'status' => $user->status,
            'allowed_api_tiers' => $allowedApiTiers,
        ], 201);
    }

    public function updateApiTiers(Request $request, User $user)
    {
        $payload = $request->validate([
            'allowed_api_tiers' => 'required|array|min:1',
            'allowed_api_tiers.*' => 'string|in:paid_1,paid_2,paid_3',
        ]);

        $old = $user->allowed_api_tiers;
        $next = $this->resolveAllowedApiTiers(
            raw: $payload['allowed_api_tiers'],
            isAdmin: (bool) $user->is_admin,
        );

        $user->allowed_api_tiers = $next;
        $user->save();

        CreditAuditLog::create([
            'actor_user_id' => (int) $request->session()->get('user_id'),
            'target_user_id' => (int) $user->id,
            'event_key' => 'user.api_tiers.updated',
            'entity_type' => 'user',
            'entity_id' => (int) $user->id,
            'old_values' => ['allowed_api_tiers' => $this->normalizeApiTiers($old)],
            'new_values' => ['allowed_api_tiers' => $next],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'request_id' => (string) Str::uuid(),
        ]);

        return response()->json([
            'ok' => true,
            'user_id' => $user->id,
            'allowed_api_tiers' => $next,
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'force_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('force_active')) {
            $user->status = 'active';
        }

        $temporaryPassword = Str::password(12);
        $user->password = Hash::make($temporaryPassword);
        $user->must_change_password = true;
        $user->save();

        $loginUrl = url('/login');
        Mail::raw(
            "Your password has been reset by Peldarg admin.\n\nCompany: {$user->company_name}\nLogin: {$loginUrl}\nEmail: {$user->email}\nTemporary Password: {$temporaryPassword}\n\nPlease sign in and change your password immediately.",
            fn ($message) => $message->to($user->email)->subject('Peldarg Extractor Password Reset')
        );

        return response()->json(['ok' => true]);
    }

    private function resolveAllowedApiTiers(mixed $raw, bool $isAdmin): array
    {
        if ($isAdmin) {
            return self::API_TIERS;
        }

        $normalized = $this->normalizeApiTiers($raw);
        if ($normalized === []) {
            return ['paid_1'];
        }

        return $normalized;
    }

    private function normalizeApiTiers(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $allowed = array_values(array_intersect(
            self::API_TIERS,
            array_map(static fn ($v) => strtolower(trim((string) $v)), $raw)
        ));

        return array_values(array_unique($allowed));
    }
}
