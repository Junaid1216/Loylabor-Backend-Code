<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::latest();

        if ($request->filled('type')) {
            if ($request->type === 'user') {
                $query->where('user_type', 'customer');
            } elseif ($request->type === 'technician') {
                $query->where('user_type', 'technician');
            }
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'user_type' => 'required|in:customer,technician'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'status' => 'active',
            'is_verified' => 1
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'user_type' => 'required|in:customer,technician'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'user_type' => $request->user_type,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function show(User $user)
    {
        if ($user->user_type === 'technician') {
            $user->load(['availabilities', 'subscriptionPlan']);
        }

        return view('admin.users.show', compact('user'));
    }

    public function verifyDocument(Request $request, User $user)
    {
        $request->validate([
            'field' => 'required|in:cnic_front,cnic_back,photo,certificates',
        ]);

        if ($user->user_type !== 'technician') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only technician documents can be verified.'], 422);
            }

            return redirect()->back()->with('error', 'Only technician documents can be verified.');
        }

        $wasPending = $user->status !== 'active';
        $column = $request->field . '_verified';
        $user->update([$column => true]);

        if ($user->fresh()->allDocumentsVerified()) {
            $user->update(['status' => 'active']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Verified successfully.',
                'status_updated' => $wasPending && $user->fresh()->status === 'active',
            ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Verified successfully.');
    }

    public function verifyPayment(Request $request, User $user)
    {
        if ($user->user_type !== 'technician') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only technician payments can be verified.'], 422);
            }

            return redirect()->back()->with('error', 'Only technician payments can be verified.');
        }

        $user->update([
            'payment_status' => 'verified',
            'subscription' => 'active',
        ]);

        if ($user->fresh()->allDocumentsVerified()) {
            $user->update(['status' => 'active']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Verified successfully.',
            ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Verified successfully.');
    }

	public function verifyEmail(Request $request, User $user)
{
    try {
        // Check if already verified
        if ($user->is_verified == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified!'
            ]);
        }
        
        // Update is_verified to 1
        $user->is_verified = 1;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Verified successfully.',
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to verify email: ' . $e->getMessage()
        ]);
    }
}
}
