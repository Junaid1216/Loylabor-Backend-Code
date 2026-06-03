<?php

namespace Modules\Order\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\GlobalMailTrait;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BasicPayment\app\Traits\PaymentTrait;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderStatusChangeHistory;
use Modules\Order\app\Models\TransactionHistory;
use Modules\Product\app\Models\Variant;

class OrderController extends Controller
{
    use GlobalMailTrait, PaymentTrait;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $query = Order::query();

        $query->with('user');

        $query->when($request->filled('user'), function ($q) use ($request) {
            $q->where('user_id', $request->user);
        });

        $query->when($request->filled('vendor_id'), function ($q) use ($request) {
            $q->where('vendor_id', $request->vendor_id);
        });

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->whereAny(['order_id', 'transaction_id', 'total_amount', 'payment_method'], 'like', '%' . $request->keyword . '%')
                ->orWhereHas('billingAddress', function ($q) use ($request) {
                    $q->whereAny(['name', 'email', 'phone', 'address', 'city', 'state', 'country', 'zip_code'], 'like', '%' . $request->keyword . '%');
                })
                ->orWhereHas('shippingAddress', function ($q) use ($request) {
                    $q->whereAny(['name', 'email', 'phone', 'address', 'city', 'state', 'country', 'zip_code'], 'like', '%' . $request->keyword . '%');
                })
                ->orWhereHas('user', function ($q) use ($request) {
                    $q->whereAny(['name', 'email', 'phone', 'address'], 'like', '%' . $request->keyword . '%');
                });

        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('order_status', $request->status);
        });

        $query->when($request->filled('payment_status'), function ($q) use ($request) {
            $q->whereRelation('paymentDetails', 'payment_status', $request->payment_status);
        });

        $query->when($request->filled('order_by'), function ($q) use ($request) {
            $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
        });

        if ($request->filled('par-page')) {
            $orders = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $orders = $query->paginate()->withQueryString();
        }

        $route = 'admin.orders';
        $title = __('Order History');
        $users = User::select('name', 'id')->get();

        return view('order::index', ['orders' => $orders, 'title' => $title, 'route' => $route, 'users' => $users]);
    }

    /**
     * @param Request $request
     */
    public function pending_order(Request $request)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $query = Order::query();

        $query->with('user')->where('order_status', OrderStatus::PENDING->value);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('order_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('transaction_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('total_amount', 'like', '%' . $request->keyword . '%')
                ->orWhere('payment_method', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('user'), function ($q) use ($request) {
            $q->where('user_id', $request->user);
        });

        $query->when($request->filled('order_by'), function ($q) use ($request) {
            $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
        });

        if ($request->filled('par-page')) {
            $orders = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $orders = $query->paginate()->withQueryString();
        }

        $route = 'admin.pending-orders';
        $title = __('Pending Order');

        $users = User::select('name', 'id')->get();

        return view('order::index', ['orders' => $orders, 'title' => $title, 'route' => $route, 'users' => $users]);
    }

    /**
     * @param Request $request
     */
    public function pending_payment(Request $request)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $query = Order::query();

        $query->with('user')->whereRelation('paymentDetails', 'payment_status', PaymentStatus::PENDING->value);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('order_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('transaction_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('total_amount', 'like', '%' . $request->keyword . '%')
                ->orWhere('payment_method', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('user'), function ($q) use ($request) {
            $q->where('user_id', $request->user);
        });

        $query->when($request->filled('order_by'), function ($q) use ($request) {
            $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
        });

        if ($request->filled('par-page')) {
            $orders = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $orders = $query->paginate()->withQueryString();
        }
        $route = 'admin.pending-payment';

        $title = __('Pending Payment');
        $users = User::select('name', 'id')->get();

        return view('order::index', ['orders' => $orders, 'title' => $title, 'route' => $route, 'users' => $users]);
    }

    /**
     * @param Request $request
     */
    public function rejected_payment(Request $request)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $query = Order::query();
        $query->with('user')->whereRelation('paymentDetails', 'payment_status', PaymentStatus::REJECTED->value);

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->where('order_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('transaction_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('total_amount', 'like', '%' . $request->keyword . '%')
                ->orWhere('payment_method', 'like', '%' . $request->keyword . '%');
        });

        $query->when($request->filled('user'), function ($q) use ($request) {
            $q->where('user_id', $request->user);
        });

        $query->when($request->filled('order_by'), function ($q) use ($request) {
            $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
        });

        if ($request->filled('par-page')) {
            $orders = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $orders = $query->paginate()->withQueryString();
        }

        $route = 'admin.rejected-payment';
        $title = __('Rejected Payment');

        $users = User::select('name', 'id')->get();

        return view('order::index', ['orders' => $orders, 'title' => $title, 'route' => $route, 'users' => $users]);
    }

    /**
     * @param $order_id
     */
    public function show($order_id)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::with([
            'shippingAddress',
            'items',
            'user',
            'billingAddress',
            'seller',
            'transactionHistories' => function ($query) {
                $query->latest();
            },
            'orderStatusHistory'   => function ($query) {
                $query->with('changedByAdmin', 'changedByUser')->latest();
            },
            'paymentStatusHistory' => function ($query) {
                $query->with('changedByAdmin', 'changedByUser')->latest();
            },
            'reviews',
        ])
            ->where('order_id', $order_id)
            ->firstOr(function () {
                throw new HttpResponseException(redirect()->back()->with([
                    'message'    => __('Order not found'),
                    'alert-type' => 'error',
                ]));
            });

        $orderStatuses   = OrderStatus::cases();
        $paymentStatuses = PaymentStatus::cases();

        return view('order::show', ['order' => $order, 'orderStatuses' => $orderStatuses, 'paymentStatuses' => $paymentStatuses]);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function updateOrderStatus(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('order.status.update');

        $request->validate([
            'order_status' => 'required|in:' . implode(',', OrderStatus::getAll()),
            'comment'      => 'nullable|string|max:3000',
        ], [
            'order_status.required' => __('Order status is required'),
            'order_status.in'       => __('Invalid order status'),
            'comment.string'        => __('Comment must be a string'),
            'comment.max'           => __('Comment must be less than 3000 characters'),
        ]);

        $order = Order::findOrFail($id);

        if ($order->paymentDetails->payment_status == PaymentStatus::REJECTED && $request->order_status != OrderStatus::CANCELLED->value) {
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Cannot update status of an order with rejected payment'),
                    'alert-type' => 'error',
                ])
            );
        }

        if ($order->paymentDetails->payment_status == PaymentStatus::COMPLETED && $request->order_status == OrderStatus::CANCELLED->value) {
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Cannot cancel an order with completed payment'),
                    'alert-type' => 'error',
                ])
            );
        }

        if ($order->orderStatusHistory()->where('to_status', OrderStatus::CANCELLED->value)->exists()) {
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Cannot update status of a cancelled order'),
                    'alert-type' => 'error',
                ])
            );
        }

        if ($order->orderStatusHistory()->where('to_status', OrderStatus::DELIVERED->value)->exists() && $request->order_status == OrderStatus::CANCELLED->value) {
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Cannot cancel an order that has already been delivered'),
                    'alert-type' => 'error',
                ])
            );
        }

        $deliveryExists = $order->orderStatusHistory()->where('to_status', OrderStatus::DELIVERED->value)->exists();

        try {
            DB::beginTransaction();

            $orderStatusEnum = OrderStatus::tryFrom($request->order_status);

            $oldStatus           = $order->order_status;
            $order->order_status = $request->order_status;
            $order->save();

            $newOrderStatusHistory                = new OrderStatusChangeHistory();
            $newOrderStatusHistory->order_id      = $order->id;
            $newOrderStatusHistory->type          = 'order_status';
            $newOrderStatusHistory->from_status   = $oldStatus;
            $newOrderStatusHistory->to_status     = $orderStatusEnum->value;
            $newOrderStatusHistory->message       = $request->comment;
            $newOrderStatusHistory->change_by     = 'admin';
            $newOrderStatusHistory->changed_by_id = auth('admin')->id();
            $newOrderStatusHistory->save();

            if ($request->order_status == OrderStatus::DELIVERED->value && !$deliveryExists) {
                $items = $order->items;

                if ($items) {
                    foreach ($items as $item) {
                        // Variant
                        if ($item->is_variant) {
                            $variant = Variant::where('sku', $item->product_sku)
                                ->where('product_id', $item->product_id)
                                ->first();

                            if ($variant) {
                                $variantOldStock = $variant->stock_qty ?? 0;
                                $stockToReduce   = $item->qty;

                                if ($variantOldStock > 0 && $item?->product?->manage_stock) {
                                    if ($variantOldStock >= $stockToReduce) {
                                        $currentUpdatedStock = $variantOldStock - $stockToReduce;
                                        $variant->manageStocks()->update(['quantity' => $currentUpdatedStock]);

                                        notifyStockChange(
                                            'Variant',
                                            $variant->product->name,
                                            $variant->id,
                                            $variantOldStock,
                                            $currentUpdatedStock,
                                            $order->order_id,
                                            $item->product_id
                                        );
                                    } else {
                                        $variant->manageStocks()->update(['quantity' => 0]);

                                        notifyAdmin(
                                            "Stock Mismatch Alert for Order #{$order->order_id}",
                                            "Variant `{$variant->product->name}` (ID: {$variant->id}) had only {$variantOldStock} in stock, but attempted to reduce {$stockToReduce} units for Order #{$order->order_id}. Stock has been set to 0. Please review.",
                                            'warning',
                                            route('admin.product.show', ['product' => $item->product_id])
                                        );
                                    }
                                }
                            }

                            // Flash Deal
                        } elseif ($item->is_flash_deal == 1) {
                            $product         = $item->product;
                            $flashDealOldQty = $product->flash_deal_qty ?? 0;
                            $qtyToReduce     = $item->qty;

                            if ($flashDealOldQty > 0) {
                                if ($flashDealOldQty >= $qtyToReduce) {
                                    $updatedQty              = max(0, $flashDealOldQty - $qtyToReduce);
                                    $product->flash_deal_qty = $updatedQty;
                                    $product->save();

                                    notifyStockChange(
                                        'Flash Deal Product',
                                        $product->name,
                                        $product->id,
                                        $flashDealOldQty,
                                        $updatedQty,
                                        $order->order_id,
                                        $product->id
                                    );
                                } else {
                                    $product->flash_deal_qty = 0;
                                    $product->save();

                                    notifyAdmin(
                                        "Flash Deal Stock Mismatch for Order #{$order->order_id}",
                                        "Flash Deal Product `{$product->name}` (ID: {$product->id}) had only {$flashDealOldQty} in stock, but attempted to reduce {$qtyToReduce} units for Order #{$order->order_id}. Quantity has been set to 0. Please verify.",
                                        'warning',
                                        route('admin.product.show', ['product' => $product->id])
                                    );
                                }
                            }

                            // Regular Product
                        } else {
                            $product         = $item->product;
                            $productOldStock = $product->stock_qty ?? 0;
                            $stockToReduce   = $item->qty;

                            if ($productOldStock > 0 && $item?->product?->manage_stock) {
                                if ($productOldStock >= $stockToReduce) {
                                    $currentUpdatedStock = $productOldStock - $stockToReduce;
                                    $product->manageStocks()->update(['quantity' => $currentUpdatedStock]);

                                    notifyStockChange(
                                        'Product',
                                        $product->name,
                                        $product->id,
                                        $productOldStock,
                                        $currentUpdatedStock,
                                        $order->order_id,
                                        $product->id
                                    );
                                } else {
                                    $product->manageStocks()->update(['quantity' => 0]);

                                    notifyAdmin(
                                        "Stock Mismatch Alert for Order #{$order->order_id}",
                                        "Product `{$product->name}` (ID: {$product->id}) had only {$productOldStock} in stock, but attempted to reduce {$stockToReduce} units for Order #{$order->order_id}. Stock has been set to 0. Please review.",
                                        'warning',
                                        route('admin.product.show', ['product' => $product->id])
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $this->sendOrderStatusChangeMail($order, true);

            notifyAdmin("Order #{$order->order_id} Status Updated.", "Order #{$order->order_id} status has been updated to {$orderStatusEnum->getLabel()}.", 'order', route('admin.order', ['id' => $order->order_id]));

            DB::commit();

            return redirect()->back()->with(['message' => __('Updated successfully'), 'alert-type' => 'success']);
        } catch (Exception $e) {
            DB::rollBack();
            logError("Error updating order status", $e);
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Failed to update order status'),
                    'alert-type' => 'error',
                ])
            );
        }
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function updateOrderPaymentStatus(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('order.payment.update');

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:' . implode(',', PaymentStatus::getAll()),
            'comment'        => 'required_if:payment_status,' . PaymentStatus::REJECTED->value,
            'paid_amount'    => 'numeric',
        ], [
            'payment_status.required' => __('Payment status is required'),
            'payment_status.in'       => __('Invalid payment status'),
            'comment.string'          => __('Comment must be a string'),
            'comment.max'             => __('Comment must be less than 3000 characters'),
            'comment.required_if'     => __('Comment is required when payment status is rejected'),
            'paid_amount.required'    => __('Paid amount is required when payment status is completed and payment method is hand cash'),
            'paid_amount.numeric'     => __('Paid amount must be numeric'),
            'paid_amount.lte'         => __('The paid amount cannot be greater than the payable amount'),
            'paid_amount.min'         => __('The paid amount must be at least 0.'),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $order = Order::findOrFail($id);

        $payableAmount = $order->paymentDetails->payable_amount;

        $validator->sometimes('paid_amount', 'required', function ($input) {
            return $input->payment_method === 'hand_cash' &&
            $input->payment_status === PaymentStatus::COMPLETED->value;
        });

        $validator->sometimes('paid_amount', "numeric|min:0|lte:$payableAmount", function ($input) {
            return $input->payment_method === 'hand_cash' &&
            $input->payment_status === PaymentStatus::COMPLETED->value;
        });

        $validator->validate();

        $oldStatus        = $order->order_status;
        $oldPaymentStatus = $order->paymentDetails->payment_status;

        $paymentStatusEnum = PaymentStatus::tryFrom($request->payment_status);

        if ($order->paymentDetails->payment_status == PaymentStatus::REJECTED && $paymentStatusEnum != PaymentStatus::COMPLETED) {
            throw new HttpResponseException(
                redirect()->back()->with([
                    'message'    => __('Cannot update payment status of an order with rejected payment'),
                    'alert-type' => 'error',
                ])
            );
        }

        try {
            DB::beginTransaction();

            $order->paymentDetails->payment_status = $paymentStatusEnum->value;
            $order->push();

            $newPaymentStatusHistory                = new OrderStatusChangeHistory();
            $newPaymentStatusHistory->order_id      = $order->id;
            $newPaymentStatusHistory->type          = 'payment_status';
            $newPaymentStatusHistory->from_status   = $oldPaymentStatus;
            $newPaymentStatusHistory->to_status     = $paymentStatusEnum->value;
            $newPaymentStatusHistory->message       = $request->comment;
            $newPaymentStatusHistory->change_by     = 'admin';
            $newPaymentStatusHistory->changed_by_id = auth('admin')->id();
            $newPaymentStatusHistory->save();

            if ($paymentStatusEnum == PaymentStatus::COMPLETED) {
                $handCashPaid = false;
                if ($request->filled('payment_method') && $request->payment_method == 'hand_cash') {
                    $order->paymentDetails->paid_amount = $request->paid_amount;
                    $handCashPaid                       = true;
                }

                $trxId = $order->paymentDetails->transaction_id;

                if (!$trxId) {
                    $trxId                                 = uniqid('manual_');
                    $order->paymentDetails->transaction_id = $trxId;
                }

                $order->push();

                if ($handCashPaid) {
                    notifyAdmin("Order #{$order->order_id} Payment Updated.", "Order #{$order->order_id} payment status has been updated to {$paymentStatusEnum->getLabel()} and recevied hand_cash amount {$request->paid_amount}.", 'order', route('admin.order', ['id' => $order->order_id]));
                }

                $this->storeTransactionHistory($order, $trxId);

                $this->sendConfirmMail($order);

                $this->saveWalletOnSuccess($order);
            }

            if ($paymentStatusEnum == PaymentStatus::REJECTED) {
                $user = $order->billingAddress;
                if ($user) {
                    $message = $request->filled('comment') ? $request->comment : __('Dear [[name]], Your payment has been rejected. Please contact support for more details.');
                    $message = str_replace('[[name]]', $user->name, $message);
                    $this->sendMail($user->email, $request->subject, $message);
                }
            }

            if ($paymentStatusEnum == PaymentStatus::COMPLETED) {
                $order->order_status = OrderStatus::APPROVED->value;
                $order->save();

                $newOrderStatusHistory                = new OrderStatusChangeHistory();
                $newOrderStatusHistory->order_id      = $order->id;
                $newOrderStatusHistory->type          = 'order_status';
                $newOrderStatusHistory->from_status   = $oldStatus;
                $newOrderStatusHistory->to_status     = $order->order_status->value;
                $newOrderStatusHistory->message       = $request->comment;
                $newOrderStatusHistory->change_by     = 'admin';
                $newOrderStatusHistory->changed_by_id = auth('admin')->id();
                $newOrderStatusHistory->save();

                $this->sendOrderStatusChangeMail($order, true);
            } else {
                $this->sendPaymentStatusChangeMail($order, true);
            }

            if (
                $paymentStatusEnum == PaymentStatus::COMPLETED
                && OrderStatusChangeHistory::where([
                    'type'      => 'payment_status',
                    'to_status' => PaymentStatus::COMPLETED->value,
                    'order_id'  => $order->id,
                ])->count() == 1
            ) {
                $this->saveWalletOnSuccess($order);
            }

            notifyAdmin("Order #{$order->order_id} Payment Status Updated.", "Order #{$order->order_id} payment status has been updated to {$paymentStatusEnum->getLabel()}.", 'order', route('admin.order', ['id' => $order->order_id]));

            DB::commit();

            return redirect()->back()->with(['message' => __('Updated successfully'), 'alert-type' => 'success']);
        } catch (Exception $e) {
            DB::rollBack();

            logError("Error updating order payment status", $e);

            return redirect()->back()->with([
                'message'    => __('Failed to update order payment status'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function acceptBankPayment(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('order.payment.update');

        $request->validate([
            'bank_payment_status' => 'required|in:' . PaymentStatus::PROCESSING->value . ',' . PaymentStatus::COMPLETED->value . ',' . PaymentStatus::REJECTED->value,
            'subject'             => 'required',
            'description'         => 'required',
        ], [
            'bank_payment_status.required' => __('Bank payment status is required'),
            'bank_payment_status.in'       => __('Invalid bank payment status'),
            'subject.required'             => __('Subject is required'),
            'description.required'         => __('Description is required'),
        ]);

        try {
            DB::beginTransaction();
            $order                                 = Order::findOrFail($id);
            $oldPaymentStatus                      = $order->paymentDetails->payment_status;
            $order->paymentDetails->payment_status = $request->bank_payment_status;
            $order->paymentDetails->paid_amount    = $order->paymentDetails->payable_amount;
            $order->push();

            if ($order->paymentDetails->payment_status == PaymentStatus::COMPLETED) {
                $trxId = $order->paymentDetails->transaction_id;

                if (!$trxId) {
                    $trxId                                 = uniqid('bank_');
                    $order->paymentDetails->transaction_id = $trxId;
                }
                $order->push();

                $order->addOrderHistory('payment_status', $oldPaymentStatus);

                $this->storeTransactionHistory($order, $trxId);

                $this->saveWalletOnSuccess($order);
            }

            notifyAdmin("Order #{$order->order_id} Payment Status Updated.", "Order #{$order->order_id} payment status has been updated to {$request->bank_payment_status}.", 'order', route('admin.order', ['id' => $order->order_id]));

            DB::commit();
        } catch (Exception $e) {
            logError("Error updating bank payment status for order #{$id}", $e);
            DB::rollBack();

            return back()->with([
                'message'    => __('Failed to update bank payment status'),
                'alert-type' => 'error',
            ]);
        }

        if ($order->paymentDetails->payment_status == PaymentStatus::COMPLETED) {
            $this->sendConfirmMail($order);
        }

        $user = $order->billingAddress;

        //mail send
        $message = $request->description;
        $message = str_replace('[[name]]', $user->name, $message);
        $this->sendMail($user->email, $request->subject, $message);

        $notification = __('Payment approved successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function updateOrderQty()
    {
        checkAdminHasPermissionAndThrowException('order.edit-update');
        // next update feature
    }

    /**
     * @param $id
     */
    public function allTransactionHistories(Request $request, $id = null)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $perPage = $request->input('per_page', 15);

        $transactions = TransactionHistory::with('order')
            ->when($id, function ($query) use ($id) {
                $query->whereRelation('order', 'order_id', $id);
            })
            ->when($request->filled('keyword'), function ($query) {
                $query->where('transaction_id', 'like', '%' . request('keyword') . '%')
                    ->orWhereHas('order', function ($query) {
                        $query->where('order_id', 'like', '%' . request('keyword') . '%')
                            ->orWhereHas('billingAddress', function ($q) {
                                $q->whereAny(['name', 'email', 'phone'], 'like', '%' . request('keyword') . '%');
                            });
                    });
            })
            ->when($request->filled('order_by'), function ($query) {
                $orderBy = request('order_by') == 1 ? 'asc' : 'desc';
                $query->orderBy('created_at', $orderBy);
            }, function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->when($request->filled('method'), function ($query) {
                $query->where('payment_method', request('method'));
            })
            ->paginate($perPage);

        $transactionMethods = TransactionHistory::select('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->toArray();

        return view('order::transaction-history.index', [
            'transactions'       => $transactions,
            'transactionMethods' => $transactionMethods,
        ]);
    }

    /**
     * @param Request $request
     */
    public function statusUpdateHistories(Request $request, $id = null)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $allNotifications = OrderStatusChangeHistory::when($id, function ($query) use ($id) {
            $query->whereRelation('order', 'order_id', $id);
        })
            ->with([
                'order',
                'changedByUser',
                'changedByAdmin',
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $query->whereHas('order', function ($query) use ($request) {
                    $query->whereAny(['order_id'], 'like', '%' . $request->keyword . '%')
                        ->orWhereHas('billingAddress', function ($q) use ($request) {
                            $q->whereAny(['name', 'email', 'phone', 'address', 'city', 'state', 'country', 'zip_code'], 'like', '%' . $request->keyword . '%');
                        })
                        ->orWhereHas('shippingAddress', function ($q) use ($request) {
                            $q->whereAny(['name', 'email', 'phone', 'address', 'city', 'state', 'country', 'zip_code'], 'like', '%' . $request->keyword . '%');
                        })
                        ->orWhereHas('paymentDetails', function ($q) use ($request) {
                            $q->whereAny(['transaction_id', 'payment_method'], 'like', '%' . $request->keyword . '%');
                        })
                        ->orWhereHas('user', function ($q) use ($request) {
                            $q->whereAny(['name', 'email', 'phone', 'address'], 'like', '%' . $request->keyword . '%');
                        });
                })->orWhereAny(['type', 'to_status'], 'like', '%' . $request->keyword . '%');
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->type == 'order' ? 'order_status' : 'payment_status';
                $query->where('type', $type);
            })
            ->latest()
            ->paginate(30);

        return view('order::status-update-history.index', compact('allNotifications'));
    }

    /**
     * @param $id
     */
    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('order.delete');

        $order = Order::findOrFail($id);

        try {
            DB::beginTransaction();

            if ($order?->paymentDetails?->payment_status == PaymentStatus::COMPLETED->value) {
                DB::rollBack();
                return back()->with([
                    'message'    => __('You cannot delete an order with completed payment, this will cause data inconsistency.'),
                    'alert-type' => 'error',
                ]);
            }

            // Delete all related OrderDetails
            $order->items()->delete();

            // Delete shipping and billing address
            $order->shippingAddress()->delete();
            $order->billingAddress()->delete();

            // Delete transaction histories
            $order->transactionHistories()->delete();

            // Delete status change histories
            $order->orderStatusHistory()->delete();
            $order->paymentStatusHistory()->delete();

            // Optional: Delete payment details only if no other orders use it
            if ($order->paymentDetails && $order->paymentDetails->orders()->count() <= 1) {
                $order->paymentDetails()->delete();
            }

            $orderId = $order->order_id;

            $order->delete();

            $admin  = auth('admin')->user();
            $time   = formattedDateTime(now());
            $ip     = request()->getClientIp();
            $device = request()->userAgent();

            notifyAdmin("Order #{$orderId} Deleted", "Order #{$orderId} has been successfully deleted by admin {$admin->name} at {$time} from IP address {$ip} using device: {$device}.", 'order', route('admin.orders'));

            DB::commit();

            $notification = __('Order deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            logError('Delete Related Data Error, Order id: ' . $order->id, $e);
            $notification = __('An error occurred while deleting the order. Please try again later.');
        }

        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return to_route('admin.orders')->with($notification);
    }

    /**
     * @param $order_id
     */
    public function invoice($order_id)
    {
        checkAdminHasPermissionAndThrowException('order.management');

        $order = Order::with([
            'shippingAddress',
            'items',
            'user',
            'billingAddress',
            'seller',
            'transactionHistories',
            'orderStatusHistory',
            'paymentStatusHistory',
            'reviews',
        ])
            ->where('order_id', $order_id)
            ->firstOrFail();

        return view('order::invoice', ['order' => $order]);
    }
}
