<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'target_sales_amount',
        'target_visits_count',
        'target_new_customers'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
