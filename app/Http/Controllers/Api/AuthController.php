<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TechnicianAvailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\Review;

class AuthController extends Controller
{
 public function register(Request $request)
{
    try {
        \Log::info('Registration started', $request->all());
        
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
        
        // ✅ Base validation rules
        $rules = [
            'user_type' => 'required|in:customer,technician',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'district_id' => 'required|exists:districts,id',
            'password' => 'required|min:6',
            'cnic_front' => 'nullable|file|mimes:jpg,png,jpeg,pdf|max:5120', // Increased to 5MB
            'cnic_back' => 'nullable|file|mimes:jpg,png,jpeg,pdf|max:5120',
            'photo' => 'nullable|file|mimes:jpg,png,jpeg|max:5120',
            'bio' => 'nullable|string',
            'skills' => 'nullable|array',
            'experience' => 'nullable|string',
            'service_area' => 'nullable|array',
            'availability' => 'nullable|array',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ];
        
        // Add availability validation only if provided
        if ($request->has('availability') && is_array($request->availability)) {
            $rules['availability.*.day'] = 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday';
            $rules['availability.*.start'] = 'nullable|date_format:H:i';
            $rules['availability.*.end'] = 'nullable|date_format:H:i';
            $rules['availability.*.is_available'] = 'nullable|boolean';
        }
        
        $request->validate($rules);
        
        // ✅ Ensure directory exists
        $uploadPath = public_path('backend/img');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
            \Log::info('Created directory: ' . $uploadPath);
        }
        
        $cnicFront = null;
        $cnicBack = null;
        $photo = null;
        $certificates = null;
        $skills = null;
        $service_area = null;
        
        if ($request->user_type == 'technician') {
            // ✅ Store CNIC Front
            if ($request->hasFile('cnic_front')) {
                $file = $request->file('cnic_front');
                if ($file->isValid()) {
                    $filename = 'cnic_front_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $filename);
                    $cnicFront = 'backend/img/' . $filename;
                    \Log::info('CNIC Front saved: ' . $cnicFront);
                } else {
                    \Log::error('CNIC Front file is not valid');
                }
            }
            
            // ✅ Store CNIC Back
            if ($request->hasFile('cnic_back')) {
                $file = $request->file('cnic_back');
                if ($file->isValid()) {
                    $filename = 'cnic_back_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $filename);
                    $cnicBack = 'backend/img/' . $filename;
                    \Log::info('CNIC Back saved: ' . $cnicBack);
                } else {
                    \Log::error('CNIC Back file is not valid');
                }
            }
            
            // ✅ Store Profile Photo
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($file->isValid()) {
                    $filename = 'profile_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $filename);
                    $photo = 'backend/img/' . $filename;
                    \Log::info('Profile photo saved: ' . $photo);
                } else {
                    \Log::error('Profile photo file is not valid');
                }
            }
            
            // ✅ Handle certificates
            $certificatesArray = [];
            
            if ($request->hasFile('certificates')) {
                $certificatesInput = $request->file('certificates');
                
                // Single file
                if (!is_array($certificatesInput)) {
                    if ($certificatesInput->isValid()) {
                        $filename = 'cert_' . time() . '_' . uniqid() . '.' . $certificatesInput->getClientOriginalExtension();
                        $certificatesInput->move($uploadPath, $filename);
                        $certificatesArray[] = 'backend/img/' . $filename;
                        \Log::info('Certificate saved: ' . $filename);
                    }
                } 
                // Multiple files
                else {
                    foreach ($certificatesInput as $file) {
                        if ($file && $file->isValid()) {
                            $filename = 'cert_' . time() . '_' . uniqid() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
                            $file->move($uploadPath, $filename);
                            $certificatesArray[] = 'backend/img/' . $filename;
                            \Log::info('Certificate saved: ' . $filename);
                        }
                    }
                }
            }
            
            // If certificates are sent as JSON array
            if (empty($certificatesArray) && $request->has('certificates') && is_string($request->certificates)) {
                $decoded = json_decode($request->certificates, true);
                if (is_array($decoded)) {
                    $certificatesArray = $decoded;
                }
            }
            
            $certificates = !empty($certificatesArray) ? json_encode($certificatesArray) : null;
            
            // ✅ Encode skills and service_area
            $skills = $request->has('skills') ? json_encode($request->skills) : null;
            $service_area = $request->has('service_area') ? json_encode($request->service_area) : null;
            
            \Log::info('Technician data prepared', [
                'cnic_front' => $cnicFront,
                'cnic_back' => $cnicBack,
                'photo' => $photo,
                'certificates' => $certificates,
                'skills' => $skills,
                'service_area' => $service_area
            ]);
        }
        
        $otp = rand(100000, 999999);
        
        // ✅ Create user
        $userData = [
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
            'certificates' => $certificates,
            'bio' => $request->bio,
            'skills' => $skills,
            'experience' => $request->experience,
            'service_area' => $service_area,
            'status' => $request->user_type == 'technician' ? 'pending' : 'active',
            'subscription_id' => $request->user_type == 'technician' ? $request->subscription_id : null,
        ];
        
        \Log::info('Creating user with data', $userData);
        
        $user = User::create($userData);
        
        \Log::info('User created successfully', ['user_id' => $user->id]);
        
        // ✅ Create availability for technician
        if ($request->user_type == 'technician') {
            $availabilityData = $request->has('availability') && !empty($request->availability) 
                ? $request->availability 
                : $this->getDefaultAvailability();
            
            foreach ($availabilityData as $schedule) {
                TechnicianAvailability::create([
                    'technician_id' => $user->id,
                    'day' => $schedule['day'],
                    'start_time' => $schedule['start'] ?? null,
                    'end_time' => $schedule['end'] ?? null,
                    'is_available' => $schedule['is_available'] ?? true,
                    'specific_date' => $schedule['specific_date'] ?? null,
                ]);
                \Log::info('Availability created for day: ' . $schedule['day']);
            }
        }
        
        // ✅ Send OTP via email
        try {
            Mail::raw("Your OTP code is: $otp", function ($mail) use ($request) {
                $mail->to($request->email)
                     ->subject('Email Verification - Your OTP Code');
            });
            \Log::info('OTP email sent to: ' . $request->email);
        } catch (\Exception $e) {
            \Log::error('OTP email failed: ' . $e->getMessage());
        }
        
        return response()->json([
            'message' => 'Registered successfully. OTP sent to your email.',
            'user' => $user->load('availabilities'),
            'token' => $user->createToken('auth')->plainTextToken,
        ], 200);
        
    } catch (\Exception $e) {
        \Log::error('Registration failed: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'message' => 'Registration failed: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
}

// Helper method for default availability
private function getDefaultAvailability(): array
{
    $availability = [];
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
        $availability[] = [
            'day' => $day,
            'start' => '09:00',
            'end' => '18:00',
            'is_available' => true,
            'specific_date' => null,
        ];
    }
    
    $availability[] = [
        'day' => 'sunday',
        'start' => null,
        'end' => null,
        'is_available' => false,
        'specific_date' => null,
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
			'message' => 'Logged in successfully',
            'user' => $user,
            'token' => $user->createToken('auth')->plainTextToken,
        ]);
    }
public function profile(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user->load(['district', 'availabilities', 'subscriptionPlan']);

        // =============================================
        // BASE RESPONSE
        // =============================================
        $response = [
            'id' => $user->id,
            'user_type' => $user->user_type,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo' => $user->photo ? asset($user->photo) : null,
            'address' => $user->address ?? 'Not added',
            'city' => $user->city ?? 'N/A',

            // ✅ Email Verified Status (Common for both)
            'email_verified' => $user->is_verified ? 'verified' : 'pending',

            'personal_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $user->company ?? 'N/A',
                'position' => $user->position ?? ($user->user_type === 'technician' ? $user->bio : 'Customer'),
            ],
        ];

        // =============================================
        // TECHNICIAN
        // =============================================
        if ($user->user_type === 'technician') {

            // Booking Stats
            $bookingStats = DB::table('bookings')
                ->where('technician_id', $user->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
                ')
                ->first();

            // Reviews Count
            $reviewsCount = DB::table('reviews')
                ->where('technician_id', $user->id)
                ->where('is_approved', 1)
                ->count();

            $response['stats'] = [
                'total_bookings' => $bookingStats->total ?? 0,
                'completed' => $bookingStats->completed ?? 0,
                'pending' => $bookingStats->pending ?? 0,
                'reviews' => $reviewsCount,
            ];

            $response['verifications'] = [
                'cnic_front' => $user->cnic_front_verified ? 'verified' : 'pending',
                'cnic_back' => $user->cnic_back_verified ? 'verified' : 'pending',
                'photo' => $user->photo_verified ? 'verified' : 'pending',
                'certificates' => $user->certificates_verified ? 'verified' : 'pending',
                'subscription' => $user->payment_status === 'verified' ? 'verified' : 'pending',
            ];

            $response['availability'] = $user->availabilities->map(function ($avail) {
                return [
                    'day' => ucfirst($avail->day),
                    'start_time' => $avail->start_time,
                    'end_time' => $avail->end_time,
                    'is_available' => $avail->is_available,
                ];
            });

            // Subscription Plan
            if ($user->payment_status === 'verified' && $user->subscriptionPlan) {
                $plan = $user->subscriptionPlan;
                $features = $plan->features;
                if (is_string($features)) {
                    $features = json_decode($features, true) ?? explode(',', $features);
                }
                $response['subscription_plan'] = [
                    'name' => $plan->name,
                    'duration_months' => $plan->duration_months,
                    'price_pkr' => $plan->price_pkr,
                    'saving_price' => $plan->saving_price,
                    'discount_percent' => $plan->discount_percent,
                    'features' => $features ?? [],
                    'status' => 'active',
                    'expires_at' => $user->subscription_end,
                ];
            } else {
                $response['subscription_plan'] = null;
            }

            $response['district_name'] = $user->district->name ?? 'N/A';
        }

        // =============================================
        // CUSTOMER
        // =============================================
        if ($user->user_type === 'customer') {

            // Booking Stats
            $bookingStats = DB::table('bookings')
                ->where('customer_id', $user->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
                ')
                ->first();

            // Reviews Count
            $reviewsCount = DB::table('reviews')
                ->where('customer_id', $user->id)
                ->where('is_approved', 1)
                ->count();

            $response['stats'] = [
                'total_bookings' => $bookingStats->total ?? 0,
                'completed' => $bookingStats->completed ?? 0,
                'pending' => $bookingStats->pending ?? 0,
                'reviews' => $reviewsCount,
            ];

            $response['recent_bookings'] = DB::table('bookings')
                ->join('users as technicians', 'bookings.technician_id', '=', 'technicians.id')
                ->where('bookings.customer_id', $user->id)
                ->orderBy('bookings.created_at', 'desc')
                ->limit(10)
                ->select(
                    'bookings.id',
                    'bookings.booking_reference',
                    'bookings.status',
                    'bookings.service_date',
                    'bookings.total_amount',
                    'bookings.payment_status',
                    'bookings.created_at',
                    'technicians.name as technician_name',
                    'technicians.photo as technician_photo'
                )
                ->get()
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'booking_reference' => $booking->booking_reference,
                        'status' => $booking->status,
                        'service_date' => $booking->service_date,
                        'total_amount' => $booking->total_amount,
                        'payment_status' => $booking->payment_status,
                        'technician_name' => $booking->technician_name ?? 'N/A',
                        'technician_photo' => $booking->technician_photo ? asset($booking->technician_photo) : null,
                        'created_at' => $booking->created_at,
                    ];
                });

            $response['district_name'] = $user->district->name ?? 'N/A';
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully',
            'data' => $response
        ]);

    } catch (\Exception $e) {
        Log::error('Profile Error: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Server Error',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = (string) rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'reset_password_token' => null,
            'reset_token_expires_at' => now()->addMinutes(30),
        ]);

        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour password reset OTP is: {$otp}\n\nThis code expires in 30 minutes.\n\nIf you did not request this, please ignore this email.",
                function ($mail) use ($request) {
                    $mail->to($request->email)
                         ->subject('Password Reset OTP - Home Services');
                }
            );
            Log::info('Password reset OTP email sent to: ' . $request->email);
        } catch (\Exception $e) {
            Log::error('Password reset OTP email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'otp' => 'required|string',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('reset_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'reset_password_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ];

        if ($user->user_type === 'technician') {
            $rules['bio'] = 'nullable|string';
            $rules['experience'] = 'nullable|string';
            $rules['skills'] = 'nullable|array';
            $rules['service_area'] = 'nullable|array';
        }

        $request->validate($rules);

        $data = $request->only(['name', 'phone']);

        if ($user->user_type === 'technician') {
            if ($request->has('bio')) $data['bio'] = $request->bio;
            if ($request->has('experience')) $data['experience'] = $request->experience;
            if ($request->has('skills')) {
                $data['skills'] = is_array($request->skills) ? json_encode($request->skills) : $request->skills;
            }
            if ($request->has('service_area')) {
                $data['service_area'] = is_array($request->service_area) ? json_encode($request->service_area) : $request->service_area;
            }
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'public');
            $data['photo'] = $path;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }
}