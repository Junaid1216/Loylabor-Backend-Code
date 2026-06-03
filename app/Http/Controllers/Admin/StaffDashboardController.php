<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\DraftShop;
use App\Models\ShopPhoto;
use App\Models\ShopVisit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $staffId = Auth::guard('staff')->id();

        // Get dashboard statistics
        $totalShops = Shop::where('staff_id', $staffId)->count();
        $visitedToday = ShopVisit::where('staff_id', $staffId)
            ->whereDate('visited_at', today())
            ->count();
        $pendingVisits = Shop::where('staff_id', $staffId)
            ->whereDoesntHave('visits', function ($query) {
            $query->whereDate('visited_at', today());
        })
            ->count();

        // Check for existing draft
        $draft = DraftShop::where('staff_id', $staffId)->first();

        $cardData = [
            'sellChartLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'sellChartValues' => [0, 0, 0, 0, 0, 0],
            'salesChartLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'salesChartValues' => [0, 0, 0, 0, 0, 0],
        ];

        return view('staff.dashboard', compact('totalShops', 'visitedToday', 'pendingVisits', 'cardData', 'draft'));
    }

    // Get draft data - ONLY show completed steps data
    public function getDraft()
    {
        $draft = DraftShop::where('staff_id', Auth::guard('staff')->id())->first();

        if (!$draft) {
            return response()->json([
                'success' => true,
                'has_draft' => false,
                'draft' => null
            ]);
        }

        // Create clean data array with only completed steps data
        $cleanData = [];
        $completedSteps = [];

        // Check Step 1 completion (mandatory fields)
        $step1Completed = false;
        if ($draft->shop_name && $draft->category && $draft->owner_name &&
        $draft->phone_number && $draft->address) {
            $step1Completed = true;
            $completedSteps[] = 1;
            $cleanData['shop_name'] = $draft->shop_name;
            $cleanData['category'] = $draft->category;
            $cleanData['owner_name'] = $draft->owner_name;
            $cleanData['phone_number'] = $draft->phone_number;
            $cleanData['whatsapp_number'] = $draft->whatsapp_number;
            $cleanData['address'] = $draft->address;
            $cleanData['about_shop'] = $draft->about_shop;
        }

        // Check Step 2 completion
        $step2Completed = false;
        if ($step1Completed && $draft->latitude && $draft->longitude) {
            $step2Completed = true;
            $completedSteps[] = 2;
            $cleanData['latitude'] = $draft->latitude;
            $cleanData['longitude'] = $draft->longitude;
        }

        // Check Step 3 completion (photos are optional, so step is complete if step2 is complete)
        $step3Completed = false;
        if ($step2Completed) {
            $step3Completed = true;
            $completedSteps[] = 3;
        }

        // Determine current step based on completed steps
        $currentStep = 1;
        if ($step2Completed) {
            $currentStep = 3; // If step2 completed, go to step3
        }
        elseif ($step1Completed) {
            $currentStep = 2; // If step1 completed, go to step2
        }

        return response()->json([
            'success' => true,
            'has_draft' => ($step1Completed || $step2Completed) ? true : false,
            'draft' => $cleanData,
            'completed_steps' => $completedSteps,
            'current_step' => $currentStep,
            'step1_completed' => $step1Completed,
            'step2_completed' => $step2Completed,
            'step3_completed' => $step3Completed
        ]);
    }

    // Save draft data
    public function saveDraft(Request $request)
    {
        try {
            $staffId = Auth::guard('staff')->id();
            $step = $request->step;

            // Validation based on step
            if ($step == 1) {
                $validator = Validator::make($request->all(), [
                    'shop_name' => 'required|string|max:255',
                    'category' => 'required|string|max:100',
                    'owner_name' => 'required|string|max:255',
                    'phone_number' => 'required|string|max:20',
                    'whatsapp_number' => 'nullable|string|max:20',
                    'about_shop' => 'nullable|string',
                ]);
            }
            elseif ($step == 2) {
                $validator = Validator::make($request->all(), [
                    'latitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                ]);
            }
            else {
                $validator = Validator::make($request->all(), []);
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find or create draft
            $draft = DraftShop::where('staff_id', $staffId)->first();

            if (!$draft) {
                $draft = new DraftShop();
                $draft->staff_id = $staffId;
            }

            // Update draft data based on step
            if ($step == 1) {
                $draft->shop_name = $request->shop_name;
                $draft->category = $request->category;
                $draft->owner_name = $request->owner_name;
                $draft->phone_number = $request->phone_number;
                $draft->whatsapp_number = $request->whatsapp_number;
                $draft->address = $request->address;
                $draft->about_shop = $request->about_shop;
            // Don't update current_step - keep it as is
            }
            elseif ($step == 2) {
                $draft->latitude = $request->latitude;
                $draft->longitude = $request->longitude;
            }
            elseif ($step == 3) {
                // Final save - create actual shop
                return $this->finalSave($request, $draft);
            }

            $draft->save();

            return response()->json([
                'success' => true,
                'message' => 'Created successfully',
                'draft_id' => $draft->id
            ]);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save: ' . $e->getMessage()
            ], 500);
        }
    }

    // Final save - create actual shop
    private function finalSave($request, $draft)
    {
        // Get draft data
        $shopData = [
            'shop_name' => $draft->shop_name,
            'category' => $draft->category,
            'owner_name' => $draft->owner_name,
            'phone_number' => $draft->phone_number,
            'whatsapp_number' => $draft->whatsapp_number,
            'about_shop' => $draft->about_shop,
        ];

        $validator = Validator::make($shopData, [
            'shop_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'owner_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create actual shop
            $shop = Shop::create([
                'staff_id' => Auth::guard('staff')->id(),
                'shop_name' => $shopData['shop_name'],
                'category' => $shopData['category'],
                'owner_name' => $shopData['owner_name'],
                'phone_number' => $shopData['phone_number'],
                'whatsapp_number' => $shopData['whatsapp_number'],
                'address' => $shopData['address'],
                'about_shop' => $shopData['about_shop'],
                'latitude' => $shopData['latitude'],
                'longitude' => $shopData['longitude'],
                'status' => 'active'
            ]);

            // Handle photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('shop_photos', 'public');
                    ShopPhoto::create([
                        'shop_id' => $shop->id,
                        'photo_path' => $path,
                        'is_primary' => false
                    ]);
                }
            }

            // Delete draft
            $draft->delete();

            return response()->json([
                'success' => true,
                'message' => 'Created successfully',
                'shop' => $shop
            ]);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shop: ' . $e->getMessage()
            ], 500);
        }
    }

    // Clear draft
    public function clearDraft()
    {
        DraftShop::where('staff_id', Auth::guard('staff')->id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft cleared successfully'
        ]);
    }


    public function directSave(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'shop_name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'owner_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'whatsapp_number' => 'nullable|string|max:20',
                'about_shop' => 'nullable|string',
                'photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create shop directly
            $shop = Shop::create([
                'staff_id' => Auth::guard('staff')->id(),
                'shop_name' => $request->shop_name,
                'category' => $request->category,
                'owner_name' => $request->owner_name,
                'phone_number' => $request->phone_number,
                'whatsapp_number' => $request->whatsapp_number,
                'address' => $request->address,
                'about_shop' => $request->about_shop,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'pending',
                'is_verified' => false,
                'total_visits' => 0
            ]);

            // Handle photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photo) {
                    $path = $photo->store('shop_photos', 'public');
                    ShopPhoto::create([
                        'shop_id' => $shop->id,
                        'photo_path' => $path,
                        'is_primary' => $index === 0 // First photo is primary
                    ]);
                }
            }

            // Delete any existing draft for this staff
            DraftShop::where('staff_id', Auth::guard('staff')->id())->delete();

            return response()->json([
                'success' => true,
                'message' => 'Created successfully',
                'shop' => $shop
            ]);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shop: ' . $e->getMessage()
            ], 500);
        }
    }
}