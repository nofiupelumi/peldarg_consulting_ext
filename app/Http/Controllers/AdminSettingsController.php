<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CreditAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminSettingsController extends Controller
{
    private function notifyRecipients(): array
    {
        $raw = (string) config('services.contact_notify_to', '');
        $parts = array_map('trim', preg_split('/[;,]+/', $raw) ?: []);
        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        // Safe default.
        if (count($parts) === 0) {
            $parts = ['peldargconsulting@gmail.com'];
        }

        return $parts;
    }

    public function show()
    {
        $settings = AppSetting::current()->toArray();
        $settings['max_upload_mb'] = AppSetting::current()->effectiveMaxUploadMb();

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $phpUploadLimitMb = AppSetting::phpUploadLimitMb();

        $data = $request->validate([
            'unit_price_usd' => 'required|numeric|min:0',
            'fx_rate_ngn' => 'required|numeric|min:0',
            'max_upload_mb' => 'required|integer|min:1|max:' . $phpUploadLimitMb,
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

        try {
            $recipients = $this->notifyRecipients();
            $newValues = $settings->only(['unit_price_usd', 'fx_rate_ngn', 'max_upload_mb', 'admin_2fa_required']);

            Mail::raw(
                "Admin settings updated.\n\nOld:\n" . json_encode($old) . "\n\nNew:\n" . json_encode($newValues),
                fn ($message) => $message->to($recipients)->subject('Peldarg Extractor Settings Updated')
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true, 'settings' => $settings]);
    }
}
