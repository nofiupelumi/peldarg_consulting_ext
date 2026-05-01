<?php

namespace App\Http\Controllers;

use App\Models\CreditInvoice;
use App\Models\User;
use App\Services\PaystackPaymentService;
use Illuminate\Http\Request;

class PaystackPaymentController extends Controller
{
    public function __construct(private PaystackPaymentService $paystack)
    {
    }

    public function initialize(Request $request)
    {
        $data = $request->validate([
            'requested_credits' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail((int) $request->session()->get('user_id'));
        $result = $this->paystack->initializeForUser($user, (int) $data['requested_credits']);
        /** @var CreditInvoice $invoice */
        $invoice = $result['invoice'];

        return response()->json([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reference' => $invoice->gateway_reference,
            'access_code' => $invoice->gateway_access_code,
            'authorization_url' => $invoice->gateway_authorization_url,
            'amount_ngn_kobo' => (int) $invoice->amount_ngn_kobo,
            'requested_credits' => (int) $invoice->requested_credits,
            'reused' => (bool) ($result['reused'] ?? false),
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string|max:255',
        ]);

        $userId = (int) $request->session()->get('user_id');
        $result = $this->paystack->verifyAndFulfill((string) $data['reference'], $userId);
        /** @var CreditInvoice $invoice */
        $invoice = $result['invoice'];

        return response()->json([
            'handled' => (bool) ($result['handled'] ?? false),
            'already_fulfilled' => (bool) ($result['already_fulfilled'] ?? false),
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'gateway_status' => $invoice->gateway_status,
            'credit_balance' => $result['credit_balance'] ?? null,
        ]);
    }
}