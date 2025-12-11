<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerRequest extends Model
{
    protected $fillable = [
        'company_name',
        'cif',
        'reg_com',
        'iban',
        'contact_name',
        'email',
        'phone',
        'region',
        'activity_type',
        'notes',
        'status',
        'assigned_agent_id',
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}
