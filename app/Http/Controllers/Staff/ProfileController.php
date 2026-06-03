<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use RedirectHelperTrait;

    public function __construct()
    {
        $this->middleware('auth:staff');
    }

    public function edit_profile()
    {
        $staffUser = Auth::guard('staff')->user();

        return view('staff.profile.edit_profile', compact('staffUser'));
    }

    /**
     * @return mixed
     */
    public function profile_update(Request $request)
    {
        $staffUser = Auth::guard('staff')->user();

        $rules = [
            'name' => 'required',
            // Fix: Change 'staffs' to 'staff' - table name is 'staff', not 'staffs'
            'email' => ['required', Rule::unique('staff', 'email')->ignore($staffUser->id)],
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

        if ($request->file('image')) {
            $file_name = file_upload(file: $request->image, path: 'uploads/custom-images/', oldFile: $staffUser->image);
            $staffUser->image = $file_name;
            $staffUser->save();
        }

        $staffUser->name = $request->name;
        $staffUser->email = $request->email;
        $staffUser->save();

        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * @return mixed
     */
    public function update_password(Request $request)
    {
        $staffUser = Auth::guard('staff')->user();
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

        if (Hash::check($request->current_password, $staffUser->password)) {
            $staffUser->password = Hash::make($request->password);
            $staffUser->save();

            $notification = __('Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);

        } else {
            $notification = __('Current password does not match');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }
}