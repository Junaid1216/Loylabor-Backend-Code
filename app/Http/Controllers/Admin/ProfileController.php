<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use RedirectHelperTrait;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function edit_profile()
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.profile.edit_profile', compact('admin'));
    }

    /**
     * @return mixed
     */
    public function profile_update(Request $request)
    {

        $admin = Auth::guard('admin')->user();
        $rules = [
            'name' => 'required',
            'email' => 'required|unique:admins,email,'.$admin->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ];
        
        $customMessages = [
            'name.required' => __('Name is required'),
            'email.required' => __('Email is required'),
            'email.unique' => __('Email already exist'),
            'image.image' => __('The file must be an image'),
            'image.mimes' => __('Image must be a file of type: jpeg, png, jpg, gif, svg'),
            'image.max' => __('Image size must be less than 2MB'),
        ];
        
        $this->validate($request, $rules, $customMessages);

        $admin = Auth::guard('admin')->user();

        if ($request->file('image')) {
            $file_name = file_upload(file: $request->image, path: 'uploads/custom-images/', oldFile: $admin->image);
            $admin->image = $file_name;
            $admin->save();
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->save();

        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * @return mixed
     */
    public function update_password(Request $request)
    {

        $admin = Auth::guard('admin')->user();
        $rules = [
            'current_password' => 'required',
            'password' => 'required|confirmed|min:4',
        ];
        $customMessages = [
            'current_password.required' => __('Current password is required'),
            'password.required' => __('Password is required'),
            'password.confirmed' => __('Confirm password does not match'),
            'password.min' => __('Password must be at leat 4 characters'),
        ];
        $this->validate($request, $rules, $customMessages);

        if (Hash::check($request->current_password, $admin->password)) {
            $admin->password = Hash::make($request->password);
            $admin->save();

            $notification = __('Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return $this->redirectWithMessage(RedirectType::UPDATE->value, '', [], $notification);

        } else {
            $notification = __('Current password does not match');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }
}
