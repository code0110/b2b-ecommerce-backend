<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::all();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $item) {
            Setting::set($item['key'], $item['value']);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    // Public endpoint for frontend to fetch necessary configs (like max discount)
    // Be careful not to expose sensitive settings
    public function publicConfig()
    {
        $keys = [
            'offer_discount_threshold_approval',
            'offer_discount_max',
        ];

        $settings = Setting::whereIn('key', $keys)->get()->mapWithKeys(function ($s) {
            return [$s->key => $s->castValue($s->value, $s->type)];
        });

        return response()->json($settings);
    }
}
