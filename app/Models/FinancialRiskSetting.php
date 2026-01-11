<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRiskSetting extends Model
{
    protected $fillable = [
        'warning_threshold_invoices',
        'approval_threshold_invoices',
        'block_threshold_invoices',
        'warning_threshold_days',
        'approval_threshold_days',
        'block_threshold_days',
    ];

    public static function getSettings()
    {
        return self::firstOrCreate([], [
            'warning_threshold_invoices' => 1,
            'approval_threshold_invoices' => 3,
            'block_threshold_invoices' => 5,
            'warning_threshold_days' => 7,
            'approval_threshold_days' => 15,
            'block_threshold_days' => 30,
        ]);
    }
}
