<?php

namespace App\Http\Controllers;

use App\Models\CreditInvoice;
use App\Models\AppSetting;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreditInvoiceController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    private function notifyRecipients(): array
    {
        $raw = (string) config('services.contact_notify_to', '');
        $parts = array_map('trim', preg_split('/[;,]+/', $raw) ?: []);
        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        // Backward compatible default.
        if (count($parts) === 0) {
            $parts = ['peldargconsulting@gmail.com'];
        }

        return $parts;
    }

    public function index(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');

        // Only return manual (bank-transfer) invoices. Paystack transactions are
        // visible via the payment-history endpoint instead.
        return CreditInvoice::query()
            ->where('user_id', $userId)
            ->where('payment_source', 'manual')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');
        $data = $request->validate([
            'requested_credits' => 'required|integer|min:1',
            'payment_reference' => 'nullable|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proof = $request->file('proof');
            $originalExt = strtolower((string) $proof->getClientOriginalExtension());
            $ext = in_array($originalExt, ['jpg', 'jpeg', 'png', 'pdf'], true) ? $originalExt : 'bin';
            $proofPath = $proof->storeAs('invoice-proofs', (string) Str::uuid() . '.' . $ext);
        }

        $settings = AppSetting::current();
        $credits = (int) $data['requested_credits'];
        $unitPriceUsd = (float) $settings->unit_price_usd;
        $invoice = CreditInvoice::create([
            'user_id' => $userId,
            'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . $userId,
            'requested_credits' => $credits,
            'unit_price_usd' => $unitPriceUsd,
            'requested_amount_usd' => round($credits * $unitPriceUsd, 4),
            'payment_reference' => $data['payment_reference'] ?? null,
            'proof_path' => $proofPath,
            'payment_source' => 'manual',
            'status' => 'pending',
        ]);

        try {
            $recipients = $this->notifyRecipients();
            Mail::raw(
                "New credit top-up request submitted.\n\nInvoice: {$invoice->invoice_number}\nUser ID: {$invoice->user_id}\nRequested Credits: {$invoice->requested_credits}\nAmount USD: {$invoice->requested_amount_usd}\nPayment Ref: {$invoice->payment_reference}\nProof Path: {$invoice->proof_path}\nStatus: {$invoice->status}",
                fn ($message) => $message->to($recipients)->subject('New Credit Top-up Invoice')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json($invoice, 201);
    }

    public function adminList(Request $request)
    {
        $status = $request->query('status');

        // Admin queue is for manual (bank-transfer) invoices only.
        // Paystack invoices are auto-fulfilled and do not require admin approval.
        $list = CreditInvoice::query()
            ->with(['user:id,company_name,name'])
            ->where('payment_source', 'manual')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();

        $list->transform(function ($inv) {
            $inv->user_company_name = $inv->user?->company_name;
            $inv->user_name = $inv->user?->name;
            return $inv;
        });

        return $list;
    }

    public function approve(Request $request, CreditInvoice $invoice)
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);
        if ($invoice->payment_source === 'paystack') {
            return response()->json(['error' => 'Paystack invoices are auto-fulfilled and cannot be manually approved.'], 422);
        }
        if ($invoice->status !== 'pending') {
            return response()->json(['error' => 'Invoice already reviewed'], 422);
        }

        // Hard cap enforcement: do not allow approval if it would exceed the user's cap.
        $user = $invoice->user()->first();
        if ($user) {
            $cap = (int) $user->credit_cap;
            $balance = (int) $user->credit_balance;
            $requested = (int) $invoice->requested_credits;
            if ($cap > 0 && ($balance + $requested) > $cap) {
                return response()->json([
                    'error' => 'Approval would exceed the user\'s credit cap. Increase cap or reject/cancel this invoice.',
                    'cap' => $cap,
                    'balance' => $balance,
                    'requested_credits' => $requested,
                    'resulting_balance' => $balance + $requested,
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $invoice) {
            $invoice->status = 'approved';
            $invoice->admin_note = $request->input('admin_note');
            $invoice->reviewed_by_user_id = (int) $request->session()->get('user_id');
            $invoice->reviewed_at = now();
            $invoice->save();

            $this->creditService->adminAddCredits(
                targetUserId: $invoice->user_id,
                credits: (int) $invoice->requested_credits,
                actorUserId: (int) $request->session()->get('user_id'),
                reason: 'Invoice approved: ' . $invoice->invoice_number,
            );
        });

        try {
            $recipients = $this->notifyRecipients();
            Mail::raw(
                "Credit invoice approved.\n\nInvoice: {$invoice->invoice_number}\nUser ID: {$invoice->user_id}\nCredits: {$invoice->requested_credits}\nAmount USD: {$invoice->requested_amount_usd}\nAdmin note: {$invoice->admin_note}\nReviewed by (user id): {$invoice->reviewed_by_user_id}\nReviewed at: {$invoice->reviewed_at}",
                fn ($message) => $message->to($recipients)->subject('Credit Top-up Invoice Approved')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, CreditInvoice $invoice)
    {
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        if ($invoice->payment_source === 'paystack') {
            return response()->json(['error' => 'Paystack invoices are auto-fulfilled and cannot be manually rejected.'], 422);
        }
        if ($invoice->status !== 'pending') {
            return response()->json(['error' => 'Invoice already reviewed'], 422);
        }

        $invoice->status = 'rejected';
        $invoice->admin_note = $data['admin_note'];
        $invoice->reviewed_by_user_id = (int) $request->session()->get('user_id');
        $invoice->reviewed_at = now();
        $invoice->save();

        try {
            $recipients = $this->notifyRecipients();
            Mail::raw(
                "Credit invoice rejected.\n\nInvoice: {$invoice->invoice_number}\nUser ID: {$invoice->user_id}\nCredits: {$invoice->requested_credits}\nAmount USD: {$invoice->requested_amount_usd}\nAdmin note: {$invoice->admin_note}\nReviewed by (user id): {$invoice->reviewed_by_user_id}\nReviewed at: {$invoice->reviewed_at}",
                fn ($message) => $message->to($recipients)->subject('Credit Top-up Invoice Rejected')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}
