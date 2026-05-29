<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerSecretRotationLog extends Model
{
    protected $table = 'partner_secret_rotation_log';
    protected $fillable = [
        'partner_name',
        'old_key_id',
        'new_key_id',
        'reason',
        'rotated_by_user_id',
        'reason_note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
