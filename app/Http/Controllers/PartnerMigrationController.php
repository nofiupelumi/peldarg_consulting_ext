<?php

namespace App\Http\Controllers;

use App\Models\CreditAuditLog;
use App\Models\CreditLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerMigrationController extends Controller
{
    private function assertPartnerToken(Request $request): void
    {
        $expectedToken = (string) config('services.partner.token');
        $providedToken = (string) $request->header('X-Partner-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            abort(401);
        }
    }

    public function migrateUser(Request $request)
    {
        $this->assertPartnerToken($request);

        $data = $request->validate([
            'riskcontrol_user_email' => 'required|email',
            'user_email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|integer|min:0',
            'opening_cap' => 'nullable|integer|min:0',
            'partner_name' => 'nullable|string|max:100',
            'partner_domain' => 'nullable|string|max:255',
            'partner_user_reference' => 'nullable|string|max:255',
        ]);

        $result = DB::transaction(function () use ($data) {
            $email = (string) $data['user_email'];
            $name = trim((string) ($data['name'] ?? ''));
            $companyName = trim((string) ($data['company_name'] ?? ''));
            $openingBalance = max(0, (int) ($data['opening_balance'] ?? 0));
            $openingCap = max(0, (int) ($data['opening_cap'] ?? 0));

            $user = User::query()->lockForUpdate()->where('email', $email)->first();
            $created = false;

            if (!$user) {
                $fallbackName = $name !== '' ? $name : Str::headline(Str::before($email, '@'));
                $fallbackCompany = $companyName !== '' ? $companyName : $fallbackName;
                $cap = max($openingCap, $openingBalance);

                $user = User::create([
                    'name' => $fallbackName,
                    'company_name' => $fallbackCompany,
                    'email' => $email,
                    'password' => Str::random(48),
                    'is_admin' => false,
                    'status' => 'active',
                    'credit_balance' => 0,
                    'credit_cap' => $cap,
                    'must_change_password' => true,
                    'allowed_api_tiers' => ['paid_1'],
                ]);
                $created = true;
            }

            if ($openingCap > 0 && (int) $user->credit_cap < $openingCap) {
                $user->credit_cap = $openingCap;
            }

            $before = (int) $user->credit_balance;
            $cap = (int) $user->credit_cap;
            $appliedCredits = $openingBalance;

            if ($cap > 0) {
                $maxAdd = max(0, $cap - $before);
                $appliedCredits = min($openingBalance, $maxAdd);
            }

            if ($appliedCredits > 0) {
                $after = $before + $appliedCredits;

                CreditLedger::create([
                    'user_id' => $user->id,
                    'document_id' => null,
                    'invoice_id' => null,
                    'action_type' => 'admin_add',
                    'credits' => $appliedCredits,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'unit_price_usd' => null,
                    'amount_usd' => null,
                    'meta' => [
                        'source' => 'partner_user_migration',
                        'riskcontrol_user_email' => (string) $data['riskcontrol_user_email'],
                        'partner_name' => $data['partner_name'] ?? 'riskcontrol',
                        'partner_domain' => $data['partner_domain'] ?? null,
                        'partner_user_reference' => $data['partner_user_reference'] ?? null,
                    ],
                    'created_by_user_id' => $user->id,
                ]);

                $user->credit_balance = $after;
            }

            $user->save();

            CreditAuditLog::create([
                'actor_user_id' => $user->id,
                'target_user_id' => $user->id,
                'event_key' => 'partner.user.migrated',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'riskcontrol_user_email' => (string) $data['riskcontrol_user_email'],
                    'opening_balance_requested' => $openingBalance,
                    'opening_balance_applied' => $appliedCredits,
                    'credit_balance' => (int) $user->credit_balance,
                    'credit_cap' => (int) $user->credit_cap,
                    'created' => $created,
                ],
                'request_id' => 'migration:' . (string) $data['riskcontrol_user_email'],
            ]);

            return [
                'created' => $created,
                'user' => $user,
                'opening_balance_applied' => $appliedCredits,
            ];
        });

        /** @var User $user */
        $user = $result['user'];

        return response()->json([
            'ok' => true,
            'created' => (bool) $result['created'],
            'user_id' => (int) $user->id,
            'user_email' => (string) $user->email,
            'credit_balance' => (int) $user->credit_balance,
            'credit_cap' => (int) $user->credit_cap,
            'opening_balance_applied' => (int) $result['opening_balance_applied'],
        ]);
    }
}
