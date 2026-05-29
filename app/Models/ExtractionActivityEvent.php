<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractionActivityEvent extends Model
{
    protected $fillable = [
        'stream_id',
        'partner_request_id',
        'event_key',
        'sequence',
        'status',
        'phase',
        'run_id',
        'doc_id',
        'event_at',
        'dedupe_key',
        'payload',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'doc_id' => 'integer',
        'event_at' => 'datetime',
        'payload' => 'array',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ExtractionActivityStream::class, 'stream_id');
    }
}
