<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Modules\Product\app\Models\ProductTos;

class SellerProductTosController extends Controller
{
    use RedirectHelperTrait;

    public function productReturnPolicy()
    {
        try {
            $returnPolicies = ProductTos::where('vendor_id', vendorId())->latest()->paginate(20);

            return view('vendor::products.return-policy.index', compact('returnPolicies'));
        } catch (\Exception $ex) {
            logError("Product Return Policy Error", $ex);

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param $id
     */
    public function productReturnPolicyDelete($id)
    {
        try {
            $returnPolicies = ProductTos::where('vendor_id', vendorId())->findOrFail($id);

            if ($returnPolicies->products()->count() > 0) {
                return back()->with([
                    'message'    => __('Return policy deletion failed. Return policy has products'),
                    'alert-type' => 'error',
                ]);
            }

            $returnPolicies->delete();

            return back()->with([
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError("Product Return Policy Delete Error", $ex);

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

}
