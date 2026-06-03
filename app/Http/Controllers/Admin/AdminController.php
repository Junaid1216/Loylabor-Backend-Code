<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    use RedirectHelperTrait;

    public function index()
    {
        checkAdminHasPermissionAndThrowException('admin.view');

        $admins = Admin::notSuperAdmin()->paginate(15);

        return view('admin.admin-list.admin')->with([
            'admins' => $admins,
        ]);

    }

    public function create()
    {
        checkAdminHasPermissionAndThrowException('admin.create');
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        if (!$roles->count()) {
            $notification = __('No role found! First, create at least one role. Then, create the admin.');
            $notification = ['message' => $notification, 'alert-type' => 'warning'];

            return to_route('admin.role.create')->with($notification);
        }

        return view('admin.admin-list.create_admin', compact('roles'));
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|unique:admins',
            'status' => 'required',
        ];
        $customMessages = [
            'name.required' => __('Name is required'),
            'email.required' => __('Email is required'),
            'status.required' => __('Status is required'),
            'email.unique' => __('Email already exist'),
            'role.array' => __('You must select role'),
        ];
        $this->validate($request, $rules, $customMessages);
        $password =12345678;

        $admin = new Admin;
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->status = $request->status;
        $admin->password = Hash::make($password);
        $admin->save();
        if ($request->role) {
            $admin->syncRoles($request->role);
        }

        return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.admin.index');
    }

    public function edit($id)
    {
        checkAdminHasPermissionAndThrowException('admin.edit');
        $admin = Admin::notSuperAdmin()->findOrFail($id);
        $roles = Role::where('name', '!=', 'Super Admin')->get();

        return view('admin.admin-list.edit_admin', compact('roles', 'admin'));
    }

    /**
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::notSuperAdmin()->find($id);
        $rules = [
            'name' => 'required',
            'email' => 'required|unique:admins,email,' . $admin->id,
            'status' => 'required',
        ];
        $customMessages = [
            'name.required' => __('Name is required'),
            'email.required' => __('Email is required'),
            'email.unique' => __('Email already exist'),
            'role.array' => __('You must select role'),
        ];
        $this->validate($request, $rules, $customMessages);
        $password=12345678;

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->status = $request->status;
        $admin->password = Hash::make($password);

        $admin->save();
        if ($request->role) {
            $admin->syncRoles($request->role);
        }

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.admin.index');
    }

    /**
     * @return mixed
     */
    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('admin.delete');
        $admin = Admin::notSuperAdmin()->findOrFail($id);
        abort_if($admin->id == 1, 403);
        $admin->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.admin.index');
    }

    public function changeStatus($id)
    {
        
        $admin = Admin::notSuperAdmin()->find($id);
        $status = $admin->status == 'active' ? 'inactive' : 'active';
        $admin->status = $status;
        $admin->save();
        $notification = __('Updated Successfully');

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
        ]);
    }
}
