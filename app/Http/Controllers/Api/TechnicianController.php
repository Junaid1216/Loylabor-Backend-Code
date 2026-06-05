<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAvailability;
use App\Models\User;
use Illuminate\Http\Request;

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

    // Activate subscription (after verification)
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
            'plan' => 'required|in:basic,premium',
            'payment_method' => 'required'
        ]);

        $user->update([
            'subscription' => 'active',
            'subscription_end' => now()->addDays(30)
        ]);

        if ($user->status == 'review') {
            $user->update(['status' => 'active']);
        }

        return response()->json([
            'message' => 'Subscription activated. Account is now LIVE!',
            'subscription_end' => $user->subscription_end
        ]);
    }

    // Get technician status
    public function status(Request $request)
    {
        $user = $request->user();
        
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
            'subscription_active' => $user->subscription == 'active' && ($user->subscription_end > now()),
            'subscription_end' => $user->subscription_end,
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
                    ->where('subscription', 'active')
                    ->where('subscription_end', '>', now());
        
        // Category filter - agar category di hai to filter, nahi to sab
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }
        
        $technicians = $query->with('district')->get();
        
        return response()->json([
            'success' => true,
            'category' => $request->category ?? 'all',
            'total' => $technicians->count(),
            'data' => $technicians
        ]);
    }

}
