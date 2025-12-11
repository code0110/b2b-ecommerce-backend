<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpSyncLog extends Model
{
    protected $fillable = [
        'entity_type',
        'direction',
        'external_id',
        'status',
        'payload',
        'message',
        'run_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'run_at'  => 'datetime',
    ];
}
