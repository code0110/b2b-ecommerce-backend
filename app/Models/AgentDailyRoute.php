<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDailyRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'total_distance',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_distance' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function routePoints()
    {
        return $this->hasMany(RoutePoint::class);
    }
}
