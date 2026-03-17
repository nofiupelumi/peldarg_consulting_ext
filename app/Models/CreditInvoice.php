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
        'proof_path',
        'status',
        'admin_note',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
