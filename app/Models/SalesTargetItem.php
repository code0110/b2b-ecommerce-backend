<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTargetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_target_id',
        'target_type',
        'target_id',
        'target_amount'
    ];

    public function salesTarget()
    {
        return $this->belongsTo(SalesTarget::class);
    }

    // Helper to get the related entity
    public function getTargetEntityAttribute()
    {
        if ($this->target_type === 'category') {
            return Category::find($this->target_id);
        }
        // Add more types here as needed
        return null;
    }
}
