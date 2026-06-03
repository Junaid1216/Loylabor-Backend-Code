<?php

namespace App\Http\Controllers\Seller;

use App\Facades\MailSender;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserProfileUpdateRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Order\app\Models\OrderDetails;
use Modules\PaymentWithdraw\app\Models\WithdrawRequest;

class SellerProfileController extends Controller
{
    /**
     * @return mixed
     */
    public function index()
    {
        $user      = Auth::guard('web')->user();
        $seller    = $user->seller;
        $countries = Country::orderBy('name', 'asc')->where('status', 1)->get();
        $states    = [];
        $cities    = [];

        if ($user->country_id) {
            $states = State::where('country_id', $user->country_id)->get();
        }
        if ($user->state_id) {
            $cities = City::where('state_id', $user->state_id)->get();
        }

        $totalWithdraw = WithdrawRequest::where('vendor_id', $seller->id)->where('status', 'approved')->sum('total_amount') ?? 0;

        $totalPendingWithdraw = WithdrawRequest::where('vendor_id', $seller->id)->where('status', 'pending')->sum('total_amount') ?? 0;

        $totalSoldProduct = OrderDetails::whereHas('order', function ($query) use ($seller) {
            $query->where([
                'vendor_id'    => $seller->id,
                'order_status' => OrderStatus::DELIVERED->value,
            ])->whereRelation('paymentDetails', 'payment_status', PaymentStatus::COMPLETED->value);
        })
            ->sum('qty') ?? 0;

        return view('vendor::seller_profile', compact('user', 'countries', 'states', 'cities', 'seller', 'totalWithdraw', 'totalPendingWithdraw', 'totalSoldProduct'));
    }

    public function changePassword()
    {
        $user = Auth::guard('web')->user();

        return view('vendor::change_password', compact('user'));
    }

    /**
     * @param Request $request
     */
    public function updateSellerProfile(UserProfileUpdateRequest $request)
    {
        $user             = auth()->user();
        $user->name       = $request->name;
        $user->phone      = $request->phone;
        $user->bio        = $request->bio;
        $user->country_id = $request->country_id;
        $user->state_id   = $request->state_id;
        $user->city_id    = $request->city_id;
        $user->zip_code   = $request->zip_code;
        $user->address    = $request->address;
        $user->birthday   = $request->birthday;
        $user->gender     = $request->gender;

        if ($request->hasFile('image')) {
            $user->image = file_upload($request->image);
        }

        $user->save();

        if (!$user->addresses->first()) {
            $address             = new Address();
            $address->user_id    = $user->id;
            $address->name       = $user->name;
            $address->email      = $user->email;
            $address->phone      = $user->phone;
            $address->country_id = $user->country_id;
            $address->state_id   = $user->state_id;
            $address->city_id    = $user->city_id;
            $address->zip_code   = $user->zip_code;
            $address->address    = $user->address;
            $address->type       = 'home';
            $address->default    = 1;
            $address->status     = 1;
            $address->save();
        }

        $message = __('Updated successfully');

        if (strtolower($user->email) !== strtolower($request->email)) {
            $emailSend = false;

            try {
                DB::beginTransaction();
                $oldEmail = $user->email;

                $user->email              = $request->email;
                $user->email_verified_at  = null;
                $user->verification_token = str()->random(100);
                $user->save();

                MailSender::sendVerifyMailSingleUser($user);

                $message .= " & " . __('Verification email sent successfully for the new email');
                $emailSend = true;

                DB::commit();

                // send email changed notification
                [$subject, $message] = MailSender::fetchEmailTemplate('email_changed', [
                    'user_name' => $user?->name ?? 'Name Missing!',
                    'email'     => $user?->email ?? 'Email Missing!',
                ]);

                $link = [
                    __('HOME') => route('website.home'),
                ];

                MailSender::sendMail($oldEmail, $subject, $message, $link);
            } catch (Exception $e) {
                DB::rollBack();
                $message .= ' & ' . __('Verification email could not be sent, email not updated');
                logError('User verification email could not be sent', $e);
            }

            if ($emailSend) {
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return to_route('login')->with([
                    'alert-type' => 'success',
                    'message'    => $message,
                ]);
            }
        }

        $notification = ['message' => $message, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param Request $request
     */
    public function updatePassword(Request $request)
    {
        $user  = Auth::guard('web')->user();
        $rules = [
            'current_password' => 'required|current_password',
            'password'         => 'required|min:4|confirmed',
        ];

        $customMessages = [
            'password.required'  => __('Password is required'),
            'password.min'       => __('Password must be 4 characters'),
            'password.confirmed' => __('Confirm password does not match'),
        ];
        $this->validate($request, $rules, $customMessages);

        $user->password = Hash::make($request->password);

        $user->save();

        Auth::logoutOtherDevices($request->password);

        $notification = __('Password Change Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function myShop()
    {
        $user = Auth::guard('web')->user();

        $seller = $user->seller;

        return view('vendor::shop_profile', compact('user', 'seller'));
    }

    /**
     * @param Request $request
     */
    public function updateSellerSop(Request $request)
    {
        $user = Auth::guard('web')->user();

        $seller = Vendor::where('user_id', $user->id)->first();

        $rules = [
            'shop_name'       => 'required|unique:vendors,shop_name,' . $seller->id,
            'email'           => 'required|unique:vendors,email,' . $seller->id,
            'phone'           => 'required',
            'opens_at'        => 'required',
            'closed_at'       => 'required',
            'address'         => 'required',
            'banner_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'logo_image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:512',
            'seo_title'       => 'nullable|max:255',
            'seo_description' => 'nullable|max:255',
        ];

        $customMessages = [
            'shop_name.required'    => __('Shop name is required'),
            'shop_name.unique'      => __('Shop name already exist'),
            'email.required'        => __('Email is required'),
            'email.unique'          => __('Email already exist'),
            'phone.required'        => __('Phone is required'),
            'description.required'  => __('Description is required'),
            'greeting_msg.required' => __('Greeting Messsage is required'),
            'opens_at.required'     => __('Opens at is required'),
            'closed_at.required'    => __('Close at is required'),
            'address.required'      => __('Address is required'),
        ];

        $request->validate($rules, $customMessages);

        $seller->shop_name       = $request->shop_name;
        $seller->shop_slug       = $this->generateShopSlug($request->shop_name);
        $seller->phone           = $request->phone;
        $seller->open_at         = $request->opens_at;
        $seller->closed_at       = $request->closed_at;
        $seller->address         = $request->address;
        $seller->seo_title       = $request->filled('seo_title') ? $request->seo_title : $request->shop_name;
        $seller->seo_description = $request->filled('seo_description') ? $request->seo_description : $request->shop_name;
        $seller->email           = $request->email;

        if ($request->hasFile('banner_image')) {
            $seller->banner_image = file_upload($request->banner_image, oldFile: $seller->banner_image);
        }

        if ($request->hasFile('logo_image')) {
            $seller->logo_image = file_upload($request->logo_image, oldFile: $seller->logo_image);
        }

        $seller->save();

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param string $slug
     */
    public function generateShopSlug(string $slug)
    {
        $baseSlug = str($slug)->slug()->__toString();

        $slugToCheck = $baseSlug;

        $counter = 1;

        while (
            Vendor::where('shop_slug', $slugToCheck)
            ->where('id', '!=', vendorId())
            ->exists()
        ) {
            $slugToCheck = $baseSlug . '-' . $counter++;
        }

        return $slugToCheck;

    }
}
