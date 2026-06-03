<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\app\Models\Gallery;
use Modules\Product\app\Services\ProductService;

class SellerProductGalleryController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @param ProductService $productService
     */
    public function __construct(
        private ProductService $productService,
    ) {
    }

    /**
     * @param $id
     */
    public function productGallery($id)
    {
        try {
            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            return view('vendor::products.gallery', compact('product'));
        } catch (\Exception $ex) {
            logError('Product Gallery Error', $ex);

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function productGalleryStore(Request $request, $id)
    {
        $request->validate([
            'file.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
        ]);

        try {
            DB::beginTransaction();

            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            foreach ($request->file as $image) {
                $productImage             = new Gallery();
                $productImage->product_id = $product->id;
                $productImage->path       = file_upload($image);
                $productImage->save();
            }

            DB::commit();

            return response()->json(
                [
                    'url'     => route('seller.product-gallery', $product->id),
                    'message' => __('Product image uploaded successfully'),
                ]
            );
        } catch (\Exception $ex) {
            logError('Product Gallery Error', $ex);

            DB::rollBack();

            return response()->json(
                [
                    'message' => __('Something Went Wrong!'),
                    'status'  => false,
                    'errors'  => $ex->getMessage(),
                    'trace'   => $ex->getTraceAsString(),
                ], 500
            );
        }
    }

    /**
     * @param $id
     */
    public function productGalleryDelete($id)
    {
        try {
            DB::beginTransaction();

            $productImage = Gallery::findOrFail($id);

            $product = $this->productService->getProduct($productImage->product_id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($productImage->path) {
                unlink(public_path($productImage->path));
            }

            $productImage->delete();

            DB::commit();

            return back()->with([
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError('Product Gallery Error', $ex);
            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong!'),
                'alert-type' => 'error',
            ]);
        }
    }
}
