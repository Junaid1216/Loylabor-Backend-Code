<?php

namespace Modules\Product\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Language\app\Models\Language;
use Modules\Product\app\Http\Requests\ProductCategoryRequest;
use Modules\Product\app\Models\Category;
use Modules\Product\app\Models\ProductCategory;
use Modules\Product\app\Services\ProductCategoryService;

class ProductCategoryController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @var mixed
     */
    protected $category;

    /**
     * @param ProductCategoryService $category
     */
    public function __construct(ProductCategoryService $category)
    {
        $this->category = $category;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        checkAdminHasPermissionAndThrowException('product.category.view');

        $categories = $this->category->getAllProductCategories(onlyParents: true, callback: function ($query) {
            $query->with(['children' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }]);
        });


        $parentCategories = $this->category->getParentCategoriesOnly();

        return view('product::products.category.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        checkAdminHasPermissionAndThrowException('product.category.create');

        $categories = $this->category->getParentCategoriesOnly();

        return view('product::products.category.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCategoryRequest $request)
    {
        checkAdminHasPermissionAndThrowException('product.category.create');
        DB::beginTransaction();
        try {
            $category = $this->category->storeProductCategory($request);
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'Created successfully', 'categories' => $category, 'status' => 200], 200);
            }

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.category.edit', ['category' => $category->id, 'code' => getSessionLanguage()]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return $this->redirectWithMessage(RedirectType::ERROR->value, 'admin.category.index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        checkAdminHasPermissionAndThrowException('product.category.edit');

        $code = request('code') ?? getSessionLanguage();

        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        $cat        = $this->category->getProductCategory($id);

        $productCategory = Category::whereSlug($cat->slug)->where('id', '!=', $id)->count();

        if ($productCategory > 0) {
            $cat->slug = $cat->slug . '-' . $productCategory;
            $cat->save();
        }

        $categories = $this->category->getParentCategoriesOnly($id);

        return view('product::products.category.edit', compact('categories', 'cat', 'code'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductCategoryRequest $request, string $id)
    {
        checkAdminHasPermissionAndThrowException('product.category.edit');

        DB::beginTransaction();

        $code = request('code') ?? getSessionLanguage();

        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        try {
            $this->category->updateProductCategory($request, $id);
            DB::commit();

            return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.category.edit', ['category' => $id, 'code' => $code], [
                'message'    => __('Updated successfully'),
                'alert-type' => 'success',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            DB::rollBack();

            return $this->redirectWithMessage(RedirectType::ERROR->value, 'admin.category.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        checkAdminHasPermissionAndThrowException('product.category.delete');
        try {
            $cat = $this->category->getProductCategory($id);

            if ($cat->parent_id && $cat?->parent && $cat?->parent?->products?->count() > 0) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.category.index', [], [
                    'message'    => __('Category delete failed, Parent category associted with products!'),
                    'alert-type' => 'error',
                ]);
            }

            if ($cat && $cat?->products?->count() > 0) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.category.index', [], [
                    'message'    => __('Category delete failed, Associted with products!'),
                    'alert-type' => 'error',
                ]);
            }

            $category = $this->category->deleteProductCategory($id);
            if (!$category) {
                return $this->redirectWithMessage(RedirectType::ERROR->value, 'admin.category.index', [], [
                    'message'    => 'Category has products',
                    'alert-type' => 'error',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.category.index')->with([
                    'message'    => 'Deleted successfully',
                    'alert-type' => 'success',
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return $this->redirectWithMessage(RedirectType::ERROR->value, 'admin.category.index');
        }
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function status(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('product.category.update');
        try {
            $this->category->statusUpdate($request, $id);

            return response()->json(['success' => true, 'message' => 'Updated successfully'], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * @param Request $request
     */
    public function deleteAll(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.category.delete');
        try {

            foreach ($this->category->getAll() as $cat) {
                if ($cat->parent_id && $cat?->parent && $cat?->parent?->products?->count() > 0) {
                    return response()->json(['message' => __('Category delete failed, parent category associted with products!')], 200);
                }

                if ($cat && $cat?->products?->count() > 0) {
                    return response()->json(['message' => __('Category delete failed, Associted with products!')], 200);
                }
            }

            $this->category->deleteAll($request);

            return response()->json(['success' => true, 'message' => 'Deleted successfully'], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
