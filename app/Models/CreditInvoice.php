<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_number',
        'requested_credits',
        'unit_price_usd',
        'requested_amount_usd',
        'payment_reference',
        'payment_provider',
        'gateway_reference',
        'gateway_access_code',
        'gateway_authorization_url',
        'gateway_status',
        'amount_ngn_kobo',
        'payment_payload',
        'proof_path',
        'status',
        'admin_note',
        'reviewed_by_user_id',
        'reviewed_at',
        'initialized_at',
        'paid_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'initialized_at' => 'datetime',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'payment_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
