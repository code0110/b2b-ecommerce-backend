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

    /**
     * Store a newly created resource in storage.
     * Proxies to update for bulk settings saving.
     */
    public function store(Request $request)
    {
        return $this->update($request);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $item) {
            // Handle boolean/integer conversion if needed based on existing type
            // But Setting::set stores as string, so we are fine.
            // We might want to look up the type to cast appropriately if we were strict.
            Setting::set($item['key'], $item['value']);
        }

        return response()->json(['message' => 'Setările au fost actualizate cu succes']);
    }

    // Public endpoint for frontend to fetch necessary configs (like max discount)
    // Be careful not to expose sensitive settings
    public function publicConfig()
    {
        $keys = [
            'offer_discount_threshold_approval',
            'offer_discount_max',
            'site_name',
            'site_description',
            'site_logo',
            'contact_phone',
            'contact_email',
            'show_vat_toggle',
            'enable_registration'
        ];

        $settings = Setting::whereIn('key', $keys)->get()->mapWithKeys(function ($s) {
            return [$s->key => $s->castValue($s->value, $s->type)];
        });

        // Dynamic overrides for authenticated users
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $discountService = app(\App\Services\DiscountRuleService::class);
            
            $settings['offer_discount_threshold_approval'] = $discountService->getApprovalThreshold($user);
            $settings['offer_discount_max'] = $discountService->getMaxDiscount($user);
        }

        return response()->json($settings);
    }
}
