<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // POST /api/auth/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = Auth::login($user);

        if (!$token) {
            return response()->json(['message' => 'Unable to generate token'], 500);
        }

        return $this->respondWithToken($token);
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Attempt to log the user in
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Optionally add a refresh token (via jwt-auth refresh flow)
        return $this->respondWithToken($token);
    }

    // POST /api/auth/logout
    public function logout()
    {
        Auth::logout(); # invalidate current token
        return response()->json(['message' => 'Successfully Logged out']);
    }

    // GET /api/auth/me
    public function me()
    {
        return response()->json(Auth::user());
    }


    // POST /api/auth/refresh
    public function refresh(JWTAuth $jwtAuth)
    {
        // Auth::refresh()
        $newToken = $jwtAuth->refresh(); # invalidate old, issue new
        return $this->respondWithToken($newToken);
    }

    // POST /api/auth/forgot-password
    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($data);
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent'])
            : response()->json(['message' => 'Unable to send reset link'], 500);
    }

    // POST /api/auth/reset-password
    public function reset(Request $request)
    {
        $data = $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function ($user) use ($data) {
            $user->forceFill(['password' => Hash::make($data['password'])])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successful'])
            : response()->json(['message' => 'Password reset failed'], 500);
    }

    /**
     * Respond with the token details.
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken(string $token)
    {
        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60
            ]
        ]);
    }
}
