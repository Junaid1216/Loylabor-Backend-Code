<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = Subscription::latest()->paginate(20);
        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.subscriptions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:100',
        'duration_months' => 'required|integer|min:1',
        'price_pkr' => 'required|numeric|min:0',
        'saving_price' => 'nullable|numeric|min:0',
        'features' => 'nullable|string',
        'discount_percent' => 'nullable|integer|min:0|max:100',
        'tax_percent' => 'nullable|integer|min:0|max:100',
        'is_active' => 'required|in:0,1',
    ]);

    $featuresArray = $request->features ? array_values(array_filter(array_map('trim', explode("\n", $request->features)))) : [];

    Subscription::create([
        'name' => $request->name,
        'duration_months' => (int) $request->duration_months,
        'price_pkr' => $request->price_pkr,
        'saving_price' => $request->saving_price ?? 0,
        'features' => $featuresArray,
        'discount_percent' => $request->discount_percent ?? 0,
        'tax_percent' => $request->tax_percent ?? 10,
        'is_active' => (int) $request->is_active,
    ]);

    return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription plan created successfully.');
}

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        return view('admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        return view('admin.subscriptions.edit', compact('subscription'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'duration_months' => 'required|integer|min:1',
            'price_pkr' => 'required|numeric|min:0',
            'features' => 'nullable|string',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'tax_percent' => 'nullable|integer|min:0|max:100',
            'is_active' => 'required|in:0,1',
        ]);

        $data = $request->all();
        $data['is_active'] = (int) $request->is_active;
        $data['features'] = $request->features ? array_values(array_filter(array_map('trim', explode("\n", $request->features)))) : [];
        $subscription->update($data);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription plan deleted successfully.');
    }
}
