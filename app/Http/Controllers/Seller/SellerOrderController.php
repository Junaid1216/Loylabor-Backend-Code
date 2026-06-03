<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Traits\GlobalMailTrait;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BasicPayment\app\Traits\PaymentTrait;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderStatusChangeHistory;
use Modules\Product\app\Models\Variant;

class SellerOrderController extends Controller
{
    use GlobalMailTrait, PaymentTrait;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        $query = Order::query();

        $query->where('vendor_id', vendorId());

        $query->with('user');

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

        $query->when($request->filled('order_by'), function ($q) use ($request) {
            $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
        });

        $orders = $request->filled('par-page') ? $query->paginate($request->get('par-page'))->withQueryString() : $query->paginate()->withQueryString();

        $route = 'seller.orders.index';

        $title = __('Order History');

        return view('vendor::order.index', ['orders' => $orders, 'title' => $title, 'route' => $route]);
    }

    /**
     * @param $order_id
     */
    public function show($order_id)
    {
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
            ->where('vendor_id', vendorId())
            ->firstOr(function () {
                throw new HttpResponseException(redirect()->back()->with([
                    'message'    => __('Order not found'),
                    'alert-type' => 'error',
                ]));
            });

        $orderStatuses   = OrderStatus::cases();
        $paymentStatuses = PaymentStatus::cases();

        return view('vendor::order.show', ['order' => $order, 'orderStatuses' => $orderStatuses, 'paymentStatuses' => $paymentStatuses]);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|in:' . implode(',', OrderStatus::getAll()),
            'comment'      => 'nullable|string|max:3000',
        ], [
            'order_status.required' => __('Order status is required'),
            'order_status.in'       => __('Invalid order status'),
            'comment.string'        => __('Comment must be a string'),
            'comment.max'           => __('Comment must be less than 3000 characters'),
        ]);

        $order = Order::where('vendor_id', vendorId())->findOrFail($id);

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
            $newOrderStatusHistory->change_by     = 'user';
            $newOrderStatusHistory->changed_by_id = auth('web')->id();
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

                            // Flash Deal Product
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

            $this->sendOrderStatusChangeMail($order);

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
     * @param $order_id
     */
    public function invoice($order_id)
    {
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
            ->where('vendor_id', vendorId())
            ->where('order_id', $order_id)
            ->firstOrFail();

        return view('vendor::order.invoice', ['order' => $order]);
    }
}
