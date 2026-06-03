<?php

namespace Modules\Product\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Facades\MailSender;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Traits\RedirectHelperTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Language\app\Models\Language;
use Modules\Product\app\Http\Requests\ProductRequest;
use Modules\Product\app\Models\Gallery;
use Modules\Product\app\Models\Product;
use Modules\Product\app\Models\ProductTos;
use Modules\Product\app\Models\Tag;
use Modules\Product\app\Rules\DynamicProductInventoryFieldRule;
use Modules\Product\app\Rules\DynamicProductPriceFieldRule;
use Modules\Product\app\Services\AttributeService;
use Modules\Product\app\Services\BrandService;
use Modules\Product\app\Services\ProductCategoryService;
use Modules\Product\app\Services\ProductLabelService;
use Modules\Product\app\Services\ProductService;
use Modules\Product\app\Services\UnitTypeService;
use Modules\Tax\app\Models\Tax;

class ProductController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @param ProductService         $productService
     * @param ProductCategoryService $categoryService
     * @param AttributeService       $attributeService
     * @param BrandService           $brandService
     * @param UnitTypeService        $unitService
     * @param ProductLabelService    $labelService
     */
    public function __construct(
        private ProductService $productService,
        private ProductCategoryService $categoryService,
        private AttributeService $attributeService,
        private BrandService $brandService,
        private UnitTypeService $unitService,
        private ProductLabelService $labelService
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        checkAdminHasPermissionAndThrowException('product.view');

        try {
            $products = $this->productService->getProducts();

            $products->withCount([
                'reviews as pending_reviews_count' => function ($query) {
                    $query->where('status', 0);
                },
            ]);

            if (request('par-page')) {
                $products = $products->paginate(request('par-page'));
                $products->appends(request()->query());
            } else {
                $products = $products->paginate(20);
            }

            $brands = $this->brandService->getActiveBrands()->load('translation');

            $categories = $this->categoryService->getAllProductCategoriesForSelect()->load('translation');

            return view('product::products.index', compact('products', 'brands', 'categories'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        checkAdminHasPermissionAndThrowException('product.create');

        $categories = $this->categoryService->getCategoryWithChild();
        $brands     = $this->brandService->getActiveBrands();
        $vendors    = Vendor::get();
        $taxes      = Tax::get();
        $tags       = Tag::get();
        $labels     = $this->labelService->all();
        $sku        = $this->productService->generateSku();
        $barcode    = $this->productService->generateBarcode();
        $units      = $this->unitService->getAll();

        $productReturnPolicies = ProductTos::all();

        return view('product::products.create', compact('barcode', 'sku', 'categories', 'vendors', 'brands', 'taxes', 'tags', 'labels', 'units', 'productReturnPolicies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        checkAdminHasPermissionAndThrowException('product.create');

        try {
            DB::beginTransaction();

            $product = $this->productService->storeProduct($request);

            if (!$request->filled('return_policy_id') && $request->filled('return_policy_question')) {
                $productReturnPolicy           = new ProductTos;
                $productReturnPolicy->question = $request->return_policy_question;
                $productReturnPolicy->answer   = $request->return_policy_answer;
                $productReturnPolicy->status   = 1;
                $productReturnPolicy->save();

                $product->return_policy_id = $productReturnPolicy->id;
                $product->save();
            }

            DB::commit();
            if ($product->id) {
                return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.product.edit', [$product->id], [
                    'message'    => __('Created successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.product.create', [], [
                    'message'    => __('Product creation failed'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
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
        checkAdminHasPermissionAndThrowException('product.view');

        try {
            $product = $this->productService->getProduct($id, function ($query) {
                return $query->with([
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
                ->latest()
                ->take(8)
                ->get();

            return view('product::products.show', compact('product', 'orders'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * @param $id
     */
    public function singleProduct($id)
    {
        $product = $this->productService->getProduct($id);

        if (request()->ajax()) {
            if ($product) {
                return response()->json(['success' => true, 'product' => view('product::products.single-product-modal', compact('product'))->render()]);
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
        $product = Product::find($id);

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
        checkAdminHasPermissionAndThrowException('product.edit');
        try {
            $categories = $this->categoryService->getAllProductCategoriesForSelect()->load([
                'children' => ['translation'],
                'translation',
            ]);
            $brands  = $this->brandService->getActiveBrands()->load('translation');
            $vendors = Vendor::with('user:name,id')->get();
            $taxes   = Tax::with('translation')->get();
            $product = $this->productService->getProduct($id, function ($q) {
                return $q->with([
                    'categories' => [
                        'children' => ['translation'],
                        'translation',
                    ],
                    'translations',
                    'brand'      => [
                        'translation',
                    ],
                ]);
            });
            $tags                  = Tag::with('translation')->get();
            $labels                = $this->labelService->all();
            $sku                   = $this->productService->generateSku();
            $barcode               = $this->productService->generateBarcode();
            $units                 = $this->unitService->getAll();
            $productReturnPolicies = ProductTos::all() ?? collect([]);

            $code = request('code') ?? getSessionLanguage();

            if (!Language::where('code', $code)->exists()) {
                abort(404);
            }

            return view('product::products.edit', compact('productReturnPolicies', 'categories', 'brands', 'product', 'vendors', 'taxes', 'tags', 'labels', 'barcode', 'sku', 'units', 'code'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, string $id)
    {
        checkAdminHasPermissionAndThrowException('product.edit');

        $code = request('code') ?? getSessionLanguage();

        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        try {
            DB::beginTransaction();
            $product = $this->productService->getProduct($id);
            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }
            $product = $this->productService->updateProduct($request, $product);

            if (!$request->filled('return_policy_id') && $request->filled('return_policy_question')) {
                $productReturnPolicy           = new ProductTos;
                $productReturnPolicy->question = $request->return_policy_question;
                $productReturnPolicy->answer   = $request->return_policy_answer;
                $productReturnPolicy->status   = 1;
                $productReturnPolicy->save();

                $product->return_policy_id = $productReturnPolicy->id;
                $product->save();
            }

            DB::commit();
            if ($product->id) {
                return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.product.edit', ['product' => $product->id, 'code' => $code], [
                    'message'    => __('Updated successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.product.edit', ['product' => $product->id, 'code' => $code], [
                    'message'    => __('Product update failed'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
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
        checkAdminHasPermissionAndThrowException('product.delete');
        try {
            $product = $this->productService->getProduct($id);
            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }
            $product = $this->productService->deleteProduct($product);
            if ($product) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.product.index', [], [
                    'message'    => __('Deleted successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.product.index', [], [
                    'message'    => __('Product deletion failed. Product has orders'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param $id
     */
    public function productGallery($id)
    {
        try {
            $product = $this->productService->getProduct($id);

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            return view('product::products.gallery', compact('product'));
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
        checkAdminHasPermissionAndThrowException('product.edit');

        $request->validate([
            'file.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
        ]);

        DB::beginTransaction();

        try {

            foreach ($request->file as $image) {
                $productImage             = new Gallery;
                $productImage->product_id = $id;
                $productImage->path       = file_upload($image);
                $productImage->save();
            }

            DB::commit();

            return response()->json([
                'message'    => __('Updated successfully'),
                'alert-type' => 'success',
                'url'        => route('admin.product-gallery', ['id' => $id]),
            ]);
        } catch (\Exception $ex) {
            logError('Product Gallery Error', $ex);
            DB::rollBack();

            return response()->json([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param $id
     */
    public function productGalleryDelete($id)
    {
        checkAdminHasPermissionAndThrowException('product.delete');

        DB::beginTransaction();
        try {
            $productImage = Gallery::findOrFail($id);

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

    /**
     * @param string $id
     */
    public function product_variant(string $id)
    {
        try {
            $product = $this->productService->getProduct($id);
            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($product->is_flash_deal == 1) {
                return back()->with([
                    'message'    => __('Product is Flash Deal'),
                    'alert-type' => 'error',
                ]);
            }

            $variants      = $this->productService->getProductVariants($product);
            $variantImages = $this->productService->variantImage($product);

            return view('product::products.product_variant', compact('product', 'variants', 'variantImages'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function product_variant_create(string $id)
    {
        try {
            $product = $this->productService->getProduct($id);
            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($product->is_flash_deal == 1) {
                return back()->with([
                    'message'    => __('Product is Flash Deal'),
                    'alert-type' => 'error',
                ]);
            }

            $attributes = $this->attributeService->getAllAttributesForSelect();

            return view('product::products.product_variant_create', compact('product', 'attributes'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function product_variant_store(Request $request, string $id)
    {
        try {
            DB::beginTransaction();
            $product = $this->productService->getProduct($id);
            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($product->is_flash_deal == 1) {
                return back()->with([
                    'message'    => __('Product is Flash Deal'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->storeProductVariant($request, $product);
            DB::commit();

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.product.product-variant', [$product->id], [
                'message'    => __('Created successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param string $variant_id
     */
    public function product_variant_edit(string $variant_id)
    {
        try {
            $variant = $this->productService->getProductVariant($variant_id);

            if (!$variant) {
                return back()->with([
                    'message'    => __('Product Variant not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($variant->product->is_flash_deal == 1) {
                return back()->with([
                    'message'    => __('Product is Flash Deal'),
                    'alert-type' => 'error',
                ]);
            }

            $attributes = $this->attributeService->getAllAttributesForSelect();
            $product    = $variant->product;

            return view('product::products.product_variant_edit', compact('variant', 'attributes', 'product'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function product_variant_update(Request $request, string $variant_id)
    {
        try {
            DB::beginTransaction();
            $variant = $this->productService->getProductVariant($variant_id);
            if (!$variant) {
                return back()->with([
                    'message'    => __('Product Variant not found'),
                    'alert-type' => 'error',
                ]);
            }

            if ($variant->product->is_flash_deal == 1) {
                return back()->with([
                    'message'    => __('Product is Flash Deal'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->updateProductVariant($request, $variant);
            DB::commit();

            return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.product.product-variant', [$variant->product->id], [
                'message'    => __('Updated successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @return mixed
     */
    public function product_variant_delete(string $variant_id)
    {
        try {
            DB::beginTransaction();
            $variant = $this->productService->getProductVariant($variant_id);
            if (!$variant) {
                return back()->with([
                    'message'    => __('Product Variant not found'),
                    'alert-type' => 'error',
                ]);
            }
            $this->productService->deleteProductVariant($variant);
            DB::commit();

            return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.product.product-variant', [$variant->product->id], [
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param $id
     */
    public function product_variant_image_delete($id)
    {
        try {
            DB::beginTransaction();

            $this->productService->variantImageDelete($id);
            DB::commit();

            return back()->with([
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    // search  product
    public function search()
    {
        $product = $this->productService->getProducts()->first();
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
        $product = $this->productService->getProducts()->get();
        if (!$product->count()) {
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
     * @param $id
     */
    public function status($id)
    {
        checkAdminHasPermissionAndThrowException('product.status');
        $product = $this->productService->getProduct($id);
        $status  = $product->status == 1 ? 0 : 1;

        $product->status = $status;
        $product->save();

        $notification = $status == 1 ? __('Product Enabled') : __('Product Disabled');

        return response()->json(['status' => 'success', 'message' => $notification]);
    }

    /**
     * @param $id
     */
    public function approveProduct($id)
    {
        checkAdminHasPermissionAndThrowException('product.status');

        $product = $this->productService->getProduct($id);

        $oldIsApproved = $product->is_approved;

        $status               = $product->is_approved == 1 ? 0 : 1;
        $product->is_approved = $status;
        $product->save();

        if ($oldIsApproved == 0 && $product->is_approved == 1 && $product->vendor) {
            try {
                $email = $product->vendor->email;

                [$subject, $message] = MailSender::fetchEmailTemplate('product_approved', [
                    'shop_name'    => $product->vendor->shop_name ?? "Shop Name Missing!",
                    'product_name' => $product->name ?? "Product Name Missing!",
                ]);

                $link = [
                    "Product Details" => route('website.product', ['product' => $product->slug]),
                    "View Product"    => route('seller.product.show', ['product' => $product->id]),
                ];

                MailSender::sendMail($email, $subject, $message, $link);
            } catch (Exception $e) {
                logError("Product Approval Mail Sending Error", $e);
            }
        }

        $notification = $status == 1 ? __('Product Approved') : __('Product Disapproved');

        return response()->json(['status' => 'success', 'message' => __($notification)]);
    }

    /**
     * @param Request $request
     */
    public function bulkDelete(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.bulk.delete');
        $ids = $request->ids;
        $this->productService->bulkDelete($ids);

        return response()->json(['status' => 'success', 'message' => 'Deleted successfully']);
    }

    public function priceUpdate()
    {
        checkAdminHasPermissionAndThrowException('product.view');

        try {
            $products = $this->productService->getProducts();

            $products->where('is_flash_deal', 0);

            if (request('par-page')) {
                $products = $products->paginate(request('par-page'));
                $products->appends(request()->query());
            } else {
                $products = $products->paginate(5);
            }

            $brands = $this->brandService->getActiveBrands();

            $categories = $this->categoryService->getAllProductCategoriesForSelect();

            return view('product::products.prices.price-update', compact('products', 'brands', 'categories'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * @param Request $request
     */
    public function priceUpdateStore(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.edit');

        $validationRules = [
            'field'              => 'required|in:price,offer_price,offer_price_end,offer_price_start,offer_price_type',
            'value'              => [
                'required_if:field,price',
                new DynamicProductPriceFieldRule($request->input('field')),
            ],
            'product_id'         => 'required|exists:products,id',
            'product_variant_id' => 'sometimes|exists:variants,id',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => __('Validation Error'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $field = $request->get('field');

        $reload = false;

        if ($request->filled('product_variant_id')) {
            $variant = $this->productService->getProductVariant($request->product_variant_id);

            if (!$variant) {
                return response()->json([
                    'status'  => false,
                    'message' => __('Product Variant not found'),
                ]);
            }

            $offerTypeOld  = $variant->offer_price_type;
            $oldOfferPrice = $variant->offer_price;

            if ($field == 'offer_price' && $offerTypeOld == 'percentage' && $request->get('value') > 100) {
                return response()->json([
                    'status'  => false,
                    'reload'  => true,
                    'message' => __('Offer price cannot be more than 100% when offer price type is percentage'),
                ]);
            }

            $variant->$field = $request->value;

            if ($field == 'offer_price_type' && $offerTypeOld == 'fixed' && $oldOfferPrice > 100 && $request->get('value') == 'percentage') {
                $variant->offer_price = 0;
                $reload               = true;
            }

            $variant->save();
        } else {
            $product = $this->productService->getProduct($request->product_id);
            if (!$product) {
                return response()->json([
                    'status'  => false,
                    'message' => __('Product not found'),
                ]);
            }

            $offerTypeOld  = $product->offer_price_type;
            $oldOfferPrice = $product->offer_price;

            if ($field == 'offer_price' && $offerTypeOld == 'percentage' && $request->get('value') > 100) {
                return response()->json([
                    'status'  => false,
                    'reload'  => true,
                    'message' => __('Offer price cannot be more than 100% when offer price type is percentage'),
                ]);
            }

            $product->$field = $request->value;

            if ($field == 'offer_price_type' && $offerTypeOld == 'fixed' && $oldOfferPrice > 100 && $request->get('value') == 'percentage') {
                $product->offer_price = 0;
                $reload               = true;
            }
            $product->save();
        }

        return response()->json([
            'status'  => true,
            'reload'  => $reload,
            'message' => __(':field updated successfully', ['field' => str($field)->replace('_', ' ')->title()]),
        ]);
    }

    public function productInventory()
    {
        checkAdminHasPermissionAndThrowException('product.view');

        try {
            $products = $this->productService->getProducts();

            $products->where('is_flash_deal', 0);

            if (request('par-page')) {
                $products = $products->paginate(request('par-page'));
                $products->appends(request()->query());
            } else {
                $products = $products->paginate(5);
            }

            $brands = $this->brandService->getActiveBrands();

            $categories = $this->categoryService->getAllProductCategoriesForSelect();

            return view('product::products.inventory.index', compact('products', 'brands', 'categories'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * @param Request $request
     */
    public function productInventoryStore(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.edit');

        $validationRules = [
            'product_id'         => 'required|exists:products,id',
            'product_variant_id' => 'sometimes|exists:variants,id',
            'sku'                => 'required|string',
            'value'              => [
                'required',
                new DynamicProductInventoryFieldRule($request->input('field')),
            ],
            'field'              => 'required|in:manage_stock,stock_status,stock_qty',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => __('Validation Error'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $field = $request->get('field');

        if ($request->filled('product_variant_id') && $request->input('field') == 'stock_qty') {
            $variant = $this->productService->getProductVariant($request->product_variant_id);

            $variantStock = $variant->manageStocks()->where('sku', $request->sku)->first();

            if (!$variant) {
                return response()->json([
                    'status'  => false,
                    'message' => __('Product Variant not found'),
                ]);
            }

            $variantStock->update(['quantity' => $request->value]);
        }

        if (!$request->filled('product_variant_id')) {
            $product = $this->productService->getProduct($request->product_id);
            if (!$product) {
                return response()->json([
                    'status'  => false,
                    'message' => __('Product not found'),
                ]);
            }

            if ($field !== 'stock_qty') {
                $product->$field = $request->value;
                $product->save();
            }

            if ($field == 'stock_qty') {
                $product->manageStocks()->update(['quantity' => $request->value]);
            }
        }

        $response = [
            'status'     => true,
            'field'      => $field,
            'product_id' => $request->product_id,
            'message'    => __(':field updated successfully', ['field' => str($request->input('field'))->replace('_', ' ')->title()]),
        ];

        if ($request->filled('product_variant_id')) {
            $response['variant_id'] = $request->product_variant_id;
        }

        return response()->json($response);
    }

    public function productReturnPolicy()
    {
        checkAdminHasPermissionAndThrowException('product.view');

        try {
            $returnPolicies = ProductTos::latest()->paginate();

            return view('product::products.return-policy.index', compact('returnPolicies'));
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            abort(500);
        }
    }

    /**
     * @param $id
     */
    public function productReturnPolicyDelete($id)
    {
        checkAdminHasPermissionAndThrowException('product.delete');

        try {
            $returnPolicies = ProductTos::findOrFail($id);

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
            Log::error($ex->getMessage());
            abort(500);
        }
    }
}
