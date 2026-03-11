<?php

namespace App\Http\Controllers;

use App\Models\CreditInvoice;
use App\Models\AppSetting;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreditInvoiceController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    public function index(Request $request)
    {
        $userId = (int) $request->session()->get('user_id');

        return CreditInvoice::query()
            ->where('user_id', $userId)
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
            $proofPath = $request->file('proof')->store('invoice-proofs');
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
            'status' => 'pending',
        ]);

        Mail::raw(
            "New credit invoice submitted.\n\nInvoice: {$invoice->invoice_number}\nUser ID: {$invoice->user_id}\nRequested Credits: {$invoice->requested_credits}\nAmount USD: {$invoice->requested_amount_usd}\nPayment Ref: {$invoice->payment_reference}\nProof Path: {$invoice->proof_path}",
            fn ($message) => $message->to('peldargconsulting@gmail.com')->subject('New Credit Top-up Invoice')
        );

        return response()->json($invoice, 201);
    }

    public function adminList(Request $request)
    {
        $status = $request->query('status');

        return CreditInvoice::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    public function approve(Request $request, CreditInvoice $invoice)
    {
        $request->validate(['admin_note' => 'nullable|string|max:1000']);
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

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, CreditInvoice $invoice)
    {
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        if ($invoice->status !== 'pending') {
            return response()->json(['error' => 'Invoice already reviewed'], 422);
        }

        $invoice->status = 'rejected';
        $invoice->admin_note = $data['admin_note'];
        $invoice->reviewed_by_user_id = (int) $request->session()->get('user_id');
        $invoice->reviewed_at = now();
        $invoice->save();

        return response()->json(['ok' => true]);
    }
}
