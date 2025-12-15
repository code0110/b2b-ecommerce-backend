<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRepresentative extends Model
{
    protected $table = 'sales_representatives';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'region',
        'counties',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'counties'   => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];
}
