<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopPhoto;
use App\Traits\RedirectHelper;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    use RedirectHelperTrait;
    
    /**
     * Display a listing of the shops.
     */
    public function index()
    {
        // Check if staff has permission to view shop list
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_view')) {
            abort(403, 'You do not have permission to view shop list.');
        }

        // Get staff member's district
        $staffDistrict = auth('staff')->user()->district_id;
        
        // Get active categories and all districts for filtering
        $categories = \App\Models\ShopCategory::where('is_active', 1)->get();
        $districts = \App\Models\District::all();

        // Filter shops with applying requested filters
        $query = Shop::with('photos')->where('district_id', $staffDistrict);
        
        if (request()->has('district_id') && request('district_id') != '') {
            $query->where('district_id', request('district_id'));
        }
        
        if (request()->has('category') && request('category') != '') {
            $query->where('category', request('category'));
        }
        
        $shops = $query->latest()->paginate(10);
            
        return view('staff.shop.index', compact('shops', 'categories', 'districts'));
    }

    /**
     * Show the form for creating a new shop.
     */
    public function create()
    {
        // Check if staff has permission to create shops
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_create')) {
            abort(403, 'You do not have permission to create shops.');
        }

        $shopCategories = ShopCategory::where('is_active', 1)->get();
        $staffDistrict = auth('staff')->user()->district;
        return view('staff.shop.create', compact('shopCategories', 'staffDistrict'));
    }

    /**
     * Store a newly created shop in storage.
     */
   public function store(Request $request)
{
    // Check if staff has permission to create shops
    if (!auth('staff')->user()->hasPermission('shop_management', 'can_create')) {
        abort(403, 'You do not have permission to create shops.');
    }

    // Get active categories
    $activeCategories = ShopCategory::where('is_active', 1)->pluck('name')->toArray();

    // Validation rules with custom messages
    $validated = $request->validate([
        'shop_name' => 'required|string|max:255',
        'category' => [
            'required',
            Rule::in($activeCategories)
        ],
        'owner_name' => 'required|string|max:255',
        'phone_number' => 'required|string|max:20',
        'whatsapp_number' => 'required|string|max:20',
        'address' => 'required|string',
        'location' => 'nullable|string',
        'about_shop' => 'required|string',
        'shop_photos' => 'nullable|array',
        'shop_photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
    ], [
        // Custom error messages without array index
        'shop_name.required' => 'Shop name is required.',
        'shop_name.max' => 'Shop name must not exceed 255 characters.',
        
        'category.required' => 'Please select a category.',
        'category.in' => 'Selected category is invalid or inactive.',
        
        'owner_name.required' => 'Owner name is required.',
        'owner_name.max' => 'Owner name must not exceed 255 characters.',
        
        'phone_number.required' => 'Phone number is required.',
        'phone_number.max' => 'Phone number must not exceed 20 characters.',
        
        'whatsapp_number.required' => 'WhatsApp number is required.',
        'whatsapp_number.max' => 'WhatsApp number must not exceed 20 characters.',
        
        'address.required' => 'Address is required.',
        
        'about_shop.required' => 'Please provide information about the shop.',
        
        'shop_photos.*.image' => 'Each file must be a valid image.',
        'shop_photos.*.mimes' => 'Photo must be a file of type: JPEG, PNG, JPG, or GIF.',
        'shop_photos.*.max' => 'Each photo must not exceed 2MB in size.',
    ]);

    // Create shop record
    $shop = Shop::create([
        'shop_name' => $validated['shop_name'],
        'category' => $validated['category'],
        'owner_name' => $validated['owner_name'],
        'phone_number' => $validated['phone_number'],
        'whatsapp_number' => $validated['whatsapp_number'],
        'address' => $validated['address'],
        'location' => $validated['location'] ?? auth('staff')->user()->location ?? 'University of Management & Technology C-II Block C 2 Phase 1 Johar Town, Lahore, 54770, Pakistan',
        'about_shop' => $validated['about_shop'],
        'slug' => Str::slug($validated['shop_name']) . '-' . uniqid(),
        'staff_id' => auth('staff')->id(),
        'district_id' => auth('staff')->user()->district_id,
    ]);

    // Handle multiple photos upload
    $photoErrors = [];
    if ($request->hasFile('shop_photos')) {
        foreach ($request->file('shop_photos') as $index => $photo) {
            try {
                $publicPath = public_path('storage/shop-photos');
                if (!file_exists($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }
                
                $filename = 'shop_' . $shop->id . '_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                
                if ($photo->move($publicPath, $filename)) {
                    ShopPhoto::create([
                        'shop_id' => $shop->id,
                        'photo_path' => 'shop-photos/' . $filename,
                        'is_primary' => false,
                    ]);
                }
            } catch (\Exception $e) {
                $photoErrors[] = "Photo " . ($index + 1) . " could not be uploaded.";
                \Log::error('Photo upload failed: ' . $e->getMessage());
            }
        }

        // Set the first photo as primary
        if ($shop->photos()->count() > 0) {
            $firstPhoto = $shop->photos()->first();
            $firstPhoto->update(['is_primary' => true]);
        }
    }

    if ($request->ajax()) {
        $response = [
            'success' => true,
            'message' => 'Created successfully',
            'shop' => $shop
        ];
        
        if (!empty($photoErrors)) {
            $response['photo_warnings'] = $photoErrors;
        }
        
        return response()->json($response);
    }

    return $this->redirectWithMessage(RedirectType::CREATE->value, 'staff.shop.index');
}
    /**
     * Display the specified shop.
     */
    public function show($id)
    {
        $shop = Shop::with('photos')->findOrFail($id);
        return view('staff.shop.show', compact('shop'));
    }

    /**
     * Show the form for editing the specified shop.
     */
    public function edit($id)
    {
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_edit')) {
            abort(403, 'You do not have permission to edit shops.');
        }
        
        $shop = Shop::with('photos')->findOrFail($id);
        $shopCategories = ShopCategory::where('is_active', 1)->get();
        $staffDistrict = auth('staff')->user()->district;
        return view('staff.shop.edit', compact('shop', 'shopCategories', 'staffDistrict'));
    }

    /**
     * Update the specified shop in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_edit')) {
            abort(403, 'You do not have permission to edit shops.');
        }
        
        $shop = Shop::findOrFail($id);

        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'category' => [
                'required',
                Rule::in(ShopCategory::where('is_active', 1)->pluck('name')->toArray())
            ],
            'owner_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'whatsapp_number' => 'required|string|max:20',
            'address' => 'required|string',
            'location' => 'nullable|string',
            'about_shop' => 'required|string',
            'district_id' => 'required|exists:districts,id',
            'shop_photos' => 'nullable|array',
            'shop_photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $shop->update([
            'shop_name' => $validated['shop_name'],
            'category' => $validated['category'],
            'owner_name' => $validated['owner_name'],
            'phone_number' => $validated['phone_number'],
            'whatsapp_number' => $validated['whatsapp_number'],
            'address' => $validated['address'],
            'location' => $validated['location'] ?? auth('staff')->user()->location ?? 'University of Management & Technology C-II Block C 2 Phase 1 Johar Town, Lahore, 54770, Pakistan',
            'about_shop' => $validated['about_shop'],
            'district_id' => $validated['district_id'],
            'slug' => Str::slug($validated['shop_name']) . '-' . uniqid(),
        ]);

        // Handle new photos upload
        if ($request->hasFile('shop_photos')) {
            foreach ($request->file('shop_photos') as $photo) {
                try {
                    $publicPath = public_path('storage/shop-photos');
                    if (!file_exists($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }
                    
                    $filename = 'shop_' . $shop->id . '_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    
                    if ($photo->move($publicPath, $filename)) {
                        ShopPhoto::create([
                            'shop_id' => $shop->id,
                            'photo_path' => 'shop-photos/' . $filename,
                            'is_primary' => false,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Photo upload failed: ' . $e->getMessage());
                }
            }
        }

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'staff.shop.index');
    }

    /**
     * Remove the specified shop from storage.
     */
    public function destroy($id)
    {
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_delete')) {
            abort(403, 'You do not have permission to delete shops.');
        }
        
        $shop = Shop::findOrFail($id);

        // Delete all associated photos from public path
        foreach ($shop->photos as $photo) {
            $filePath = public_path('storage/' . $photo->photo_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $photo->delete();
        }

        $shop->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'staff.shop.index');
    }

    /**
     * Delete a specific photo from a shop.
     */
    public function deletePhoto($photoId)
    {
        // Check edit permission
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete photos.'
            ], 403);
        }
        
        try {
            $photo = ShopPhoto::findOrFail($photoId);
            $shopId = $photo->shop_id;
            
            // Delete the file from public path (CORRECTED PATH)
            $filePath = public_path('storage/' . $photo->photo_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete the record
            $photo->delete();
            
            // Check if there are any photos left
            $remainingPhotos = ShopPhoto::where('shop_id', $shopId)->count();
            
            // If there are photos but no primary photo, set the first one as primary
            if($remainingPhotos > 0) {
                $hasPrimary = ShopPhoto::where('shop_id', $shopId)->where('is_primary', true)->exists();
                if(!$hasPrimary) {
                    $firstPhoto = ShopPhoto::where('shop_id', $shopId)->first();
                    $firstPhoto->is_primary = true;
                    $firstPhoto->save();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set primary photo
     */
    public function setPrimaryPhoto(Request $request)
    {
        // Check edit permission
        if (!auth('staff')->user()->hasPermission('shop_management', 'can_edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit shops.'
            ], 403);
        }
        
        try {
            // Check if photo_id exists in request
            if (!$request->has('photo_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photo ID is required.'
                ], 400);
            }
            
            $photoId = $request->photo_id;
            
            // Check if photo exists
            $photo = ShopPhoto::find($photoId);
            if (!$photo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photo not found.'
                ], 404);
            }
            
            // Remove primary status from all photos of this shop
            ShopPhoto::where('shop_id', $photo->shop_id)->update(['is_primary' => false]);
            
            // Set this photo as primary
            $photo->is_primary = true;
            $photo->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Set primary photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary photo: ' . $e->getMessage()
            ], 500);
        }
    }
}