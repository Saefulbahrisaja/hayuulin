<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouristOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_admin) {
            return response()->json([
                'message' => 'API ini hanya tersedia untuk akun wisatawan.',
            ], 403);
        }

        return $next($request);
    }
}
