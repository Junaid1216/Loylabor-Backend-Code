<?php

namespace Modules\Wallet\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\GlobalMailTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\Order\app\Http\Enums\PaymentStatus;
use Modules\Wallet\app\Http\Controllers\WalletController as UserWalletController;
use Modules\Wallet\app\Models\WalletHistory;

class WalletController extends Controller
{
    use GlobalMailTrait;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $query = WalletHistory::query()
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                        ->orWhereHas('vendor', function ($q) use ($keyword) {
                            $q->where('shop_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('order', function ($q) use ($keyword) {
                            $q->whereAny(['order_id', 'transaction_id'], 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('orderDetails', function ($q) use ($keyword) {
                            $q->whereAny(['product_name', 'product_sku'], 'like', '%' . $keyword . '%');
                        })->orWhere('transaction_id', 'like', '%' . $keyword . '%');
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('payment_status', $request->status);
            })
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            })
            ->when($request->filled('order_by'), function ($q) use ($request) {
                $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
            }, function ($q) {
                $q->latest();
            });

        if ($request->filled('par-page')) {
            $data['wallet_histories'] = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $data['wallet_histories'] = $query->paginate()->withQueryString();
        }

        $data['totalCreditAmount']        = WalletHistory::where('transaction_type', 'credit')->sum('amount');
        $data['totalDebitAmount']         = WalletHistory::where('transaction_type', 'debit')->sum('amount');
        $data['totalPendingCreditAmount'] = WalletHistory::where('transaction_type', 'credit')->where('payment_status', 'pending')->sum('amount');

        $data['sellers'] = Vendor::with('user')->get();

        $data['title'] = __('Wallet History');

        return view('wallet::admin.index', $data);
    }

    /**
     * @param Request $request
     */
    public function pending_wallet_payment(Request $request)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $query = WalletHistory::where('payment_status', 'pending')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                        ->orWhereHas('vendor', function ($q) use ($keyword) {
                            $q->where('shop_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('order', function ($q) use ($keyword) {
                            $q->whereAny(['order_id', 'transaction_id'], 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('orderDetails', function ($q) use ($keyword) {
                            $q->whereAny(['product_name', 'product_sku'], 'like', '%' . $keyword . '%');
                        })->orWhere('transaction_id', 'like', '%' . $keyword . '%');
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('payment_status', $request->status);
            })
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            })
            ->when($request->filled('order_by'), function ($q) use ($request) {
                $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
            }, function ($q) {
                $q->latest();
            });

        if ($request->filled('par-page')) {
            $data['wallet_histories'] = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $data['wallet_histories'] = $query->paginate()->withQueryString();
        }
        $data['totalCreditAmount']        = WalletHistory::where('transaction_type', 'credit')->sum('amount');
        $data['totalDebitAmount']         = WalletHistory::where('transaction_type', 'debit')->sum('amount');
        $data['totalPendingCreditAmount'] = WalletHistory::where('transaction_type', 'credit')
            ->where('payment_status', 'pending')
            ->sum('amount');

        $data['sellers'] = Vendor::with('user')->get();
        $data['title']   = __('Pending Wallet Payment');

        return view('wallet::admin.index', $data);
    }

    /**
     * @param Request $request
     */
    public function rejected_wallet_payment(Request $request)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $query = WalletHistory::where('payment_status', 'rejected')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                        ->orWhereHas('vendor', function ($q) use ($keyword) {
                            $q->where('shop_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('order', function ($q) use ($keyword) {
                            $q->whereAny(['order_id', 'transaction_id'], 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('orderDetails', function ($q) use ($keyword) {
                            $q->whereAny(['product_name', 'product_sku'], 'like', '%' . $keyword . '%');
                        })->orWhere('transaction_id', 'like', '%' . $keyword . '%');
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('payment_status', $request->status);
            })
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            })
            ->when($request->filled('order_by'), function ($q) use ($request) {
                $q->orderBy('id', $request->order_by == 1 ? 'asc' : 'desc');
            }, function ($q) {
                $q->latest();
            });

        if ($request->filled('par-page')) {
            $data['wallet_histories'] = $query->paginate($request->get('par-page'))->withQueryString();
        } else {
            $data['wallet_histories'] = $query->paginate()->withQueryString();
        }
        $data['totalCreditAmount']        = WalletHistory::where('transaction_type', 'credit')->sum('amount');
        $data['totalDebitAmount']         = WalletHistory::where('transaction_type', 'debit')->sum('amount');
        $data['totalPendingCreditAmount'] = WalletHistory::where('transaction_type', 'credit')
            ->where('payment_status', 'pending')
            ->sum('amount');

        $data['sellers'] = Vendor::with('user')->get();
        $data['title']   = __('Pending Wallet Payment');

        return view('wallet::admin.index', $data);
    }

    /**
     * @param $id
     */
    public function show($id)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $wallet_history = WalletHistory::findOrFail($id);

        return view('wallet::admin.show', ['wallet_history' => $wallet_history]);
    }

    /**
     * @param $id
     */
    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');
        $wallet_history = WalletHistory::findOrFail($id);
        $wallet_history->delete();

        $notification = __('Payment delete successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.wallet-history')->with($notification);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function rejected_wallet_request(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $request->validate([
            'subject'     => 'required',
            'description' => 'required',
        ], [
            'subject.required'     => __('Subject is required'),
            'description.required' => __('Description is required'),
        ]);

        $wallet_history                 = WalletHistory::findOrFail($id);
        $wallet_history->payment_status = PaymentStatus::REJECTED->value;
        $wallet_history->save();

        try {
            $user = User::findOrFail($wallet_history->user_id);

            //mail send
            $message = $request->description;
            $message = str_replace('[[name]]', $user->name, $message);
            $this->sendMail($user->email, $request->subject, $message);
        } catch (Exception $e) {
            logError("Error in sending wallet request rejection email: ", $e);
        }

        $notification = __('Wallet request rejected successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return back()->with($notification);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function approved_wallet_request($id)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        try {
            DB::beginTransaction();
            $wallet_history                 = WalletHistory::findOrFail($id);
            $wallet_history->payment_status = PaymentStatus::COMPLETED->value;
            $wallet_history->save();

            UserWalletController::updateWalletBalance($wallet_history->id, true);

            $notification = __('Wallet payment approval successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            logError("Error in approving wallet request: ", $e);

            notifyAdmin(
                'Wallet Approval Error',
                'An error occurred while approving wallet request ID: ' . $id . '. Error: ' . $e->getMessage(),
                'danger',
                link: route('admin.show-wallet-history', $id)
            );

            $notification = __('Something went wrong, please try again');
            $notification = ['message' => $notification, 'alert-type' => 'error'];
        }

        return back()->with($notification);

    }

    /**
     * @param $field
     */
    public function autoApproveStatus($field)
    {
        checkAdminHasPermissionAndThrowException('wallet.management');

        $currentValue = getSettings('wallet_amount_auto_approve') == 1 ? 0 : 1;

        Setting::where('key', 'wallet_amount_auto_approve')->update(['value' => $currentValue]);

        Cache::forget('setting');

        return response()->json([
            'status'  => true,
            'message' => __('Updated successfully'),
        ]);
    }
}
