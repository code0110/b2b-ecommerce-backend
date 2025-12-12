<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRepresentative extends Model
{
    protected $fillable = [
        'name',
        'region',
        'counties',
        'phone',
        'email',
        'territory_code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
