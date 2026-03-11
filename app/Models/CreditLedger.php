<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLedger extends Model
{
    protected $fillable = [
        'user_id',
        'document_id',
        'invoice_id',
        'action_type',
        'credits',
        'balance_before',
        'balance_after',
        'unit_price_usd',
        'amount_usd',
        'meta',
        'created_by_user_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
