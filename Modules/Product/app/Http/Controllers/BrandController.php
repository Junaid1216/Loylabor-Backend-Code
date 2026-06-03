<?php

namespace Modules\Product\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Language\app\Models\Language;
use Modules\Product\app\Http\Requests\BrandRequest;
use Modules\Product\app\Services\BrandService;

class BrandController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @var mixed
     */
    protected $brandService;

    /**
     * @param BrandService $brandService
     */
    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        checkAdminHasPermissionAndThrowException('product.brand.view');
        $brands = $this->brandService->getPaginateBrands();

        $parpage = request('par-page', 20);

        $brands = $brands->paginate($parpage);
        $brands->appends(request()->query());

        return view('product::products.brand.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        checkAdminHasPermissionAndThrowException('product.brand.create');
        try {
            return view('product::products.brand.create');
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return back()->with(['message' => __('Something Went Wrong'), 'alert-type' => 'error']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrandRequest $request)
    {
        checkAdminHasPermissionAndThrowException('product.brand.create');

        try {
            $brand = $this->brandService->store($request);

            if ($brand->id) {
                if ($request->ajax()) {
                    return response()->json(['message' => __('Created successfully'), 'status' => 200, 'brand' => $brand], 200);
                }

                return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.brand.edit', ['brand' => $brand->id, 'code' => getSessionLanguage()], [
                    'message'    => __('Created successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.brand.create', [], [
                    'message'    => 'Brand creation failed',
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            logError("Brand Create Failed: ", $ex);

            return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.brand.create', [], [
                'message'    => 'Brand creation failed',
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        checkAdminHasPermissionAndThrowException('product.brand.edit');

        $brand = $this->brandService->find($id);

        $code = request('code') ?? getSessionLanguage();

        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        try {
            return view('product::products.brand.edit', compact('brand', 'code'));
        } catch (\Exception $ex) {
            logError("Brand edit view: ", $ex);

            return back()->with(['message' => __('Something Went Wrong'), 'alert-type' => 'error']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrandRequest $request, string $id)
    {
        checkAdminHasPermissionAndThrowException('product.brand.edit');

        $code = request('code') ?? getSessionLanguage();

        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        try {
            $brand = $this->brandService->update($request, $id);

            if ($brand) {
                return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.brand.edit', [
                    'brand' => $brand->id,
                    'code'  => $code,
                ], [
                    'message'    => __('Updated successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.brand.index', [], [
                    'message'    => __('Brand update failed'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            logError("Brand update Failed: ", $ex);

            return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.brand.index', [], [
                'message'    => __('Brand update failed'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        checkAdminHasPermissionAndThrowException('product.brand.delete');
        try {
            $brand = $this->brandService->find($id);

            if ($brand && $brand?->products?->count() > 0) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.brand.index', [], [
                    'message'    => __('Brand delete failed, Associted with products!'),
                    'alert-type' => 'error',
                ]);
            }

            $brand = $this->brandService->delete($id);

            if ($brand) {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.brand.index', [], [
                    'message'    => __('Deleted successfully'),
                    'alert-type' => 'success',
                ]);
            } else {
                return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.brand.index', [], [
                    'message'    => __('Brand delete failed'),
                    'alert-type' => 'error',
                ]);
            }
        } catch (\Exception $ex) {
            logError("Brand delete Failed: ", $ex);

            return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.brand.index', [], [
                'message'    => __('Brand delete failed'),
                'alert-type' => 'error',
            ]);
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
            $this->brandService->statusUpdate($request, $id);

            return response()->json(['success' => true, 'message' => 'Updated successfully'], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());

            return response()->json(['message' => __('Something went wrong')], 500);
        }
    }

    /**
     * @param Request $request
     */
    public function deleteAll(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.brand.delete');
        try {
            $brand = $this->brandService->deleteAll($request);

            if ($brand) {
                return response()->json(['success' => true, 'message' => __('Deleted successfully')], 200);
            } else {
                return response()->json(['success' => false, 'message' => __('Delete failed')], 400);
            }
        } catch (\Exception $ex) {
            logError("Brand delete All Failed: ", $ex);

            return response()->json(['success' => false, 'message' => __('Delete failed')], 400);
        }
    }
}
