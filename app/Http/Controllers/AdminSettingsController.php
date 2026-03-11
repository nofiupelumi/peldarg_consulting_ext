<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CreditAuditLog;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function show()
    {
        return AppSetting::current();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'unit_price_usd' => 'required|numeric|min:0',
            'fx_rate_ngn' => 'required|numeric|min:0',
            'max_upload_mb' => 'required|integer|min:1',
            'admin_2fa_required' => 'required|boolean',
        ]);

        $settings = AppSetting::current();
        $old = $settings->only(['unit_price_usd', 'fx_rate_ngn', 'max_upload_mb', 'admin_2fa_required']);

        $settings->fill([
            'unit_price_usd' => $data['unit_price_usd'],
            'fx_rate_ngn' => $data['fx_rate_ngn'],
            'max_upload_mb' => (int) $data['max_upload_mb'],
            'admin_2fa_required' => (bool) $data['admin_2fa_required'],
        ]);
        $settings->save();

        CreditAuditLog::create([
            'actor_user_id' => (int) $request->session()->get('user_id'),
            'target_user_id' => null,
            'event_key' => 'settings.updated',
            'entity_type' => AppSetting::class,
            'entity_id' => (int) $settings->id,
            'old_values' => $old,
            'new_values' => $settings->only(['unit_price_usd', 'fx_rate_ngn', 'max_upload_mb', 'admin_2fa_required']),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'request_id' => null,
        ]);

        return response()->json(['ok' => true, 'settings' => $settings]);
    }
}
