<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleFormRequest;
use App\Models\Admin;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    use RedirectHelperTrait;

    public function index()
    {
        checkAdminHasPermissionAndThrowException('role.view');
        $roles = Role::where('name', '!=', 'Super Admin')->paginate(15);
        $admins_exists = Admin::notSuperAdmin()->whereStatus('active')->count();

        return view('admin.roles.index', compact('roles', 'admins_exists'));
    }

    public function create()
    {
        // checkAdminHasPermissionAndThrowException('role.create');
        return view('admin.roles.create');
    }

    public function store(RoleFormRequest $request)
    {
		 $request->validate([
        'name' => 'required|string|unique:roles,name',
    ], [
        'name.required' => 'Role name is required.',
        'name.unique'   => 'This role name is already exist.',
    ]);
        // checkAdminHasPermissionAndThrowException('role.store');
        $role = Role::create(['name' => $request->name]);

        return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.role.index');
    }

    public function assignPermissionsForm()
    {
        // Sirf wohi access kar sakte hain jinko 'role.assign' permission hai
        checkAdminHasPermissionAndThrowException('role.assign');
        
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        
        $permission_groups = Admin::getPermissionGroupsWithPermissions();
        
        return view('admin.roles.assign-role', compact('roles', 'permission_groups'));
    }

    /**
     * Get role permissions via AJAX (ALAG FUNCTION)
     */
    public function getRolePermissions($roleId)
    {
        // Sirf wohi access kar sakte hain jinko 'role.assign' permission hai
        checkAdminHasPermissionAndThrowException('role.assign');
        
        try {
            $role = Role::findOrFail($roleId);
            
            // Super Admin check
            if ($role->name === 'Super Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify Super Admin permissions'
                ], 403);
            }
            
            $permissions = $role->permissions->pluck('name');
            
            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role permissions (ALAG FUNCTION - Sirf permissions handle karega)
     */
    public function updateRolePermissions(Request $request)
    {
          // Sirf wohi update kar sakte hain jinko 'role.assign' permission hai
        checkAdminHasPermissionAndThrowException('role.assign');
        
        // Validation - Fix to check admin guard permissions
        $request->validate([
            'user_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name,guard_name,admin'
        ]);
        
        try {
            DB::beginTransaction();
            
            $role = Role::findOrFail($request->user_id);
            
            // Super Admin ko modify nahi kar sakte
            if ($role->name === 'Super Admin') {
                return redirect()->back()->with('error', 'Super Admin permissions cannot be modified');
            }
            
            // Debug: Log incoming permissions
            \Log::info('Permissions to save:', $request->permissions ?? []);
            
            // Permissions sync karo using Spatie's proper method
            if (!empty($request->permissions)) {
                // Filter out empty values and ensure all permissions exist
                $validPermissions = array_filter($request->permissions);
                \Log::info('Valid permissions after filter:', $validPermissions);
                
                $existingPermissions = Permission::whereIn('name', $validPermissions)
                    ->where('guard_name', 'admin')
                    ->pluck('name')
                    ->toArray();
                
                \Log::info('Existing permissions from DB:', $existingPermissions);
                
                $role->syncPermissions($existingPermissions);
                \Log::info('Permissions synced for role: ' . $role->name);
            } else {
                // Agar koi permission select nahi ki toh saari hata do
                \Log::info('Removing all permissions from role: ' . $role->name);
                $role->syncPermissions([]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Updated successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        checkAdminHasPermissionAndThrowException('role.edit');
        $role = Role::where('name', '!=', 'Super Admin')->where('id', $id)->first();
        abort_if(! $role, 403);
        $permissions = Permission::all();
        $permission_groups = Admin::getPermissionGroupsWithPermissions();

        return view('admin.roles.edit', compact('permissions', 'permission_groups', 'role'));
    }

   public function update(RoleFormRequest $request, $id)
    { 
		$role = Role::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255|unique:roles,name,' . $id,
    ], [
        'name.required' => 'Role name is required.',
        'name.unique'   => 'This role name already exists.',
    ]);

      
        $role->name = $request->name;
        $role->save();

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.role.index');
    }


    public function show($id)
    {
        checkAdminHasPermissionAndThrowException('role.view');
        $role = Role::where('name', '!=', 'Super Admin')->where('id', $id)->first();
        abort_if(! $role, 403);
        
        return view('admin.roles.show', compact('role'));
    }

    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('role.delete');
        $role = Role::where('name', '!=', 'Super Admin')->where('id', $id)->first();
        abort_if(! $role, 403);
        if (! is_null($role)) {
            $role->delete();
        }

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.role.index');
    }

    public function assignRoleView()
    {
        // checkAdminHasPermissionAndThrowException('role.assign');
        $roles = Role::where('name', '!=', 'Super Admin')->get();

        return view('admin.roles.assign-role', compact('roles'));
    }

    public function getAdminRoles($id)
    {
        $role = Role::find($id);
        if ($role) {
            $permissions = $role->permissions->pluck('name')->toArray();
            
            return response()->json([
                'success' => true,
                'permissions' => $permissions,
            ]);
        }

        return response()->json([
            'success' => false,
            'data' => $options,
        ]);
    }

    public function assignRoleUpdate(Request $request)
    {
        // checkAdminHasPermissionAndThrowException('role.assign');

        $messages = [
            'user_id.required' => __('You must select a role'),
            'user_id.exists' => __('Role not found'),
            'permissions.array' => __('You must select permissions'),
            'permissions.*.required' => __('You must select permissions'),
            'permissions.*.string' => __('You must select permissions'),
        ];

        $request->validate([
            'user_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|string',
        ], $messages);

        $role = Role::findOrFail($request->user_id);
        
        // Sync permissions to the role
        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissions = array_filter($request->permissions);
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.role.index');
    }
}
