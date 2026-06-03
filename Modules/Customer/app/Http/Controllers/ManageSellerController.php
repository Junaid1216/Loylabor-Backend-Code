<?php

namespace Modules\Customer\app\Http\Controllers;

use App\Facades\MailSender;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerProfileController;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderDetails;
use Modules\PaymentWithdraw\app\Models\WithdrawRequest;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\ProductReview;
use Modules\Wallet\app\Models\WalletHistory;

class ManageSellerController extends Controller
{
    /**
     * @param Request $request
     */
    public function allSellers(Request $request)
    {
        checkAdminHasPermissionAndThrowException('sellers.view');

        $query = User::query();

        $query->with([
            'seller' => function ($q) {
                $q->withCount('products');

                $q->withCount([
                    'orders as orders_count' => function ($query) {
                        $query->where('order_status', OrderStatus::DELIVERED->value);
                    },
                ]);

                $q->withSum([
                    'walletRequests as wallet_requests_sum' => function ($query) {
                        $query->where([
                            'transaction_type' => 'credit',
                            'payment_status'   => PaymentStatus::COMPLETED->value,
                        ]);
                    },
                ], 'amount');
            },
        ]);

        $query->whereHas('seller');

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%')
                ->orWhereHas('seller', function ($query) use ($request) {
                    $query->where('shop_name', 'like', '%' . $request->keyword . '%')->orWhere('email', 'like', '%' . $request->keyword . '%');
                });
        });

        $query->when($request->filled('verified'), function ($q) use ($request) {
            match ((int) $request->verified) {
                0, 1 => $q->whereHas('seller', fn($query) => $query->where('is_verified', $request->verified)),
                2       => $q->whereNotNull('email_verified_at'),
                3       => $q->whereNull('email_verified_at'),
                default => $q->whereHas('seller', fn($query) => $query->where('is_verified', 1)),
            };
        });

        $query->when($request->filled('banned'), function ($q) use ($request) {
            $q->where('is_banned', $request->banned == 1 ? 'yes' : 'no');
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        $title = $request->filled('order_by') && $request->order_by == 1 ? __('All Sellers (A-Z)') : __('All Sellers (Z-A)');

        return view('customer::manage-sellers.index', [
            'users' => $users,
            'title' => $title,
        ]);
    }

    /**
     * @param Request $request
     */
    public function pendingSellers(Request $request)
    {
        checkAdminHasPermissionAndThrowException('sellers.view');

        $query = User::query();

        $query->with([
            'seller' => function ($q) {
                $q->withCount('products');

                $q->withCount([
                    'orders as orders_count' => function ($query) {
                        $query->where('order_status', OrderStatus::DELIVERED->value);
                    },
                ]);

                $q->withSum([
                    'walletRequests as wallet_requests_sum' => function ($query) {
                        $query->where([
                            'transaction_type' => 'credit',
                            'payment_status'   => PaymentStatus::COMPLETED->value,
                        ]);
                    },
                ], 'amount');
            },
        ]);

        $query->whereRelation('seller', 'status', 'pending');

        $query->whereHas('seller');

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%')
                ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                ->orWhere('address', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('verified'), function ($q) use ($request) {
            match ((int) $request->verified) {
                0, 1 => $q->whereHas('seller', fn($query) => $query->where('is_verified', $request->verified)),
                2       => $q->whereNotNull('email_verified_at'),
                3       => $q->whereNull('email_verified_at'),
                default => $q->whereHas('seller', fn($query) => $query->where('is_verified', 1)),
            };
        });

        $query->when($request->filled('banned'), function ($q) use ($request) {
            $q->where('is_banned', $request->banned == 1 ? 'yes' : 'no');
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $users = $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $users = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        $title = $request->filled('order_by') && $request->order_by == 1 ? __('Pending Sellers (A-Z)') : __('Pending Sellers (Z-A)');

        return view('customer::manage-sellers.index', [
            'users' => $users,
            'title' => $title,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function profile(Request $request)
    {
        checkAdminHasPermissionAndThrowException('sellers.view');

        $user = User::with(['seller'])->findOrFail($request->id);

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

        $totalEarning = WalletHistory::where([
            'vendor_id'        => $seller->id,
            'transaction_type' => 'credit',
            'payment_status'   => PaymentStatus::COMPLETED->value,
        ])->sum('amount') ?? 0;

        $totalSoldProduct = OrderDetails::whereHas('order', function ($query) use ($seller) {
            $query->where([
                'vendor_id'    => $seller->id,
                'order_status' => OrderStatus::DELIVERED->value,
            ])->whereHas('paymentDetails', function ($query) {
                $query->where('payment_status', PaymentStatus::COMPLETED->value);
            });
        })
            ->sum('qty') ?? 0;

        return view('customer::manage-sellers.profile', compact('user', 'countries', 'states', 'cities', 'seller', 'totalWithdraw', 'totalEarning', 'totalSoldProduct'));
    }

    /**
     * @param Request $request
     */
    public function storeShopProfile(Request $request, $id)
    {
        $seller = Vendor::findOrFail($id);

        $rules = [
            'shop_name'       => 'required|unique:vendors,shop_name,' . $seller->id,
            'email'           => 'required|unique:vendors,email,' . $seller->id,
            'phone'           => 'nullable',
            'opens_at'        => 'nullable',
            'closed_at'       => 'nullable',
            'address'         => 'nullable',
            'banner_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'logo_image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:512',
            'seo_title'       => 'nullable|max:255',
            'seo_description' => 'nullable|max:255',
            'status'          => 'required|in:0,1',
            'is_featured'     => 'required|in:0,1',
            'top_rated'       => 'required|in:0,1',
            'is_verified'     => 'required|in:0,1',
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
            'banner_image.image'    => __('Banner image must be an image file'),
            'banner_image.mimes'    => __('Banner image must be a file of type: jpg, jpeg, png, webp'),
            'banner_image.max'      => __('Banner image must not be greater than 2MB'),
            'logo_image.image'      => __('Logo image must be an image file'),
            'logo_image.mimes'      => __('Logo image must be a file of type: jpg, jpeg, png, webp'),
            'logo_image.max'        => __('Logo image must not be greater than 512KB'),
            'seo_title.max'         => __('SEO title must not be greater than 255 characters'),
            'seo_description.max'   => __('SEO description must not be greater than 255 characters'),
            'status.in'             => __('Status must be either Active or Inactive'),
            'is_featured.in'        => __('Is Featured must be either Yes or No'),
            'top_rated.in'          => __('Top Rated must be either Yes or No'),
            'is_verified.in'        => __('KYC Verified must be either Yes or No'),
        ];

        $request->validate($rules, $customMessages);

        $seller->shop_name       = $request->shop_name;
        $seller->shop_slug       = (new SellerProfileController)->generateShopSlug($request->shop_name);
        $seller->phone           = $request->phone;
        $seller->open_at         = $request->opens_at;
        $seller->closed_at       = $request->closed_at;
        $seller->address         = $request->address;
        $seller->seo_title       = $request->filled('seo_title') ? $request->seo_title : $request->shop_name;
        $seller->seo_description = $request->filled('seo_description') ? $request->seo_description : $request->shop_name;
        $seller->status          = $request->filled('status') ? $request->status : 0;
        $seller->is_featured     = $request->filled('is_featured') ? $request->is_featured : 0;
        $seller->top_rated       = $request->filled('top_rated') ? $request->top_rated : 0;
        $seller->is_verified     = $request->filled('is_verified') ? $request->is_verified : 0;
        $seller->email           = $request->email;

        if ($request->hasFile('banner_image')) {
            $seller->banner_image = file_upload($request->banner_image, oldFile: $seller->banner_image);
        }

        if ($request->hasFile('logo_image')) {
            $seller->logo_image = file_upload($request->logo_image, oldFile: $seller->logo_image);
        }

        $seller->save();

        $notification = __('Shop Profile Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function storeProfile(Request $request, $id)
    {
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
     * @param $id
     */
    public function sendVerifyLink($id)
    {
        checkAdminHasPermissionAndThrowException('sellers.update');

        $user   = User::with(['seller'])->findOrFail($id);
        $vendor = $user->seller;

        try {
            DB::beginTransaction();

            if ($vendor) {
                $vendor->status             = 0;
                $vendor->verification_token = str()->random(40);
                $vendor->save();
                [$subject, $message] = MailSender::fetchEmailTemplate('shop_verification', ['shop_name' => $vendor->shop_name]);

                $message .= '<br>' . __('This verify link is generated by the admin.');

                $link = [__('COMPLETE VERIFICATION') => route('website.verify.shop', ['token' => $vendor->verification_token])];

                if ($vendor->verification_token) {
                    MailSender::sendMail($vendor->email, $subject, $message, $link);
                }
                DB::commit();
                $notification = __('Verification email has been sent to the vendor\'s email address');
            }
        } catch (Exception $e) {
            DB::rollBack();
            logError('Verification email could not be sent', $e);

            $date    = formattedTime(now());
            $message = "Unable to send vendor {$vendor->shop_name}'s verification email to {$vendor->email} at {$date}. The error is {$e->getMessage()}";

            $route = route('admin.manage-seller.profile', $vendor->user_id);

            notifyAdmin('Verification email could not be sent', $message, 'danger', $route);
            $notification = __('Verification email could not be sent, please try again later.');
            $type         = 'danger';
        }

        return redirect()->back()->with([
            'message'    => $notification,
            'alert-type' => isset($type) ? $type : 'success',
        ]);
    }

    /**
     * @param $id
     */
    public function deleteSeller($id)
    {
        checkAdminHasPermissionAndThrowException('sellers.delete');

        try {
            // Begin a transaction
            DB::beginTransaction();

            $user = User::with(['seller.products.orders'])->findOrFail($id);

            if ($user->seller) {
                $shopName  = $user->seller->shop_name ?? 'Shop Name Missing!';
                $hasOrders = $user->seller->products->contains(function ($product) {
                    return $product->orders->count() > 0;
                });

                if ($hasOrders) {
                    throw new Exception(__('This seller has orders, cannot delete the profile.'));
                }

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

            DB::commit();

            $notification = ['message' => __('Deleted successfully'), 'alert-type' => 'success'];
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            logError('User not found', $e);
            $notification = ['message' => 'User not found.', 'alert-type' => 'error'];
        } catch (Exception $e) {
            DB::rollBack();

            logError('User could not be deleted', $e);

            $notification = ['message' => __('An error occurred while deleting the user.'), 'alert-type' => 'error'];
        }

        return redirect()->back()->with($notification);
    }

    /**
     * @param $id
     */
    public function shopDashboard($id)
    {
        $user = User::with(['seller'])->findOrFail($id);

        $seller = $user->seller;

        $todayOrders = Order::with('user')->where('vendor_id', vendorId())->orderBy('id', 'desc')->whereDay('created_at', now()->day)->get();

        $totalOrders = Order::with('user')->where('vendor_id', vendorId())->orderBy('id', 'desc')->get();

        $monthlyOrders = Order::with('user')->whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->seller->id);
        })->orderBy('id', 'desc')->whereMonth('created_at', now()->month)->get();

        $yearlyOrders = Order::with('user')->whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->seller->id);
        })->orderBy('id', 'desc')->whereYear('created_at', now()->year)->get();

        $products = Product::where('vendor_id', $seller->id)->get();

        $reviews = ProductReview::where('order_id', $seller->id)->get();
        $reports = [];

        $totalWithdraw = WithdrawRequest::where('vendor_id', $seller->id)
            ->where('status', 'approved')
            ->sum('total_amount');

        $totalPendingWithdraw = WithdrawRequest::where('vendor_id', $seller->id)
            ->where('status', 'pending')
            ->sum('total_amount');

        $availableYears = Order::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray() ?? [];

        $cardData = [];

        $cardData['availableYears'] = $availableYears ?? [];

        $month = request()->get('month', now()->month);
        $year  = request()->get('year', now()->year);

        $baseQuery = Order::whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->seller->id);
        })->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $cardData['totalMonthlyOrders'] = (clone $baseQuery)->count();

        $cardData['totalMonthlyPendingOrders'] = (clone $baseQuery)
            ->where('order_status', OrderStatus::PENDING->value)
            ->count();

        $cardData['totalMonthlyShippedOrders'] = (clone $baseQuery)
            ->where('order_status', OrderStatus::SHIPPED->value)
            ->count();

        $cardData['totalMonthlyDeliveredOrders'] = (clone $baseQuery)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->count();

        [$cardData['sellChartLabels'], $cardData['sellChartValues'], $cardData['sellChartAmount']]   = (new SellerDashboardController)->getBalanceData($seller->id);
        [$cardData['salesChartLabels'], $cardData['salesChartValues'], $cardData['salesChartCount']] = (new SellerDashboardController)->getSalesCountData($seller->id);

        return view('customer::manage-sellers.dashboard', compact('user', 'todayOrders', 'totalOrders', 'monthlyOrders', 'yearlyOrders', 'products', 'reviews', 'reports', 'seller', 'totalWithdraw', 'totalPendingWithdraw', 'cardData', ));
    }
}
