<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAvailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TechnicianController extends Controller
{
    //
	public function submitVerification(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type != 'technician') {
            return response()->json(['error' => 'Not a technician'], 403);
        }

        if (!$user->cnic_front || !$user->cnic_back || !$user->photo) {
            return response()->json(['error' => 'Upload CNIC front, back and photo first'], 400);
        }

        $user->update(['status' => 'review']);
        
        return response()->json(['message' => 'Documents submitted for review', 'status' => $user->status]);
    }

    public function activateSubscription(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type != 'technician') {
            return response()->json(['error' => 'Not a technician'], 403);
        }

        if ($user->status != 'active' && $user->status != 'review') {
            return response()->json(['error' => 'Account not verified yet'], 400);
        }

        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_method' => 'required',
            'payment_screenshot' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $path = $request->file('payment_screenshot')->store('payments', 'public');
        $subscription = \App\Models\Subscription::find($request->subscription_id);

        $user->update([
            'subscription_id' => $subscription->id,
            'payment_screenshot' => $path,
            'payment_status' => 'pending',
            'subscription_end' => now()->addMonths($subscription->duration_months)
        ]);

        return response()->json([
            'message' => 'Payment screenshot uploaded. Waiting for admin verification.',
            'subscription_end' => $user->subscription_end
        ]);
    }

    // Get technician status
    public function status(Request $request)
    {
        $user = $request->user()->load('subscriptionPlan');
        
        return response()->json([
            'documents' => [
                'cnic_front' => !is_null($user->cnic_front),
                'cnic_back' => !is_null($user->cnic_back),
                'photo' => !is_null($user->photo),
                'certificates' => !empty($user->certificates),
            ],
            'verification' => [
                'cnic_front_verified' => (bool) $user->cnic_front_verified,
                'cnic_back_verified' => (bool) $user->cnic_back_verified,
                'photo_verified' => (bool) $user->photo_verified,
                'certificates_verified' => (bool) $user->certificates_verified,
            ],
            'account_status' => $user->status,
            'payment_status' => $user->payment_status ?? 'none',
            'subscription_active' => $user->subscription == 'active' && ($user->subscription_end > now()),
            'subscription_end' => $user->subscription_end,
            'subscription_plan' => $user->subscriptionPlan ? [
                'id' => $user->subscriptionPlan->id,
                'name' => $user->subscriptionPlan->name,
                'permissions' => is_array($user->subscriptionPlan->features)
                    ? $user->subscriptionPlan->features
                    : explode(',', $user->subscriptionPlan->features ?? ''),
            ] : null,
            'availability' => $user->availabilities,
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $user = $request->user();

        if ($user->user_type != 'technician') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'availability' => 'required|array',
            'availability.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'availability.*.start' => 'nullable|date_format:H:i',
            'availability.*.end' => 'nullable|date_format:H:i',
            'availability.*.is_available' => 'boolean',
        ]);

        foreach ($request->availability as $schedule) {
            TechnicianAvailability::updateOrCreate(
                [
                    'technician_id' => $user->id,
                    'day' => $schedule['day'],
                    'specific_date' => $schedule['specific_date'] ?? null,
                ],
                [
                    'start_time' => $schedule['start'] ?? null,
                    'end_time' => $schedule['end'] ?? null,
                    'is_available' => $schedule['is_available'] ?? true,
                ]
            );
        }

        return response()->json([
            'message' => 'Availability updated successfully',
            'availability' => TechnicianAvailability::where('technician_id', $user->id)->get(),
        ]);
    }

    public function toggleDayAvailability(Request $request)
    {
        $user = $request->user();

        if ($user->user_type != 'technician') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_available' => 'required|boolean',
        ]);

        $availability = TechnicianAvailability::where([
            'technician_id' => $user->id,
            'day' => $request->day,
        ])->first();

        if ($availability) {
            $availability->update(['is_available' => $request->is_available]);
        }

        return response()->json([
            'message' => $request->is_available
                ? "Now available on {$request->day}"
                : "Now off on {$request->day}",
            'availability' => $availability,
        ]);
    }

    public function getTechnicians(Request $request)
    {
        $query = User::where('user_type', 'technician')
            ->where('status', 'active')
            ->where('is_verified', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        $technicians = $query->with(['district', 'availabilities'])->get()->map(function ($tech) {
            $skills = $tech->skills;
            if (is_string($skills)) {
                $skills = json_decode($skills, true) ?? [];
            }

            return [
                'id' => $tech->id,
                'name' => $tech->name,
                'email' => $tech->email,
                'phone' => $tech->phone,
                'photo' => $tech->photo ? asset($tech->photo) : null,
                'bio' => $tech->bio,
                'experience' => $tech->experience,
                'skills' => $skills ?? [],
                'service_area' => $tech->service_area ?? [],
                'district' => $tech->district ? [
                    'id' => $tech->district->id,
                    'name' => $tech->district->name,
                ] : null,
                'availability' => $tech->availabilities->map(function ($avail) {
                    return [
                        'day' => $avail->day,
                        'start_time' => $avail->start_time,
                        'end_time' => $avail->end_time,
                        'is_available' => (bool) $avail->is_available,
                    ];
                }),
                'rating' => Schema::hasTable('reviews')
                    ? round((float) DB::table('reviews')
                        ->where('technician_id', $tech->id)
                        ->where('is_approved', 1)
                        ->avg('rating'), 1)
                    : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => $request->category ?? 'all',
            'total' => $technicians->count(),
            'data' => $technicians,
        ]);
    }

    public function getSubscriptions()
    {
        $subscriptions = \App\Models\Subscription::where('is_active', 1)->get()->map(function($sub) {
            $features = is_array($sub->features) ? $sub->features : json_decode($sub->features, true);
            if (!is_array($features)) {
                $features = explode(',', $sub->features);
            }
            
            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'duration_months' => $sub->duration_months,
                'price_pkr' => $sub->price_pkr,
                'discount_percent' => $sub->discount_percent,
                'tax_percent' => $sub->tax_percent,
                'features' => $features
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

}
