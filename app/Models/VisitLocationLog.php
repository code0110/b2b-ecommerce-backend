<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitLocationLog extends Model
{
    protected $fillable = [
        'customer_visit_id',
        'latitude',
        'longitude',
        'accuracy',
        'battery_level',
        'provider',
        'recorded_at',
        'speed',
        'heading',
        'altitude',
        'network_type',
        'is_mocked'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'altitude' => 'float',
        'is_mocked' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(CustomerVisit::class, 'customer_visit_id');
    }
}
