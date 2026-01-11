<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialRiskSetting;
use Illuminate\Http\Request;

class FinancialRiskSettingController extends Controller
{
    public function show()
    {
        return response()->json(FinancialRiskSetting::getSettings());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'warning_threshold_invoices' => 'required|integer|min:0',
            'approval_threshold_invoices' => 'required|integer|min:0',
            'block_threshold_invoices' => 'required|integer|min:0',
            'warning_threshold_days' => 'required|integer|min:0',
            'approval_threshold_days' => 'required|integer|min:0',
            'block_threshold_days' => 'required|integer|min:0',
        ]);

        $settings = FinancialRiskSetting::getSettings();
        $settings->update($validated);

        return response()->json([
            'message' => 'Setările de risc financiar au fost actualizate.',
            'settings' => $settings
        ]);
    }
}
