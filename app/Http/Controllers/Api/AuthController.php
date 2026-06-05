<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAvailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
public function register(Request $request)
{
    // ✅ Convert JSON strings to arrays (for form-data requests)
    if ($request->has('skills') && is_string($request->skills)) {
        $request->merge(['skills' => json_decode($request->skills, true)]);
    }
    
    if ($request->has('service_area') && is_string($request->service_area)) {
        $request->merge(['service_area' => json_decode($request->service_area, true)]);
    }
    
    if ($request->has('availability') && is_string($request->availability)) {
        $request->merge(['availability' => json_decode($request->availability, true)]);
    }
    
    $request->validate([
        'user_type' => 'required|in:customer,technician',
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'phone' => 'required',
        'district_id' => 'required|exists:districts,id',
        'password' => 'required|min:6',
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
        'availability.*.day' => 'required_with:availability|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        'availability.*.start' => 'nullable|date_format:H:i',
        'availability.*.end' => 'nullable|date_format:H:i',
        'availability.*.is_available' => 'nullable|boolean',
    ]);

    $cnicFront = null;
    $cnicBack = null;
    $photo = null;
    $certificates = null;
    $skills = null;
    $service_area = null;

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

        $certificates = [];
        if ($request->hasFile('certificates')) {
            foreach ($request->file('certificates') as $file) {
                $certificates[] = $file->store('certificates', 'public');
            }
        }
        
        $skills = $request->skills ? json_encode($request->skills) : null;
        $service_area = $request->service_area ? json_encode($request->service_area) : null;
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
        'cnic_front' => $cnicFront,
        'cnic_back' => $cnicBack,
        'photo' => $photo,
        'certificates' => $certificates ? json_encode($certificates) : null,
        'bio' => $request->bio,
        'skills' => $skills,
        'experience' => $request->experience,
        'service_area' => $service_area,
        'status' => $request->user_type == 'technician' ? 'pending' : 'active',
    ]);

    if ($request->user_type == 'technician') {
        $availabilityData = $request->availability ?? $this->getDefaultAvailability();

        foreach ($availabilityData as $schedule) {
            TechnicianAvailability::create([
                'technician_id' => $user->id,
                'day' => $schedule['day'],
                'start_time' => $schedule['start'] ?? null,
                'end_time' => $schedule['end'] ?? null,
                'is_available' => $schedule['is_available'] ?? true,
                'specific_date' => $schedule['specific_date'] ?? null,
            ]);
        }
    }

    // ✅ Send OTP via email (Uncomment this)
    try {
        Mail::raw("Your OTP code is: $otp", function ($mail) use ($request) {
            $mail->to($request->email)
                 ->subject('Email Verification - Your OTP Code');
        });
    } catch (\Exception $e) {
        // Log error but don't stop registration
        \Log::error('OTP email failed: ' . $e->getMessage());
    }

    return response()->json([
        'message' => 'Registered successfully. OTP sent to your email.',
        'user' => $user->load('availabilities'),
        'token' => $user->createToken('auth')->plainTextToken,
    ], 200);
}

private function getDefaultAvailability(): array
{
    $availability = [];
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
        $availability[] = [
            'day' => $day,
            'start' => '09:00',
            'end' => '18:00',
            'is_available' => true,
        ];
    }

    $availability[] = [
        'day' => 'sunday',
        'start' => null,
        'end' => null,
        'is_available' => false,
    ];

    return $availability;
}

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'otp' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->otp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $user->update(['is_verified' => true, 'otp' => null]);

        return response()->json(['message' => 'Email verified']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
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
            'token' => $user->createToken('auth')->plainTextToken,
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json(
            $request->user()->load(['district', 'availabilities'])
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Str::random(60);
        $user->update([
            'reset_password_token' => $token,
            'reset_token_expires_at' => now()->addMinutes(30),
        ]);

        $resetLink = url("/api/reset-password?token={$token}&email={$request->email}");

        Mail::send('emails.reset-password', ['resetLink' => $resetLink, 'name' => $user->name], function ($mail) use ($request) {
            $mail->to($request->email)->subject('Reset Your Password');
        });

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed',
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
            'reset_token_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}
