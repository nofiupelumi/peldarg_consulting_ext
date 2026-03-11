<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'request_id',
        'filename',
        'path',
        'session',
        'status',
        'csv_url',
        'xlsx_url',
        'docx_url',
        'page_start',
        'page_end',
        'pages_requested',
        'pages_processed',
        'pages_with_results',
        'credits_reserved',
        'credits_consumed',
        'credits_refunded',
        'credit_status',
        'failed_reason',
    ];

    protected $casts = [
        'credits_reserved' => 'integer',
        'credits_consumed' => 'integer',
        'credits_refunded' => 'integer',
        'pages_requested' => 'integer',
        'pages_processed' => 'integer',
        'pages_with_results' => 'integer',
        'page_start' => 'integer',
        'page_end' => 'integer',
    ];
}
