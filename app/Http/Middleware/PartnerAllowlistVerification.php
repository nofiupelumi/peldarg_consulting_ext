<?php

namespace App\Http\Middleware;

use App\Models\PartnerAllowlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerAllowlistVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $partnerName = $request->header('X-Partner-Name', '');

        if (!$partnerName) {
            return response()->json(['error' => 'Missing X-Partner-Name header'], 401);
        }

        $partner = PartnerAllowlist::findByPartnerName($partnerName);
        if (!$partner) {
            return response()->json(['error' => 'Partner not found or inactive'], 401);
        }

        // Get client IP (handle proxies)
        $clientIp = $this->getClientIp($request);

        // Check if IP is in allowlist
        if (!$partner->isIpAllowed($clientIp)) {
            \Log::warning('Partner IP allowlist check failed', [
                'partner_name' => $partnerName,
                'client_ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Client IP not in allowlist'], 403);
        }

        return $next($request);
    }

    private function getClientIp(Request $request): string
    {
        // Check X-Forwarded-For first (for proxies)
        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            return trim($ips[0]);
        }

        return $request->ip() ?? '';
    }
}
