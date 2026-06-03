<?php

namespace App\Http\Controllers\Seller;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\app\Services\AttributeService;
use Modules\Product\app\Services\ProductService;

class SellerProductVariantController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @param ProductService   $productService
     * @param AttributeService $attributeService
     */
    public function __construct(
        private ProductService $productService,
        private AttributeService $attributeService,
    ) {
    }

    /**
     * @param string $id
     */
    public function product_variant(string $id)
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

            $variants = $this->productService->getProductVariants($product);

            $variantImages = $this->productService->variantImage($product);

            return view('vendor::products.product_variant', compact('product', 'variants', 'variantImages'));
        } catch (\Exception $ex) {
            logError('Product Variant Error on Seller Profile', $ex);

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
            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }
            $attributes = $this->attributeService->getAllAttributesForSelect();

            return view('vendor::products.product_variant_create', compact('product', 'attributes'));
        } catch (\Exception $ex) {
            logError('Product Variant Create Error on Seller Profile', $ex);

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

            $product = $this->productService->getProduct($id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->storeProductVariant($request, $product);

            DB::commit();

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'seller.product.product-variant', [$product->id], [
                'message'    => __('Created successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError('Product Variant Create Error on Seller Profile', $ex);

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

            $attributes = $this->attributeService->getAllAttributesForSelect();

            $product = $this->productService->getProduct($variant->product_id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            return view('vendor::products.product_variant_edit', compact('variant', 'attributes', 'product'));
        } catch (\Exception $ex) {
            logError('Product Variant Edit Error on Seller Profile', $ex);

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
                    'message'    => 'Product Variant not found',
                    'alert-type' => 'error',
                ]);
            }

            $product = $this->productService->getProduct($variant->product_id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->updateProductVariant($request, $variant);

            DB::commit();

            return $this->redirectWithMessage(RedirectType::UPDATE->value, 'seller.product.product-variant', [$variant->product->id], [
                'message'    => __('Updated successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError('Product Variant Update Error on Seller Profile', $ex);

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
                    'message'    => 'Product Variant not found',
                    'alert-type' => 'error',
                ]);
            }

            $product = $this->productService->getProduct($variant->product_id, function ($query) {
                return $query->where('vendor_id', vendorId());
            });

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->deleteProductVariant($variant);

            DB::commit();

            return $this->redirectWithMessage(RedirectType::DELETE->value, 'seller.product.product-variant', [$variant->product->id], [
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError('Product Variant Delete Error on Seller Profile', $ex);

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

            $variant = $this->productService->getProductVariant($id);

            if (!$variant) {
                return back()->with([
                    'message'    => 'Product Variant not found',
                    'alert-type' => 'error',
                ]);
            }

            $product = $this->productService->getProduct($variant->product_id);

            if (!$product) {
                return back()->with([
                    'message'    => __('Product not found'),
                    'alert-type' => 'error',
                ]);
            }

            $this->productService->variantImageDelete($id);

            DB::commit();

            return back()->with([
                'message'    => __('Deleted successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            logError('Product Variant Delete Error on Seller Profile', $ex);

            DB::rollBack();

            return back()->with([
                'message'    => __('Something Went Wrong'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * @param Request $request
     */
    public function getAttributeValue(Request $request)
    {
        try {
            $values = $this->attributeService->getValues($request);
            if ($values) {
                return response()->json(['status' => true, 'data' => $values]);
            } else {
                return response()->json(['status' => false, 'message' => __('No values found')]);
            }
        } catch (\Exception $ex) {
            logError("Get Attribute Value Error on Seller", $ex);

            return response()->json(['status' => false, 'message' => __('Something went wrong')]);
        }
    }

}
