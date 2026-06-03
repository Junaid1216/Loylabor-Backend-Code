<?php

namespace App\Http\Controllers\User;

use App\Facades\MailSender;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressRequest;
use App\Http\Requests\UserProfileUpdateRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Modules\BasicPayment\app\Services\PaymentMethodService;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderDetails;
use Modules\Product\app\Models\ProductReview;

class UserController extends Controller
{
    /**
     * @return mixed
     */
    public function dashboard()
    {
        $user = auth()->user();

        $totalOrders = Order::where('user_id', $user->id)->count();

        return view('user.pages.dashboard', compact('user', 'totalOrders'));
    }

    public function changePassword()
    {
        return view('user.pages.change-password');
    }

    /**
     * @param Request $request
     */
    public function updatePassword(Request $request)
    {
        $rules = [
            'current_password' => 'required',
            'password'         => 'required|min:4|confirmed',
        ];
        $customMessages = [
            'current_password.required' => __('Current password is required'),
            'password.required'         => __('Password is required'),
            'password.min'              => __('Password minimum 4 character'),
            'password.confirmed'        => __('Confirm password does not match'),
        ];

        $this->validate($request, $rules, $customMessages);

        $user = Auth::guard('web')->user();
        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            try {
                [$subject, $message] = MailSender::fetchEmailTemplate('password_changed', [
                    'user_name' => $user?->name ?? 'Name Missing!',
                ]);

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
        } else {
            $notification = __('Current password does not match');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }

    public function reviews()
    {
        $perPage = customPagination()->user_reviews ?? 10;

        $reviews = ProductReview::where('user_id', auth('web')->id())->latest()->paginate($perPage);

        return view('user.pages.reviews.index', compact('reviews'));
    }

    /**
     * @return mixed
     */
    public function address()
    {
        $addresses = Address::where('user_id', auth('web')->id())->paginate();

        $countries = Country::all();

        return view('user.pages.address.index', compact('addresses', 'countries'));
    }

    /**
     * @return mixed
     */
    public function orders()
    {
        $perPage = customPagination()->user_orders ?? 10;

        $userId          = auth('web')->id();
        $orders          = Order::withCount('items')->where('user_id', $userId)->latest()->paginate($perPage);
        $total_order     = Order::where('user_id', $userId)->count();
        $pending_order   = Order::where('user_id', $userId)->where('order_status', OrderStatus::PENDING->value)->count();
        $delivered_order = Order::where('user_id', $userId)->where('order_status', OrderStatus::DELIVERED->value)->count();
        $totalUnpaid     = Order::where('user_id', $userId)->whereRelation('paymentDetails', 'payment_status', PaymentStatus::PENDING->value)->count();

        return view('user.pages.orders', compact('orders', 'total_order', 'pending_order', 'delivered_order', 'totalUnpaid'));
    }

    /**
     * @return mixed
     */
    public function wishlist()
    {
        $perPage = customPagination()->user_wishlist ?? 18;

        $wishlist = Wishlist::where('user_id', auth('web')->id())
            ->whereHas('product', function ($query) {
                $query->published();
            })
            ->get()
            ->map(function ($wishlist) {
                return $wishlist->product;
            })->paginate($perPage);

        return view('user.pages.wishlist', compact('wishlist'));
    }

    /**
     * @param $uuid
     */
    public function invoice($uuid)
    {
        $order       = Order::with(['items', 'billingAddress', 'shippingAddress', 'paymentStatusHistory', 'orderStatusHistory'])->whereUuid($uuid)->firstOrFail();
        $showMessage = session('just_ordered', false);

        if ($order->is_guest_order && $showMessage) {
            return to_route('website.guest.order.complete', [
                'uuid'        => $order->uuid,
                'showMessage' => $showMessage,
            ]);
        }

        $view = $order->is_guest_order ? 'website.guest-invoice' : 'user.pages.invoice';

        return view($view, compact('order', 'showMessage'));
    }

    /**
     * @return mixed
     */
    public function editProfile()
    {
        $countries = Country::get();
        $user      = auth()->user();
        $states    = [];
        $cities    = [];

        if ($user->country_id) {
            $states = State::where('country_id', $user->country_id)->get();
        }
        if ($user->state_id) {
            $cities = City::where('state_id', $user->state_id)->get();
        }

        return view('user.pages.edit-profile', compact('user', 'countries', 'states', 'cities'));
    }

    /**
     * @param Request $request
     */
    public function storeProfile(UserProfileUpdateRequest $request)
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
            $address             = new Address;
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
            DB::beginTransaction();
            $emailSend = false;

            try {
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

        return to_route('website.user.edit.profile')->with([
            'alert-type' => 'success',
            'message'    => $message,
        ]);
    }

    /**
     * @param Request $request
     */
    public function storeAddress(UserAddressRequest $request)
    {
        $address             = new Address;
        $address->user_id    = auth('web')->id();
        $address->name       = $request->name;
        $address->email      = $request->email;
        $address->country_id = $request->a_country_id;
        $address->state_id   = $request->a_state_id;
        $address->city_id    = $request->a_city_id;
        $address->address    = $request->address;
        $address->type       = $request->type;
        $address->zip_code   = $request->zip;
        $address->phone      = $request->phone;
        $address->default    = $request->get('is_default', 0);
        $address->status     = $request->get('status', 1);
        $address->save();

        return redirect()->back()->with([
            'message'    => __('Created successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param $id
     */
    public function deleteAddress($id)
    {
        $address = Address::where([
            'id'      => $id,
            'user_id' => auth('web')->id(),
        ])->firstOr(function () {
            abort(404);
        });

        $address->delete();

        return redirect()->back()->with([
            'message'    => __('Deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function deleteProfile()
    {
        $user = auth()->user();

        $user->delete();

        // TODO: add all the realeted data to delete

        Auth::logout();

        Session::invalidate();

        return to_route('website.home')->with([
            'message'    => __('Deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param $uuid
     * @param $type
     */
    public function completePayment($uuid, $type = 'order')
    {
        $data['purchase'] = Order::where('uuid', $uuid)->firstOrFail();

        $data['amount'] = $data['purchase']->payable_amount;

        $data['currencyCode'] = $data['purchase']->payable_currency;

        $this->checkUnpaidStatus($data['purchase']);

        $data['type']            = $type;
        $data['orderId']         = $data['purchase']->uuid;
        $data['paymentViewPath'] = app(PaymentMethodService::class)->getBladeView($data['purchase']->payment_method);

        $this->checkIsMethodActive($data['purchase']->payment_method);

        return view('website.complete-payment', $data);
    }

    /**
     * @param $order
     */
    protected function checkUnpaidStatus($order)
    {
        $errorMessage = 'Payment already ' . $order->paymentDetails->payment_status->getLabel();

        if ($order->paymentDetails->payment_status->value != OrderStatus::PENDING->value) {
            session()->flash('message', __($errorMessage));
            session()->flash('alert-type', 'error');

            throw new HttpResponseException(
                redirect()
                    ->back()
                    ->with(['message' => __($errorMessage), 'alert-type' => 'error']),
            );
        }
    }

    /**
     * @param $method
     */
    public function checkIsMethodActive($method)
    {
        if (!app(PaymentMethodService::class)->isActive($method)) {
            if (request()->expectsJson()) {
                throw new HttpResponseException(response()->json(['message' => __('This payment method is not active'), 'alert-type' => 'error'], 422));
            } else {
                throw new HttpResponseException(redirect()->back()->with(['message' => __('This payment method is not active'), 'alert-type' => 'error']));
            }
        }
    }

    /**
     * @param Request      $request
     * @param $orderId
     * @param $productId
     */
    public function addReviews(Request $request, $orderId, $productId = null)
    {
        $order = Order::with([
            'items' => [
                'product',
                'review',
            ],
        ])->where([
            'user_id'  => auth('web')->id(),
            'order_id' => $orderId,
        ])->when(!is_null($productId), function ($query) use ($productId) {
            $query->whereRelation('items', 'product_id', $productId);
        })->firstOrFail();

        return view('user.pages.reviews.create', compact('order'));
    }

    /**
     * @param  Request $request
     * @return mixed
     */
    public function storeReviews(Request $request)
    {
        $request->validate([
            'order_details_id' => 'required|exists:order_details,id',
            'order_id'         => 'required|exists:orders,id',
            'product_id'       => 'required|exists:products,id',
            'rating'           => 'required',
            'review'           => 'required',
        ], [
            'order_details_id.required' => __('Order is required'),
            'order_details_id.exists'   => __('Order not found'),
            'order_id.required'         => __('Order is required'),
            'order_id.exists'           => __('Order not found'),
            'product_id.required'       => __('Product id is required'),
            'product_id.exists'         => __('Product not found'),
            'rating.required'           => __('Rating is required'),
            'review.required'           => __('Review is required'),
        ]);

        $orderDetails = OrderDetails::where([
            'id'         => $request->order_details_id,
            'user_id'    => auth('web')->id(),
            'order_id'   => $request->order_id,
            'product_id' => $request->product_id,
        ])->firstOr(function () {
            throw new HttpResponseException(response()->json(['message' => __('Order not found'), 'alert-type' => 'error'], 422));
        });

        ProductReview::updateOrCreate([
            'product_id'       => $orderDetails->product_id,
            'user_id'          => auth('web')->id(),
            'vendor_id'        => $orderDetails->vendor_id,
            'order_id'         => $orderDetails->order_id,
            'order_details_id' => $orderDetails->id,
            'product_sku'      => $orderDetails->product_sku,
        ], [
            'rating'  => $request->rating,
            'review'  => $request->review,
            'options' => $orderDetails->options,
        ]);

        $message = "New Product review added by " . auth('web')->user()->name . " to the product " . $orderDetails->product->name;
        $link    = route('admin.product-review', ['product' => $orderDetails->product->slug]);

        notifyAdmin(
            'New Product Review Added',
            $message,
            link: $link,
        );

        return response()->json([
            'message'    => __('Created successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param $id
     */
    public function deleteReviews($id)
    {
        $review = ProductReview::where([
            'id'      => $id,
            'user_id' => auth('web')->id(),
        ])->firstOr(function () {
            throw new HttpResponseException(redirect()->back()->with(['message' => __('Review not found'), 'alert-type' => 'error']));
        });

        $review->delete();

        return redirect()->back()->with([
            'message'    => __('Deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param $uuid
     */
    public function cancelOrder($uuid)
    {
        $order = Order::where([
            'uuid'         => $uuid,
            'user_id'      => auth('web')->id(),
            'order_status' => OrderStatus::PENDING->value,
        ])->whereRelation('paymentDetails', 'payment_status', PaymentStatus::PENDING->value)->firstOr(function () {
            throw new HttpResponseException(redirect()->back()->with(['message' => __('Order not found'), 'alert-type' => 'error']));
        });

        $orderCancelLimit = (int) getSettings('order_cancel_minutes_before');

        $orderCreatedAt = Carbon::parse($order->created_at);

        $orderCancelTime = $orderCreatedAt
            ->copy()
            ->addMinutes($orderCancelLimit);

        $now = Carbon::now();

        if (!$now->lt($orderCancelTime)) {
            throw new HttpResponseException(redirect()->back()->with(['message' => __('Order cancel time expired'), 'alert-type' => 'error']));
        }

        $payload = null;

        try {
            DB::beginTransaction();

            $order->order_status = OrderStatus::CANCELLED->value;
            $order->save();

            DB::commit();

            $payload = $this->generateGTMData($order);
        } catch (Exception $e) {
            logError("Order Cancel Error", $e);
            return redirect()->back()->with([
                'message'    => __('Order cancel failed'),
                'alert-type' => 'error',
            ]);
        }

        if ($payload) {
            pushToGTM($payload);
        }

        return redirect()->back()->with([
            'message'    => __('Order cancelled successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param $order
     */
    private function generateGTMData($order)
    {
        $items = [];

        foreach ($order->items as $item) {
            $name = $item->is_variant == 1 ? $item->product_name . ' | ' . $item->product_sku . ' | ' . $item->options : $item->product_name . ' | ' . $item->product_sku;

            $items[] = [
                'item_id'   => $item->product_id,
                'item_name' => $name,
                'price'     => (float) number_format($item->price, 2, '.', ''),
                'quantity'  => (int) $item->qty,
            ];
        }

        pushToGTM([
            'event'     => 'order_cancelled',
            'user_id'   => auth()->id() ?? 0,
            'user_role' => auth()->check() ? auth()->user()->name : 'guest',
            'language'  => getSessionLanguage(),
            'ecommerce' => [
                'transaction_id' => $order->order_number ?? $order->id,
                'value'          => (float) number_format($order->payable_amount, 2, '.', ''),
                'currency'       => $order->payable_currency ?? 'USD',
                'items'          => $items,
            ],
        ]);
    }
}
