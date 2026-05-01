<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerExtractionAuthorization extends Model
{
    protected $fillable = [
        'user_id',
        'partner_name',
        'partner_domain',
        'partner_user_reference',
        'partner_request_id',
        'extraction_type',
        'pages_requested',
        'pages_processed',
        'pages_with_results',
        'credits_reserved',
        'credits_consumed',
        'credits_refunded',
        'api_tier',
        'status',
        'failed_reason',
        'expires_at',
        'finalized_at',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'finalized_at' => 'datetime',
        'meta' => 'array',
        'pages_requested' => 'integer',
        'pages_processed' => 'integer',
        'pages_with_results' => 'integer',
        'credits_reserved' => 'integer',
        'credits_consumed' => 'integer',
        'credits_refunded' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}