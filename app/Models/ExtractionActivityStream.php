<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtractionActivityStream extends Model
{
    protected $fillable = [
        'user_id',
        'authorization_id',
        'partner_request_id',
        'partner_name',
        'partner_domain',
        'user_email',
        'extraction_type',
        'status',
        'phase',
        'last_event_key',
        'latest_sequence',
        'pages_requested',
        'pages_processed',
        'pages_with_results',
        'credits_reserved',
        'credits_consumed',
        'credits_refunded',
        'credit_outcome',
        'failed_reason',
        'run_id',
        'started_at',
        'last_event_at',
        'completed_at',
        'last_payload',
    ];

    protected $casts = [
        'latest_sequence' => 'integer',
        'pages_requested' => 'integer',
        'pages_processed' => 'integer',
        'pages_with_results' => 'integer',
        'credits_reserved' => 'integer',
        'credits_consumed' => 'integer',
        'credits_refunded' => 'integer',
        'started_at' => 'datetime',
        'last_event_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(PartnerExtractionAuthorization::class, 'authorization_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExtractionActivityEvent::class, 'stream_id');
    }
}
