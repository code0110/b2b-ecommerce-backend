<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_daily_route_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
        'battery_level',
        'is_mocked',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'is_mocked' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function dailyRoute()
    {
        return $this->belongsTo(AgentDailyRoute::class, 'agent_daily_route_id');
    }
}
