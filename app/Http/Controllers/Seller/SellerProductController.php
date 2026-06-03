<?php

namespace App\Http\Controllers\Seller;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\app\Http\Requests\ProductRequest;
use Modules\Product\app\Models\ProductTos;
use Modules\Product\app\Models\Tag;
use Modules\Product\app\Services\BrandService;
use Modules\Product\app\Services\ProductCategoryService;
use Modules\Product\app\Services\ProductLabelService;
use Modules\Product\app\Services\ProductService;
use Modules\Product\app\Services\UnitTypeService;
use Modules\Tax\app\Models\Tax;

class SellerProductController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @param ProductService         $productService
     * @param ProductCategoryService $categoryService
     * @param BrandService           $brandService
     * @param UnitTypeService        $unitService
     * @param ProductLabelService    $labelService
     */
    public function __construct(
        private ProductService $productService,
        private ProductCategoryService $categoryService,
        private BrandService $brandService,
        private UnitTypeService $unitService,
        private ProductLabelService $labelService
    ) {
    }

    public function index()
    {
        try {
            $products = $this->productService->getProducts();

            $products->where('vendor_id', vendorId());

            $products = request()->filled('par-page') ? $products->paginate(request('par-page'))->withQueryString() : $products->paginate(20);

            $brands = $this->brandService->getActiveBrands()->load('translation');

            $categories = $this->categoryService->getAllProductCategoriesForSelect()->load('translation');

            return view('vendor::products.index', compact('products', 'brands', 'categories'));
        } catch (\Exception $ex) {
            logError('Product Index Error in seller panel', $ex);

            return to_route('seller.dashboard')->with([
                'alert-type' => 'error',
                'message'    => __('Something went wrong in the code! Please try again later or contact support team'),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->categoryService->getCategoryWithChild();
        $brands     = $this->brandService->getActiveBrands();
        $taxes      = Tax::where('status', 1)->get();
        $tags       = Tag::get();
        $labels     = $this->labelService->all();
        $sku        = $this->productService->generateSku();
        $barcode    = $this->productService->generateBarcode();
        $units      = $this->unitService->getAll();

        $productReturnPolicies = ProductTos::get();

        return view('vendor::products.create', compact('barcode', 'sku', 'categories', 'brands', 'taxes', 'tags', 'labels', 'units', 'productReturnPolicies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): \Illuminate\Http\RedirectResponse
    {
        try {
            DB::beginTransaction();

            $product = $this->productService->storeProduct($request);

            $product->vendor_id = vendorId();

            $product->is_approved = 0;

            $product->save();

            $this->createReturnPolicy($request, $product);

            DB::commit();

            if ($product) {
                $shopName = auth('web')->user()->seller->shop_name ?? 'Unknown Vendor';

                notifyAdmin('New product created by vendor', 'A new product has been created by vendor: ' . $shopName, 'success', route('admin.product.edit', ['product' => $product->id]));
            }

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'seller.product.edit', [$product->id], [
                'message'    => __('Created successfully'),
                'alert-type' => 'success',
            ]);

        } catch (\Exception $ex) {
            logError("Product Store Error in seller panel", $ex);

            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId())->with([
                    'reviews' => function ($q) {
                        $q->with('user', 'order')->latest()->take(8);
                    },
                ]);
            });

            if (!$product) {
                return to_route('seller.product.index')->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $orders = $product
                ->orders()
                ->where('orders.vendor_id', vendorId())
                ->latest()
                ->take(8)
                ->get();

            return view('vendor::products.show', compact('product', 'orders'));
        } catch (\Exception $ex) {
            logError("Product Show Error in seller panel", $ex);

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param $id
     */
    public function singleProduct($id)
    {
        $product = $this->productService->getProduct($id, function ($query) {
            return $query->where('vendor_id', vendorId());
        });

        if (request()->ajax()) {
            if ($product) {
                return response()->json(['success' => true, 'product' => view('vendor::products.single-product-modal', compact('product'))->render()]);
            } else {
                return response()->json(['success' => false, 'message' => __('Product not found')]);
            }
        }
    }

    /**
     * @param $id
     */
    public function getProductJson($id)
    {
        $product = $this->productService->getProduct($id, function ($query) {
            return $query->where('vendor_id', vendorId());
        });

        $product->append('name');

        if (request()->ajax()) {
            if ($product) {
                try {
                    return response()->json(['success' => true, 'product' => $product->toArray()]);
                } catch (Exception $e) {
                    return response()->json([
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ], 500);
                }
            } else {
                return response()->json(['success' => false, 'message' => __('Product not found')]);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $categories = $this->categoryService->getAllProductCategoriesForSelect();
            $brands     = $this->brandService->getActiveBrands();
            $taxes      = Tax::where('status', 1)->get();
            $product    = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });
            $tags                  = Tag::get();
            $labels                = $this->labelService->all();
            $sku                   = $this->productService->generateSku();
            $barcode               = $this->productService->generateBarcode();
            $units                 = $this->unitService->getAll();
            $productReturnPolicies = ProductTos::get();

            return view('vendor::products.edit', compact('productReturnPolicies', 'categories', 'brands', 'product', 'taxes', 'tags', 'labels', 'barcode', 'sku', 'units'));
        } catch (\Exception $ex) {
            logError("Product Edit Error in seller panel", $ex);
            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $product = $this->productService->updateProduct($request, $product);

            $this->createReturnPolicy($request, $product);

            DB::commit();

            return $this->redirectWithMessage(RedirectType::UPDATE->value, 'seller.product.index', [], [
                'message'    => __('Updated successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError("Product Update Error in seller panel", $ex);

            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
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

            $product = $this->productService->deleteProduct($product);

            if ($product) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'seller.product.index', [], [
                    'message'    => __('Deleted successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'seller.product.index', [], [
                    'message'    => __('Product deletion failed. Product has orders'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            logError("Product Delete Error in seller panel", $ex);

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    // search  product
    public function search()
    {
        $product = $this->productService->getProducts()->where('vendor_id', vendorId())->first();

        if (!$product) {
            return response()->json([
                'status'  => false,
                'message' => __('Product not found'),
            ]);
        } else {
            return response()->json([
                'status' => true,
                'data'   => $product,
            ]);
        }
    }

    /**
     * @param Request $request
     */
    public function searchProducts(Request $request)
    {
        $product = $this->productService->getProducts()->where('vendor_id', vendorId())->get();

        if (!$product->count()) {
            return response()->json([
                'status'  => false,
                'message' => __('Products not found'),
            ]);
        } else {
            return response()->json([
                'status' => true,
                'data'   => $product,
            ]);
        }
    }

    /**
     * @param $id
     */
    public function status($id)
    {
        $product = $this->productService->getProduct($id, function ($query) {
            return $query->where('vendor_id', vendorId());
        });

        if (!$product) {
            return response()->json([
                'status'  => false,
                'message' => __('Product not found'),
            ]);
        }

        $status = $product->status == 1 ? 0 : 1;

        $product->status = $status;
        $product->save();

        $notification = $status == 1 ? __('Product Enabled') : __('Product Disabled');

        return response()->json(['status' => 'success', 'message' => $notification]);
    }
    /**
     * @param  ProductRequest $request
     * @param  mixed          $product
     * @return void
     */
    private function createReturnPolicy(ProductRequest $request, mixed $product): void
    {
        if (!$request->filled('return_policy_id') && $request->filled('return_policy_question')) {
            $productReturnPolicy            = new ProductTos;
            $productReturnPolicy->vendor_id = vendorId();
            $productReturnPolicy->question  = $request->return_policy_question;
            $productReturnPolicy->answer    = $request->return_policy_answer;
            $productReturnPolicy->status    = 1;
            $productReturnPolicy->save();

            $product->return_policy_id = $productReturnPolicy->id;
            $product->save();
        }
    }
}
