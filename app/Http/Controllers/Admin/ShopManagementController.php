<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Staff;
use App\Models\StaffJob;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use App\Enums\RedirectType;
use App\Traits\RedirectHelperTrait;

class ShopManagementController extends Controller
{
    use RedirectHelperTrait;
    
    public function index()
    {
        // Check if admin has permission to view shop management
        if (!auth('admin')->user()->hasPermissionTo('shop.edit')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Get only staff members who have shop_management permissions with permissable checked
        $allStaff = Staff::where('is_active', true)
            ->whereHas('staffPermissions', function($query) {
                $query->where('module', 'shop_management')
                      ->where('permissable', true);
            })
            ->get();
            
        // Get all jobs ordered by status (pending first, done last)
        $jobs = StaffJob::with(['shop', 'assignedTo', 'assignedBy'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('admin.shop-management.index', compact('jobs', 'allStaff'));
    }

    public function show($id)
    {
        // Check if admin has permission to view shop management details
        if (!auth('admin')->user()->hasPermissionTo('view shop-management')) {
            abort(403, 'Unauthorized action.');
        }
        
        $shop = Shop::with(['staff', 'jobs.assignedTo', 'jobs.assignedBy'])->findOrFail($id);
        
        // Get only staff members who have shop_management permissions with permissable checked
        $allStaff = Staff::where('is_active', true)
            ->whereHas('staffPermissions', function($query) {
                $query->where('module', 'shop_management')
                      ->where('permissable', true);
            })
            ->get();
            
        $jobTypes = StaffJob::$jobTypes;
        
        return view('admin.shop-management.show', compact('shop', 'allStaff', 'jobTypes'));
    }

    public function assignJob(Request $request, $shopId)
    {
        // Check if admin has permission to assign jobs
        if (!auth('admin')->user()->hasPermissionTo('shop.edit')) {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'assigned_to' => 'required|exists:staff,id',
            'description' => 'nullable|string|min:5',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ], [
            'assigned_to.required' => 'Please select a staff member.',
            'assigned_to.exists' => 'Selected staff member does not exist.',
            'description.min' => 'Job description must be at least 5 characters.',
            'scheduled_date.required' => 'Please select a scheduled date.',
            'scheduled_date.after_or_equal' => 'Scheduled date cannot be in the past.',
            'scheduled_time.required' => 'Please select a scheduled time.',
            'scheduled_time.date_format' => 'Please enter a valid time format (HH:MM).',
        ]);
        $shop = Shop::with('district')->findOrFail($shopId);
        
        // District validation removed per user request
        $staff = Staff::findOrFail($request->assigned_to);
        
        StaffJob::create([
            'shop_id' => $shop->id,
            'assigned_by' => auth('admin')->id(),
            'assigned_to' => $request->assigned_to,
            'job_type' => 'general', // Default job type
            'description' => $request->description,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Job assigned successfully!']);
    }

    public function shopList()
    {
        // Check if admin has permission to view shop list
        if (!auth('admin')->user()->hasPermissionTo('shop.view')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Get only staff members who have shop_management permissions with permissable checked
        $allStaff = Staff::where('is_active', true)
            ->whereHas('staffPermissions', function($query) {
                $query->where('module', 'shop_management')
                      ->where('permissable', true);
            })
            ->get();
        
        // Get all districts and categories for filtering
        $districts = \App\Models\District::all();
        $categories = \App\Models\ShopCategory::where('is_active', 1)->get();
            
        // Get all shops with their relationships and apply filters
        $query = Shop::with(['staff', 'jobs.assignedTo', 'district']);
        
        if (request()->has('district_id') && request('district_id') != '') {
            $query->where('district_id', request('district_id'));
        }
        
        if (request()->has('category') && request('category') != '') {
            $query->where('category', request('category'));
        }
        
        $shops = $query->latest()->paginate(10);
            
        return view('admin.shop-management.shopindex', compact('shops', 'allStaff', 'districts', 'categories'));
    }

    public function jobDetails(Request $request)
    {
        $job = StaffJob::with(['shop', 'assignedTo', 'assignedBy'])->findOrFail($request->id);
        
        $html = '
            <div class="row">
                <div class="col-md-6">
                    <h6>' . __('Shop Information') . '</h6>
                    <p><strong>' . __('Shop Name') . ':</strong> ' . $job->shop->shop_name . '</p>
                    <p><strong>' . __('Owner Name') . ':</strong> ' . $job->shop->owner_name . '</p>
                    <p><strong>' . __('Phone') . ':</strong> ' . $job->shop->phone_number . '</p>
                    <p><strong>' . __('Address') . ':</strong> ' . $job->shop->address . '</p>
                </div>
                <div class="col-md-6">
                    <h6>' . __('Job Information') . '</h6>
                    <p><strong>' . __('Assigned To') . ':</strong> ' . $job->assignedTo->name . '</p>
                    <p><strong>' . __('Assigned By') . ':</strong> ' . $job->assignedBy->name . '</p>
                    <p><strong>' . __('Status') . ':</strong> <span class="badge badge-' . ($job->status == 'pending' ? 'warning' : 'success') . '">' . ucfirst($job->status) . '</span></p>
                    <p><strong>' . __('Created At') . ':</strong> ' . $job->created_at->format('Y-m-d H:i') . '</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>' . __('Description') . '</h6>
                    <p>' . ($job->description ?: 'No description provided') . '</p>
                </div>
            </div>';
            
        if($job->scheduled_date) {
            $html .= '
            <div class="row mt-3">
                <div class="col-12">
                    <h6>' . __('Schedule') . '</h6>
                    <p><strong>' . __('Date') . ':</strong> ' . $job->scheduled_date . '</p>';
            if($job->scheduled_time) {
                $html .= '<p><strong>' . __('Time') . ':</strong> ' . $job->scheduled_time . '</p>';
            }
            $html .= '</div>
            </div>';
        }
        
        return response()->json(['html' => $html]);
    }

    public function jobNotes(Request $request)
    {
        $job = StaffJob::findOrFail($request->id);
        
        $html = '
            <div class="row">
                <div class="col-12">
                    <p>' . ($job->notes ?: 'No additional notes provided') . '</p>
                </div>
            </div>';
        
        return response()->json(['html' => $html]);
    }

    public function toggleJobStatus(Request $request, $id)
    {
        // Check if admin has permission to manage job status
        if (!auth('admin')->user()->hasPermissionTo('edit shop-management')) {
            abort(403, 'Access Denied. You do not have permission to manage job status.');
        }
        
        $job = StaffJob::findOrFail($id);
        
        // Use the status from request or toggle default behavior
        if ($request->has('status')) {
            $job->status = $request->status;
        } else {
            // Default toggle behavior
            $job->status = $job->status == 'pending' ? 'done' : 'pending';
        }
        
        $job->save();
        
        return response()->json([
            'message' => 'Updated successfully',
            'status' => $job->status
        ]);
    }

    public function getShopDistrict($id)
    {
        $shop = Shop::findOrFail($id);
        return response()->json(['district_id' => $shop->district_id]);
    }

    public function showJobDetails($id)
    {
        // Check if admin has permission to view shop management details
      
        
        $job = StaffJob::with(['shop', 'assignedTo', 'assignedBy'])->findOrFail($id);
        
        return view('admin.shop-management.job-details', compact('job'));
    }

    public function getStaffWithPermissions(Request $request)
    {
        $module = $request->module;
        $action = $request->action ?? 'can_view';
        
        $staff = Staff::where('is_active', true)
            ->whereHas('permissions', function($query) use ($module, $action) {
                $query->where('module', $module)->where($action, true);
            })
            ->get();

        return response()->json($staff);
    }

    public function Shopindex() {
        $categories = ShopCategory::latest()->paginate(10);
        return view('admin.shop-management.categories', compact('categories'));
    }

  public function store(Request $request)
{
    $request->validate([
        'name' => 'required|unique:shop_categories|max:255',
        'is_active' => 'required|boolean',
    ]);

    ShopCategory::create([
        'name' => $request->name,
        'is_active' => $request->is_active
    ]);

    return response()->json(['message' => 'Created successfully']);
}

// Status Update
public function updateStatus($id)
{
    $category = ShopCategory::findOrFail($id);
    $category->is_active = !$category->is_active;
    $category->save();
    
    $status = $category->is_active ? 'activated' : 'deactivated';
    return response()->json(['message' => "Updated successfully"]);
}

// Delete
public function destroy($id)
{
    $category = ShopCategory::findOrFail($id);
    
    $category->delete();
    return response()->json(['message' => 'Deleted successfully!']);
}

public function delete($id)
{
    try {
        $job = StaffJob::findOrFail($id);
        $job->delete();
        
    return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.shop-management.index');

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting job'
        ]);
    }
}

public function bulkAssign(Request $request)
{
    // Check if admin has permission to assign jobs
    if (!auth('admin')->user()->hasPermissionTo('shop.edit')) {
        abort(403, 'Unauthorized action.');
    }
    
    $request->validate([
        'shop_ids' => 'required|array',
        'shop_ids.*' => 'exists:shops,id',
        'assigned_to' => 'required|exists:staff,id',
        'description' => 'nullable|string|min:5',
        'scheduled_date' => 'required|date|after_or_equal:today',
        'scheduled_time' => 'required|date_format:H:i',
        'notes' => 'nullable|string',
    ], [
        'shop_ids.required' => 'Please select at least one shop.',
        'shop_ids.*.exists' => 'One or more selected shops do not exist.',
        'assigned_to.required' => 'Please select a staff member.',
        'assigned_to.exists' => 'Selected staff member does not exist.',
        'description.min' => 'Job description must be at least 5 characters.',
        'scheduled_date.required' => 'Please select a scheduled date.',
        'scheduled_date.after_or_equal' => 'Scheduled date cannot be in the past.',
        'scheduled_time.required' => 'Please select a scheduled time.',
        'scheduled_time.date_format' => 'Please enter a valid time format (HH:MM).',
    ]);
    
    $staff = Staff::findOrFail($request->assigned_to);
    
    // Get all shops in one query for better performance
    $shops = Shop::whereIn('id', $request->shop_ids)->get();
    
    $validShopIds = [];
    $skippedShops = [];
    
    foreach ($shops as $shop) {
        // District validation removed per user request
        $validShopIds[] = $shop->id;
    }
    
    $assignedCount = 0;
    
    // Bulk insert for better performance
    if (!empty($validShopIds)) {
        $jobsToInsert = [];
        $now = now();
        
        foreach ($validShopIds as $shopId) {
            $jobsToInsert[] = [
                'shop_id' => $shopId,
                'assigned_by' => auth('admin')->id(),
                'assigned_to' => $request->assigned_to,
                'job_type' => 'general',
                'description' => $request->description,
                'scheduled_date' => $request->scheduled_date,
                'scheduled_time' => $request->scheduled_time,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Bulk insert for performance
        StaffJob::insert($jobsToInsert);
        $assignedCount = count($validShopIds);
    }
    
    // Build response message
    if ($assignedCount > 0) {
        $message = "Successfully assigned jobs to {$assignedCount} shops.";
        
        if (!empty($skippedShops)) {
            $message .= " Skipped " . count($skippedShops) . " shops due to district mismatch: " . implode(', ', array_slice($skippedShops, 0, 3));
            if (count($skippedShops) > 3) {
                $message .= " and " . (count($skippedShops) - 3) . " more.";
            }
        }
    } else {
        $message = "No jobs were assigned. ";
        if (!empty($skippedShops)) {
            $message .= "All shops were skipped due to district mismatch.";
        } else {
            $message .= "No valid shops found.";
        }
    }
    
    return response()->json(['message' => $message]);
}

    
}
