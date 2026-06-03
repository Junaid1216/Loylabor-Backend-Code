<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\GlobalSetting\app\Models\AdminNotification;
use Modules\Order\app\Http\Enums\OrderStatus;
use Modules\Order\app\Models\Order;
use Modules\PaymentWithdraw\app\Models\WithdrawRequest;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\ProductReview;
use Modules\Subscription\app\Models\SubscriptionHistory;

class DashboardController extends Controller
{
    /**
     * @param Request $request
     */
    public function dashboard(Request $request)
    {
        Cache::remember('admin-notifications', 6000, function () {
            return AdminNotification::where('is_read', 0)->latest()->get();
        });

        // remove intended url from session
        $request->session()->forget('url');

        $todayOrders = Order::with('user')->orderBy('id', 'desc')->whereDay('created_at', now()->day)->get();

        $totalOrders = Order::with('user')->orderBy('id', 'desc')->get();

        $monthlyOrders = Order::with('user')->orderBy('id', 'desc')->whereMonth('created_at', now()->month)->get();

        $yearlyOrders = Order::with('user')->orderBy('id', 'desc')->whereYear('created_at', now()->year)->get();

        $products = Product::get();

        $reviews = ProductReview::get();
        $reports = [];

        $totalWithdraw = WithdrawRequest::where('status', 'approved')
            ->sum('total_amount');

        $totalPendingWithdraw = WithdrawRequest::where('status', 'pending')
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

        $baseQuery = Order::whereMonth('created_at', $month)
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

        [$cardData['sellChartLabels'], $cardData['sellChartValues'], $cardData['sellChartAmount']]   = $this->getBalanceData();
        [$cardData['salesChartLabels'], $cardData['salesChartValues'], $cardData['salesChartCount']] = $this->getSalesCountData();

        return view('admin.dashboard', compact('todayOrders', 'totalOrders', 'monthlyOrders', 'yearlyOrders', 'products', 'reviews', 'reports', 'totalWithdraw', 'totalPendingWithdraw', 'cardData'));
    }

    public function getBalanceData()
    {
        $month = request()->get('month', now()->month);
        $year  = request()->get('year', now()->year);

        // Get first and last day of selected month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $orderData = Order::join('order_payment_details', 'orders.order_payment_details_id', '=', 'order_payment_details.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_payment_details.payable_amount_without_rate) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray() ?? [];

        // Fill missing dates with zero
        $labels = [];
        $values = [];
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay());

        foreach ($period as $date) {
            $formatted = $date->format('d-m-Y');
            $labels[]  = $formatted;
            $values[]  = isset($orderData[$date->format('Y-m-d')]) ? (float) $orderData[$date->format('Y-m-d')] : 0;
        }

        // return total amount
        $totalAmount = array_sum($values);

        return [
            $labels,
            $values,
            $totalAmount,
        ];
    }

    /**
     * @return mixed
     */
    public function getSalesCountData()
    {
        $month    = request()->get('month', now()->month);
        $year     = request()->get('year', now()->year);
        $vendorId = vendorId();

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        // Sum of qty from order_details per day
        $orderData = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['items' => function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            }])
            ->get()
            ->groupBy(function ($order) {
                return $order->created_at->format('Y-m-d');
            })
            ->map(function ($orders) {
                return $orders->sum(function ($order) {
                    return $order->items->sum('qty');
                });
            })
            ->toArray();

        $labels = [];
        $values = [];
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());

        foreach ($period as $date) {
            $formatted = $date->format('d-m-Y');
            $labels[]  = $formatted;
            $values[]  = isset($orderData[$date->format('Y-m-d')]) ? (int) $orderData[$date->format('Y-m-d')] : 0;
        }

        $totalQtySold = array_sum($values);

        return [
            $labels,
            $values,
            $totalQtySold,
        ];
    }

    public function setLanguage()
    {
        $action = setLanguage(request('code'));

        if ($action) {
            $notification = __('Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);
        }

        $notification = __('Updated successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function setCurrency()
    {
        $currency = allCurrencies()->where('currency_code', request('currency'))->first();

        if (session()->has('currency_code')) {
            session()->forget('currency_code');
            session()->forget('currency_position');
            session()->forget('currency_icon');
            session()->forget('currency_rate');
        }
        if ($currency) {
            session()->put('currency_code', $currency->currency_code);
            session()->put('currency_position', $currency->currency_position);
            session()->put('currency_icon', $currency->currency_icon);
            session()->put('currency_rate', $currency->currency_rate);

            $notification = __('Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);
        }

        getSessionCurrency();
        $notification = __('Updated successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    /**
     * @param Request $request
     * @param $uuid
     * @param $type
     */
    public function invoice(Request $request, $uuid, $type = 'order')
    {
        if ($type == 'order') {
            $order = Order::whereUuid($uuid)->firstOrFail();
        } else {
            $order = SubscriptionHistory::whereUuid($uuid)->firstOrFail();
        }

        dd($order, $request->all());
    }
}
