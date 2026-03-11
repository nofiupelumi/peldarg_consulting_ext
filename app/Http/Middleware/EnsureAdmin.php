<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) $request->session()->get('user_id');
        $user = $userId > 0 ? User::find($userId) : null;

        if (!$user || !$user->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
