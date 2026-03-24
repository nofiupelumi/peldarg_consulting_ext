<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\AppSetting;
use App\Models\CreditInvoice;
use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['App\Http\Middleware\CheckAuth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = User::findOrFail((int) session('user_id'));
        $settings = AppSetting::current();
        $maxUploadMb = $settings->effectiveMaxUploadMb();
        $allApiTiers = ['paid_1', 'paid_2', 'paid_3'];
        $allowedApiTiers = (bool) $user->is_admin
            ? $allApiTiers
            : array_values(array_intersect($allApiTiers, (array) ($user->allowed_api_tiers ?? [])));
        if ($allowedApiTiers === []) {
            $allowedApiTiers = ['paid_1'];
        }

        $now = Carbon::now();
        $uploadsToday = Document::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->count();
        $uploadsThisMonth = Document::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();
        $successfulExtractsTotal = Document::query()
            ->where('user_id', $user->id)
            ->where('status', 'complete')
            ->count();

        return view('convocation', [
            'userName' => (string) ($user->company_name ?: $user->name ?: 'User'),
            'creditBalance' => (int) $user->credit_balance,
            'creditCap' => (int) ($user->credit_cap ?? 0),
            'unitPriceUsd' => (string) $settings->unit_price_usd,
            'fxRateNgn' => (string) $settings->fx_rate_ngn,
            'maxUploadMb' => $maxUploadMb,
            'uploadsToday' => (int) $uploadsToday,
            'uploadsThisMonth' => (int) $uploadsThisMonth,
            'successfulExtractsTotal' => (int) $successfulExtractsTotal,
            'availableApiTiers' => $allowedApiTiers,
            'defaultApiTier' => $allowedApiTiers[0],
        ]);
    })->name('dashboard');

    Route::get('/top-up', function () {
        $user = User::findOrFail((int) session('user_id'));
        $settings = AppSetting::current();

        $currentInvoice = CreditInvoice::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        return view('topup', [
            'userName' => (string) ($user->company_name ?: $user->name ?: 'User'),
            'creditBalance' => (int) $user->credit_balance,
            'creditCap' => (int) ($user->credit_cap ?? 0),
            'unitPriceUsd' => (string) $settings->unit_price_usd,
            'fxRateNgn' => (string) $settings->fx_rate_ngn,
            'currentInvoice' => $currentInvoice,
        ]);
    })->name('topup');

    Route::get('/payment-history', function () {
        $user = User::findOrFail((int) session('user_id'));

        return view('payment-history', [
            'userName' => (string) ($user->company_name ?: $user->name ?: 'User'),
        ]);
    })->name('payment.history');

    Route::get('/settings', function () {
        $user = User::findOrFail((int) session('user_id'));

        return view('settings', [
            'userName' => (string) ($user->company_name ?: $user->name ?: 'User'),
            'companyName' => (string) ($user->company_name ?: ''),
            'email' => (string) ($user->email ?: ''),
        ]);
    })->name('settings');

    Route::middleware(['App\Http\Middleware\EnsureAdmin'])->group(function () {
        Route::get('/admin', function () {
            $settings = AppSetting::current();
            $maxUploadMb = $settings->effectiveMaxUploadMb();

            return view('admin', [
                'unitPriceUsd' => (string) $settings->unit_price_usd,
                'fxRateNgn' => (string) $settings->fx_rate_ngn,
                'maxUploadMb' => $maxUploadMb,
                'admin2faRequired' => (bool) $settings->admin_2fa_required,
            ]);
        })->name('admin.console');
    });
});
