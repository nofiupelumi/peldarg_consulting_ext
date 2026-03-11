<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\AppSetting;
use App\Models\User;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['App\Http\Middleware\CheckAuth'])->group(function () {
    Route::get('/', function () {
        $user = User::findOrFail((int) session('user_id'));
        $settings = AppSetting::current();

        return view('convocation', [
            'creditBalance' => (int) $user->credit_balance,
            'maxUploadMb' => (int) $settings->max_upload_mb,
        ]);
    })->name('dashboard');

    Route::middleware(['App\Http\Middleware\EnsureAdmin'])->group(function () {
        Route::get('/admin', function () {
            $settings = AppSetting::current();

            return view('admin', [
                'unitPriceUsd' => (string) $settings->unit_price_usd,
                'fxRateNgn' => (string) $settings->fx_rate_ngn,
                'maxUploadMb' => (int) $settings->max_upload_mb,
                'admin2faRequired' => (bool) $settings->admin_2fa_required,
            ]);
        })->name('admin.console');
    });
});
