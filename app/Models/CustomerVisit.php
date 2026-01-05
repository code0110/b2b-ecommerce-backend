<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'customer_id',
        'status',
        'start_time',
        'end_time',
        'latitude',
        'longitude',
        'end_latitude',
        'end_longitude',
        'distance_deviation',
        'is_off_site',
        'notes',
        'outcome',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
        'is_off_site' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_visit_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_visit_id');
    }

    public function locationLogs()
    {
        return $this->hasMany(VisitLocationLog::class, 'customer_visit_id');
    }
}
