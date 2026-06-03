<?php

namespace Modules\Customer\app\Http\Controllers;

use App\Facades\MailSender;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\MailSenderService;
use App\Traits\GetGlobalInformationTrait;
use App\Traits\GlobalMailTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Blog\app\Models\BlogComment;
use Modules\Coupon\app\Models\CouponHistory;
use Modules\Customer\app\Models\BannedHistory;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderBillingAddress;
use Modules\Order\app\Models\OrderDetails;
use Modules\Order\app\Models\OrderShippingAddress;
use Modules\Product\app\Models\ProductReview;

class CustomerController extends Controller
{
    use GetGlobalInformationTrait, GlobalMailTrait;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('customer.view');

        $query = User::query();

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('seller'), function ($q) use ($request) {
            if ($request->seller == 1) {
                $q->whereHas('seller');
            } elseif ($request->seller == 0) {
                $q->whereDoesntHave('seller');
            }
        });

        $query->when($request->filled('verified'), function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                if ($request->verified == 1) {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->verified == 0) {
                    $query->whereNull('email_verified_at');
                }
            });
        });

        $query->when($request->filled('banned'), function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                if ($request->banned == 1) {
                    $query->where('is_banned', 'yes');
                } elseif ($request->banned == 0) {
                    $query->where('is_banned', 'no');
                }
            });
        });
        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        return view('customer::all_customer')->with([
            'users' => $users,
        ]);
    }

    /**
     * @param Request $request
     */
    public function active_customer(Request $request)
    {
        checkAdminHasPermissionAndThrowException('customer.view');

        $query = User::query();
        $query->where(['status' => 'active', 'is_banned' => 'no'])->where('email_verified_at', '!=', null);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%');
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        return view('customer::active_customer')->with([
            'users' => $users,
        ]);
    }

    /**
     * @param Request $request
     */
    public function non_verified_customers(Request $request)
    {
        checkAdminHasPermissionAndThrowException('customer.view');

        $query = User::query();
        $query->where('email_verified_at', null);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%');
        });
        $query->when($request->filled('banned'), function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                if ($request->banned == 1) {
                    $query->where('is_banned', 'yes');
                } elseif ($request->banned == 0) {
                    $query->where('is_banned', 'no');
                }
            });
        });
        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        return view('customer::non_verified_customer')->with([
            'users' => $users,
        ]);
    }

    /**
     * @param Request $request
     */
    public function banned_customers(Request $request)
    {
        checkAdminHasPermissionAndThrowException('customer.view');

        $query = User::query();
        $query->where('is_banned', 'yes');

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('verified'), function ($q) use ($request) {
            $q->where(function ($query) use ($request) {
                if ($request->verified == 1) {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->verified == 0) {
                    $query->whereNull('email_verified_at');
                }
            });
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        return view('customer::banned_customer')->with([
            'users' => $users,
        ]);
    }

    /**
     * @param $id
     */
    public function show($id)
    {
        checkAdminHasPermissionAndThrowException('customer.view');

        $user = User::findOrFail($id);

        $banned_histories = BannedHistory::where('user_id', $id)->orderBy('id', 'desc')->get();

        $countries = Country::orderBy('name', 'asc')->where('status', 1)->get();
        $states    = [];
        $cities    = [];

        if ($user->country_id) {
            $states = State::where('country_id', $user->country_id)->get();
        }
        if ($user->state_id) {
            $cities = City::where('state_id', $user->state_id)->get();
        }

        return view('customer::customer_show')->with([
            'user'             => $user,
            'banned_histories' => $banned_histories,
            'countries'        => $countries,
            'states'           => $states,
            'cities'           => $cities,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('customer.update');

        $user = User::with(['seller'])->findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:50',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:20',
            'bio'        => 'nullable|string|max:2000',
            'birthday'   => 'nullable|date',
            'gender'     => 'nullable|in:male,female,other',
            'image'      => 'nullable|image|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:512',
            'zip_code'   => 'nullable',
            'address'    => 'nullable',
            'country_id' => 'nullable|exists:countries,id',
            'state_id'   => 'nullable|exists:states,id',
            'city_id'    => 'nullable|exists:cities,id',
            'status'     => 'required|in:0,1',
        ], [
            'name.required'     => __('Name is required'),
            'name.string'       => __('Name must be a string'),
            'name.max'          => __('Name must be less than 50 characters'),
            'email.required'    => __('Email is required'),
            'email.email'       => __('Email is not valid'),
            'email.unique'      => __('Email is already in use'),
            'phone.string'      => __('Phone must be a number'),
            'phone.max'         => __('Phone must be less than 20 characters'),
            'bio.string'        => __('Bio must be a string'),
            'bio.max'           => __('Bio must be less than 2000 characters'),
            'birthday.date'     => __('Birthday must be a valid date'),
            'gender.in'         => __('Gender must be male, female, or other'),
            'address.required'  => __('Address is required'),
            'country_id.exists' => __('Country not found'),
            'state_id.exists'   => __('State not found'),
            'city_id.exists'    => __('City not found'),
            'image.image'       => __('Image must be an image'),
            'image.mimetypes'   => __('Image must be a jpeg, png, gif, or webp'),
            'image.max'         => __('Image must be less than 512kb'),
            'status.required'   => __('Status is required'),
            'status.id'         => __('Status must be either Active or Inactive'),
        ]);

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
        $user->status     = $request->status == 1 ? 'active' : 'inactive';

        if ($request->hasFile('image')) {
            $user->image = file_upload($request->image);
        }

        $user->save();

        if (!$user?->addresses?->first() && $user->country_id && $user->state_id && $user->city_id && $user->zip_code && $user->address) {
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

        $notification = __('Updated successfully');

        if (strtolower($user->email) !== strtolower($request->email) || $user->status == 'inactive') {
            try {
                DB::beginTransaction();

                $oldEmail = $user->email;

                $user->email              = $request->email;
                $user->email_verified_at  = null;
                $user->verification_token = str()->random(100);
                $user->save();

                MailSender::sendVerifyMailSingleUser($user);

                $notification .= " & " . __('Verification email sent successfully for the new email');

                DB::commit();

                if (strtolower($oldEmail) !== strtolower($request->email)) {
                    [$subject, $message] = MailSender::fetchEmailTemplate('email_changed', [
                        'user_name' => $user?->name ?? 'Name Missing!',
                        'email'     => $user?->email ?? 'Email Missing!',
                    ]);

                    $message .= '<br>' . __('This email is changed by the admin.');

                    $link = [
                        __('HOME') => route('website.home'),
                    ];

                    MailSender::sendMail($oldEmail, $subject, $message, $link);
                }
            } catch (Exception $e) {
                DB::rollBack();
                $notification .= ' & ' . __('Verification email could not be sent, email not updated');
                logError('User verification email could not be sent', $e);
            }
        }

        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function password_change(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('customer.update');

        $rules = [
            'password' => 'required|min:4|confirmed',
        ];
        $customMessages = [
            'password.required'  => __('Password is required'),
            'password.min'       => __('Password minimum 4 character'),
            'password.confirmed' => __('Confirm password does not match'),
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::findOrFail($id);

        $user->password = Hash::make($request->password);
        $user->save();

        try {
            [$subject, $message] = MailSender::fetchEmailTemplate('password_changed', [
                'user_name' => $user?->name ?? 'Name Missing!',
            ]);

            $message .= '<br>' . __('And the change made by the admin.');

            $link = [
                __('HOME') => route('website.home'),
            ];

            MailSender::sendMail($user->email, $subject, $message, $link);
        } catch (Exception $e) {
            logError('User password changed email could not be sent', $e);
        }

        $notification = __('Password change successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function send_banned_request(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('customer.update');

        $rules = [
            'subject'     => 'required|max:255',
            'description' => 'required',
        ];
        $customMessages = [
            'subject.required'     => __('Subject is required'),
            'description.required' => __('Description is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $user = User::findOrFail($id);
        if ($user->is_banned == 'yes') {
            $user->is_banned = 'no';
            $user->save();

            $banned              = new BannedHistory;
            $banned->user_id     = $id;
            $banned->subject     = $request->subject;
            $banned->reasone     = 'for_unbanned';
            $banned->description = $request->description;
            $banned->save();
        } else {
            $user->is_banned = 'yes';
            $user->save();

            $banned              = new BannedHistory;
            $banned->user_id     = $id;
            $banned->subject     = $request->subject;
            $banned->reasone     = 'for_banned';
            $banned->description = $request->description;
            $banned->save();
        }

        //Mail send
        $this->sendMail($user->email, $request->subject, $request->description);

        $notification = __('Banned request successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    /**
     * @param Request $request
     * @param $id
     */
    public function send_verify_request(Request $request, $id)
    {

        $user                     = User::findOrFail($id);
        $user->verification_token = Str::random(100);
        $user->save();

        (new MailSenderService)->sendVerifyMailSingleUser($user);

        $notification = __('A verification link has been send to user mail');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    /**
     * @param Request $request
     */
    public function send_verify_request_to_all(Request $request)
    {

        (new MailSenderService)->sendVerifyMailToAllUser();

        $notification = __('A verification link has been send to user mail');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    /**
     * @param Request $request
     * @param $id
     */
    public function send_mail_to_customer(Request $request, $id)
    {
        $rules = [
            'subject'     => 'required|max:255',
            'description' => 'required',
        ];
        $customMessages = [
            'subject.required'     => __('Subject is required'),
            'description.required' => __('Description is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $user = User::select('email')->findOrFail($id);

        //send mail
        $this->sendMail($user->email, $request->subject, $request->description);

        $notification = __('Mail sent to customer successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function send_bulk_mail()
    {
        checkAdminHasPermissionAndThrowException('customer.bulk.mail');

        return view('customer::send_bulk_mail');
    }

    /**
     * @param Request $request
     */
    public function send_bulk_mail_to_all(Request $request)
    {
        checkAdminHasPermissionAndThrowException('customer.bulk.mail');

        $rules = [
            'subject'     => 'required|max:255',
            'description' => 'required',
        ];

        $customMessages = [
            'subject.required'     => __('Subject is required'),
            'description.required' => __('Description is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $userCount = User::select('id')->where(['status' => 'active', 'is_banned' => 'no'])->where('email_verified_at', '!=', null)->count();

        if ($userCount > 0) {
            $email_list = User::select('email')->where(['status' => 'active', 'is_banned' => 'no'])->where('email_verified_at', '!=', null)->orderBy('id', 'desc')->get();

            (new MailSenderService)->SendBulkEmail($email_list, $request->subject, $request->description);

            $notification = __('Mail sent to customer successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];
        } else {
            $notification = __('Mail can not be sent because no active user was found.');
            $notification = ['message' => $notification, 'alert-type' => 'error'];
        }

        return redirect()->back()->with($notification);

    }

    /**
     * @param $id
     */
    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('customer.delete');

        $user = User::findOrFail($id);

        $userName = $user->name ?? 'Name Missing!';

        $image = $user->image;

        $sellerMessage = '';

        try {
            DB::beginTransaction();

            $seller = $user->seller;

            if ($seller) {
                $shopName    = $user->seller->shop_name ?? 'Shop Name Missing!';
                $hasOrders   = $seller->orders()->exists();
                $hasProducts = $seller->products()->exists();
                $sellerId    = $seller->id;

                if (!$hasOrders && !$hasProducts) {
                    $user->seller->kyc()->delete();
                    $user->seller->returnPolicies()->delete();
                    $user->seller->withdrawRequests()->delete();
                    $user->seller->walletRequests()->delete();
                    $user->seller->products()->delete();
                    $user->seller->delete();
                }

                [$subject, $message] = MailSender::fetchEmailTemplate('seller_deleted', [
                    'shop_name' => $shopName ?? 'Name Missing!',
                ]);

                $link = [
                    __('HOME') => route('website.home'),
                ];

                MailSender::sendMail($user->email, $subject, $message, $link);

                $sellerMessage = "Seller with ID: $sellerId and Shop Name: $shopName has been deleted successfully.";
            }

            $user->wishlist()?->delete();
            $user->addresses()?->delete();
            $user->cart()?->delete();
            $user->socialite()?->delete();

            BannedHistory::where('user_id', $user->id)?->delete();

            ProductReview::where('user_id', $user->id)?->delete();

            BlogComment::where('user_id', $user->id)->update(['user_id' => 0]);

            CouponHistory::where('user_id', $user->id)->update(['user_id' => 0]);

            Order::where('user_id', $user->id)->update(['user_id' => 0]);

            OrderShippingAddress::where('user_id', $user->id)->update(['user_id' => 0]);

            OrderBillingAddress::where('user_id', $user->id)->update(['user_id' => 0]);

            OrderDetails::where('user_id', $user->id)->update(['user_id' => 0]);

            $user->delete();

            DB::commit();

            $message = "Customer with ID: $id and Name: $userName has been deleted successfully." . $sellerMessage;

            notifyAdmin("Customer Deleted!", $message);

        } catch (Exception $e) {
            logError("Unable to delete customer with ID: $id", $e);
            DB::rollBack();
            return redirect()->back()->with(['message' => __('Something went wrong, please try again'), 'alert-type' => 'error']);
        }

        if ($image) {
            try {
                if (File::exists(public_path($image))) {
                    unlink(public_path($image));
                }
            } catch (Exception $e) {
                logError("Unable to delete customer image with ID: $id", $e);
            }
        }

        return to_route('admin.all-customers')->with(['message' => __('Customer deleted successfully with all related data!'), 'alert-type' => 'success']);
    }
}
