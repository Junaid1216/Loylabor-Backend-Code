<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends Controller
{
	use RedirectHelperTrait;
	
    //
	public function index()
	{
		$staffs = \App\Models\Staff::latest()->paginate(15);
		return view('admin.staff.index', compact('staffs'));
	}

	public function create()
	{
		return view('admin.staff.create');
	}

public function store(Request $request)
{
    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:staff,email',
        'phone' => 'required|string|max:20'
    ]);

    $password = 12345678;
    $staff = \App\Models\Staff::create([
        'name'  => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => bcrypt($password),
    ]);

    return redirect()->route('admin.staff.index')
        ->with('success', 'Created successfully');
}

public function edit($id)
{
	$staff = \App\Models\Staff::findOrFail($id);
	return view('admin.staff.edit', compact('staff'));
}

public function update(Request $request, $id)
{
	$request->validate([
		'name'  => 'required|string|max:255',
		'email' => 'required|email|unique:staff,email,' . $id,
		'phone' => 'required|string|max:20'
	]);

	$password = 12345678;
	$staff = \App\Models\Staff::findOrFail($id);
	$staff->update([
		'name'  => $request->name,
		'email' => $request->email,
		'phone' => $request->phone,
		'password' => bcrypt($password),
	]);

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.staff.index');

}

public function destroy($id)
{
    $staff = \App\Models\Staff::find($id);

    if (!$staff) {
        return redirect()->back()->with('error', 'Staff not found!');
    }

    // 🔥 Delete related data first
    $staff->staffPermissions()->delete();
    $staff->assignedJobs()->delete();

    // Then delete staff
    DB::table('model_has_roles')
        ->where('model_type', Staff::class)
        ->where('model_id', $staff->id)
        ->delete();

    DB::table('model_has_permissions')
        ->where('model_type', Staff::class)
        ->where('model_id', $staff->id)
        ->delete();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $staff->delete();

	        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.staff.index');

}

}
