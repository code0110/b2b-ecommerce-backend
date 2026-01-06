<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'agent_id',
        'customer_id',
        'customer_visit_id',
        'status',
        'total_amount',
        'discount_amount',
        'notes',
        'requires_director_approval',
        'valid_until',
        'quote_request_id'
    ];

    public function visit()
    {
        return $this->belongsTo(CustomerVisit::class, 'customer_visit_id');
    }

    protected $casts = [
        'valid_until' => 'datetime',
        'requires_director_approval' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OfferItem::class);
    }

    public function messages()
    {
        return $this->hasMany(OfferMessage::class);
    }
}
