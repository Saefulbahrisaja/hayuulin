<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->forceFill(['is_admin' => false])->save();

        return $this->tokenResponse($user, $validated['device_name'], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $key = Str::transliterate(Str::lower($validated['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan masuk. Coba lagi beberapa saat.',
            ], 429)->header('Retry-After', RateLimiter::availableIn($key));
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json(['message' => 'Email atau kata sandi tidak cocok.'], 401);
        }

        if ($user->is_admin) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'API aplikasi mobile hanya dapat digunakan oleh akun wisatawan.',
            ], 403);
        }

        RateLimiter::clear($key);

        return $this->tokenResponse($user, $validated['device_name']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200)
    {
        $token = $user->createToken($deviceName, ['tourist'])->plainTextToken;

        return response()->json([
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => $this->userData($user),
            ],
        ], $status);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
