<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
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
            return redirect()->back()->with('error', 'Only technician documents can be verified.');
        }

        $column = $request->field . '_verified';
        $user->update([$column => true]);

        if ($user->fresh()->allDocumentsVerified()) {
            $user->update(['status' => 'active']);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', ucfirst(str_replace('_', ' ', $request->field)) . ' marked as verified.');
    }

    public function verifyPayment(Request $request, User $user)
    {
        if ($user->user_type !== 'technician') {
            return redirect()->back()->with('error', 'Only technician payments can be verified.');
        }

        $request->validate([
            'payment_screenshot' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $data = [
            'payment_status' => 'verified',
            'subscription' => 'active'
        ];

        if ($request->hasFile('payment_screenshot')) {
            $path = $request->file('payment_screenshot')->store('payments', 'public');
            $data['payment_screenshot'] = $path;
        }

        $user->update($data);

        if ($user->fresh()->allDocumentsVerified()) {
            $user->update(['status' => 'active']);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Payment verified successfully. Subscription is now active.');
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
            'message' => 'Email verified successfully for ' . $user->name
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to verify email: ' . $e->getMessage()
        ]);
    }
}
}
