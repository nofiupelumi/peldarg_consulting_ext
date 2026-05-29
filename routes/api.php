<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminCreditController;
use App\Http\Controllers\AdminLedgerController;
use App\Http\Controllers\AdminActivityStreamController;
use App\Http\Controllers\AdminPaymentHistoryController;
use App\Http\Controllers\AdminReconciliationController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CreditInvoiceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GithubController;
use App\Http\Controllers\BookletLogController;
use App\Http\Controllers\PartnerCapabilityController;
use App\Http\Controllers\PartnerActivityController;
use App\Http\Controllers\PartnerExtractionController;
use App\Http\Controllers\PartnerMigrationController;
use App\Http\Controllers\PartnerTrackingController;
use App\Http\Controllers\PaystackPaymentController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserCreditController;
use App\Http\Controllers\UserAccountController;

// These routes rely on session-based auth (CheckAuth uses Session::get('authenticated')).
// API routes do not include session middleware by default, so we explicitly enable the `web`
// middleware group for the authenticated endpoints.
Route::middleware(['web', 'App\Http\Middleware\CheckAuth'])->group(function () {
    Route::post('/upload', [DocumentController::class, 'upload']);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/booklet-logs', [BookletLogController::class, 'index']);
    Route::delete('/documents/{doc}', [DocumentController::class, 'delete']);
    Route::post('/documents/{doc}/recover-artifact', [DocumentController::class, 'recoverArtifact']);

    Route::get('/credit-summary', [UserCreditController::class, 'summary']);
    Route::get('/credit-ledger', [UserCreditController::class, 'ledger']);

    Route::get('/credit-invoices', [CreditInvoiceController::class, 'index']);
    Route::post('/credit-invoices', [CreditInvoiceController::class, 'store']);
    Route::post('/credit-invoices/paystack/initialize', [PaystackPaymentController::class, 'initialize']);
    Route::post('/credit-invoices/paystack/verify', [PaystackPaymentController::class, 'verify']);

    Route::post('/account/profile', [UserAccountController::class, 'profile']);
    Route::post('/account/password', [UserAccountController::class, 'password']);
});

Route::middleware(['web', 'App\Http\Middleware\CheckAuth', 'App\Http\Middleware\EnsureAdmin'])->group(function () {
    Route::get('/admin/settings', [AdminSettingsController::class, 'show']);
    Route::post('/admin/settings', [AdminSettingsController::class, 'update']);

    Route::get('/admin/ledger', [AdminLedgerController::class, 'index']);
    Route::get('/admin/audit', [AdminAuditController::class, 'index']);
    Route::get('/admin/activity-streams', [AdminActivityStreamController::class, 'index']);
    Route::get('/admin/payment-history', [AdminPaymentHistoryController::class, 'index']);
    Route::get('/admin/reconciliation', [AdminReconciliationController::class, 'index']);

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::post('/admin/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
    Route::post('/admin/users/{user}/api-tiers', [AdminUserController::class, 'updateApiTiers']);
    Route::post('/admin/users/{user}/credits/add', [AdminCreditController::class, 'add']);
    Route::post('/admin/users/{user}/credits/deduct', [AdminCreditController::class, 'deduct']);
    Route::post('/admin/users/{user}/credit-cap', [AdminCreditController::class, 'setCap']);

    Route::get('/admin/credit-invoices', [CreditInvoiceController::class, 'adminList']);
    Route::post('/admin/credit-invoices/{invoice}/approve', [CreditInvoiceController::class, 'approve']);
    Route::post('/admin/credit-invoices/{invoice}/reject', [CreditInvoiceController::class, 'reject']);
});

Route::get('/download/{doc}', [DocumentController::class, 'download'])
    ->name('documents.download')
    ->middleware('signed');
Route::get('/download-output/{doc}/{type}', [DocumentController::class, 'downloadOutput'])
    ->name('documents.downloadOutput')
    ->where('type', 'csv|xlsx')
    ->middleware('signed');

Route::post('/github/callback', [GithubController::class, 'callback'])->name('github.callback');
Route::post('/github/upload-results', [GithubController::class, 'uploadResults'])->name('github.uploadResults');
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])->name('paystack.webhook');

// Phase 6: Security hardening for partner endpoints (machine-to-machine)
// Requires: X-Partner-Name, X-Partner-Signature, X-Partner-Timestamp, X-Partner-Nonce headers
// Validates: HMAC signature, timestamp freshness, nonce uniqueness, IP allowlist, and idempotency
Route::middleware([
    'App\Http\Middleware\PartnerAllowlistVerification',
    'App\Http\Middleware\PartnerSignatureVerification',
    'App\Http\Middleware\IdempotencyKeyTracking',
])->group(function () {
    Route::get('/partner/capabilities', [PartnerCapabilityController::class, 'show']);
    Route::post('/partner/credit-summary', [PartnerCapabilityController::class, 'creditSummary']);
    Route::post('/partner/payment-history', [PartnerCapabilityController::class, 'paymentHistory']);
    Route::post('/partner/activity-events', [PartnerActivityController::class, 'ingest']);
    Route::post('/partner/extraction-progress', [PartnerTrackingController::class, 'progress']);
    Route::post('/partner/authorize-extraction', [PartnerExtractionController::class, 'authorizeExtraction']);
    Route::post('/partner/finalize-extraction', [PartnerExtractionController::class, 'finalizeExtraction']);
    Route::post('/partner/paystack/initialize', [PartnerCapabilityController::class, 'paystackInitialize']);
    Route::post('/partner/paystack/verify', [PartnerCapabilityController::class, 'paystackVerify']);
});

// Phase 7: User migration endpoint (admin operation, uses simple token auth in controller)
Route::post('/partner/migrate-user', [PartnerMigrationController::class, 'migrateUser']);

