<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite; 

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_pic' => 'https://ui-avatars.com/api/?name=' . urlencode($request->username) . '&background=38a1db&color=fff'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->setAttribute('stats', $user->calculateStats());

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Support login by Email OR Username
        $fieldType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$fieldType => $request->username, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'username' => ['Invalid credentials provided.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->setAttribute('stats', $user->calculateStats());

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->setAttribute('stats', $user->calculateStats());
        return response()->json($user);
    }
    public function googleRedirect()
    {
        // Detect if request came from LAN and use appropriate redirect URI.
        // The LAN IP must be registered in Google Cloud Console under Authorized redirect URIs.
        $requestOrigin = request()->getHost();
        $isLAN = str_contains($requestOrigin, '192.168') || 
                 ($requestOrigin !== 'localhost' && $requestOrigin !== '127.0.0.1');

        $redirectUri = $isLAN 
            ? env('GOOGLE_REDIRECT_URI_LAN', 'http://192.168.1.11:8000/api/auth/google/callback')
            : env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/api/auth/google/callback');

        config(['services.google.redirect' => $redirectUri]);

        // Google requires device_id and device_name for private IP redirect URIs
        $params = [];
        if ($isLAN) {
            $params = [
                'device_id' => 'intellitravel-web-' . md5($requestOrigin),
                'device_name' => 'IntelliTravel Web App',
            ];
        }

        return Socialite::driver('google')->stateless()->with($params)->redirect();
    }

    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find existing user or create new one
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'username' => $googleUser->getName() ?? $googleUser['email'],
                    'google_id' => $googleUser->getId(),
                    'profile_pic' => $googleUser->getAvatar(),
                    'password' => null, // No password for Google users
                ]
            );
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->setAttribute('stats', $user->calculateStats());

            // Get base URL for frontend redirect (handle LAN vs localhost)
            $requestOrigin = request()->getHost();
            $isLAN = str_contains($requestOrigin, '192.168') || 
                     $requestOrigin !== 'localhost' && 
                     $requestOrigin !== '127.0.0.1';

            $frontendUrl = $isLAN 
                ? env('FRONTEND_LAN_URL', 'http://192.168.1.11:8080')
                : env('FRONTEND_URL', 'http://localhost:8080');

            return redirect("{$frontendUrl}/auth/callback?token={$token}");

        } catch (\Exception $e) {
            $requestOrigin = request()->getHost();
            $isLAN = str_contains($requestOrigin, '192.168') || 
                     $requestOrigin !== 'localhost' && 
                     $requestOrigin !== '127.0.0.1';

            $frontendUrl = $isLAN 
                ? env('FRONTEND_LAN_URL', 'http://192.168.1.11:8080')
                : env('FRONTEND_URL', 'http://localhost:8080');

            return redirect("{$frontendUrl}/login?error=Google login failed");
        }
    }
}
