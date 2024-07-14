<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomAuthenticationException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the incoming request
        $validated_data = $request->validate([
            'name' =>'required|string|max:255',
            'email' =>'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Create a new user instance
        $user = User::create($validated_data);

        return response()->json([
            'message' => 'Success',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' =>'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        
        // Attempt to authenticate the user
        if (!auth()->attempt($request->only('email', 'password'))) {
            throw new CustomAuthenticationException('Email or Password incorrect!');
        }

        $user = User::where('email', $request->get('email'))->first();

        // Generate new tokens for the authenticated user
        $accessToken = $user->createToken('accessToken', [config('auth.access-token-ability')], now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
        $refreshToken = $user->createToken('refreshToken', [config('auth.refresh-token-ability')], now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
        ]);

    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json([
               'message' => 'Logged out successfully',
            ]);
        } catch (\Throwable $th) {
            throw new AuthenticationException();
        }
    }


    public function refreshToken(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $newToken = $request->user()->createToken('accessToken', [config('auth.access-token-ability')], now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        $token = Hash::make(Str::uuid());

        $result = DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' =>now()
        ]);

        try {
            Mail::send('email.forgetPassword',['token' => $token] , function ($message) use($user) {
                $message->to('siyeb15507@calunia.com');
                $message->subject('Reset Password');
            });
        } catch (\Throwable $th) {
            throw new \Exception('Failed to send email');
        }

        return response()->json(['message' => 'We have e-mailed your password reset link!']);
    }


    public function resetPassword(Request $request)
    {
        $request->validate(['password' => 'required|string|min:8', 'token' => 'required']);

        $resetToken = DB::table('password_reset_tokens')->where('token', $request->get('token'))->first();

        if (!$resetToken) {
            return response()->json(['message' => 'Invalid Reset Token'], 404);
        }

        $user = User::where('email', $resetToken->email)->first();

        if (!$user) {
            throw new ModelNotFoundException();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('token', $resetToken->token)->delete();

        return response()->json(['message' => 'Password reset successfully']);

    }
}
