<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $guarded = [];

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
