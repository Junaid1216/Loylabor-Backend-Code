<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpSupport;
use Illuminate\Http\Request;

class HelpSupportController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'booking_id' => 'nullable|string',
            'issue_category' => 'required|string',
            'description' => 'required|string',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'priority' => 'required|in:low,medium,high'
        ]);

        try {
            $path = null;
            if ($request->hasFile('screenshot')) {
                $path = $request->file('screenshot')->store('support', 'public');
            }

            $helpSupport = HelpSupport::create([
                'user_id' => $request->user()->id,
                'booking_id' => $request->booking_id,
                'issue_category' => $request->issue_category,
                'description' => $request->description,
                'screenshot' => $path,
                'priority' => $request->priority,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your complaint has been submitted successfully. We will get back to you soon.',
                'data' => $helpSupport
            ]);
        } catch (\Throwable $e) {
            \Log::error('Help support submit failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit complaint. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
