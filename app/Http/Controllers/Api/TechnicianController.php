<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'cnic' => !is_null($user->cnic_front),
                'photo' => !is_null($user->photo),
            ],
            'account_status' => $user->status,
            'subscription_active' => $user->subscription == 'active' && ($user->subscription_end > now()),
            'subscription_end' => $user->subscription_end
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
