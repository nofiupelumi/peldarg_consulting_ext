<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditAuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'target_user_id',
        'event_key',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
