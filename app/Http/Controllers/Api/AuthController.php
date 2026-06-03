<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ONE REGISTER FUNCTION - handles both customer & technician
    public function register(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:customer,technician',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'district_id' => 'required|exists:districts,id',
            'password' => 'required|min:6',
            // Technician optional fields
            'cnic_front' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'cnic_back' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'photo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'certificates' => 'nullable|array',
            'certificates.*' => 'image|mimes:jpg,png,jpeg,pdf|max:2048',
            'bio' => 'nullable|string',
            'skills' => 'nullable|array',
            'experience' => 'nullable|string',
            'service_area' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        // Upload files if technician
        $cnicFront = null;
        $cnicBack = null;
        $photo = null;
        $certificates = [];

        if ($request->user_type == 'technician') {
            if ($request->hasFile('cnic_front')) {
                $cnicFront = $request->file('cnic_front')->store('cnic', 'public');
            }
            if ($request->hasFile('cnic_back')) {
                $cnicBack = $request->file('cnic_back')->store('cnic', 'public');
            }
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo')->store('photos', 'public');
            }
            if ($request->hasFile('certificates')) {
                foreach ($request->file('certificates') as $file) {
                    $certificates[] = $file->store('certificates', 'public');
                }
            }
        }

        $otp = rand(100000, 999999);

        $user = User::create([
            'user_type' => $request->user_type,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'district_id' => $request->district_id,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            // Technician fields
            'cnic_front' => $cnicFront,
            'cnic_back' => $cnicBack,
            'photo' => $photo,
            'certificates' => $certificates,
            'bio' => $request->bio,
            'skills' => $request->skills,
            'experience' => $request->experience,
            'service_area' => $request->service_area,
            'availability' => $request->availability,
            'status' => $request->user_type == 'technician' ? 'pending' : 'active',
        ]);

        // Send OTP
        Mail::raw("Your OTP is: $otp", function($mail) use ($request) {
            $mail->to($request->email)->subject('Email Verification');
        });

        return response()->json([
            'message' => 'Registered. OTP sent to email.',
            'user' => $user,
            'token' => $user->createToken('auth')->plainTextToken
        ], 201);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'otp' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        
        if ($user->otp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $user->update(['is_verified' => true, 'otp' => null]);

        return response()->json(['message' => 'Email verified']);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_verified) {
            return response()->json(['error' => 'Verify email first'], 401);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth')->plainTextToken
        ]);
    }

    // Get profile
    public function profile(Request $request)
    {
        return response()->json($request->user()->load('district'));
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

	// app/Http/Controllers/Api/AuthController.php

// Function 1: Forgot Password - Send Reset Link
public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users'
    ]);

    $user = User::where('email', $request->email)->first();
    
    // Generate token
    $token = Str::random(60);
    $user->update([
        'reset_password_token' => $token,
        'reset_token_expires_at' => now()->addMinutes(30)
    ]);
    
    // Send email
    $resetLink = url("/api/reset-password?token={$token}&email={$request->email}");
    
    Mail::send('emails.reset-password', ['resetLink' => $resetLink, 'name' => $user->name], function($mail) use ($request) {
        $mail->to($request->email)->subject('Reset Your Password');
    });
    
    return response()->json([
        'success' => true,
        'message' => 'Password reset link sent to your email'
    ]);
}

// Function 2: Reset Password
public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users',
        'token' => 'required|string',
        'password' => 'required|min:6|confirmed'
    ]);
    
    $user = User::where('email', $request->email)
                ->where('reset_password_token', $request->token)
                ->where('reset_token_expires_at', '>', now())
                ->first();
    
    if (!$user) {
        return response()->json(['error' => 'Invalid or expired token'], 400);
    }
    
    $user->update([
        'password' => Hash::make($request->password),
        'reset_password_token' => null,
        'reset_token_expires_at' => null
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);
}
}