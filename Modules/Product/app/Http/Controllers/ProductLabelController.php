<?php

namespace Modules\Product\app\Http\Controllers;

use App\Enums\RedirectMessage;
use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\app\Http\Requests\ProductLabelRequest;
use Modules\Product\app\Services\ProductLabelService;

class ProductLabelController extends Controller
{
    use RedirectHelperTrait;

    /**
     * @param ProductLabelService $productLabelService
     */
    public function __construct(
        private ProductLabelService $productLabelService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('product.label.view');

        $productLabels = $this->productLabelService->getPaginateBrands($request->get('par-page', 20));

        return view('product::products.label.index', compact('productLabels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductLabelRequest $request)
    {
        checkAdminHasPermissionAndThrowException('product.label.create');

        $this->productLabelService->store($request);

        return $this->redirectWithMessage(RedirectMessage::CREATE->value, notification: [
            'message'    => __('Created successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        checkAdminHasPermissionAndThrowException('product.label.edit');

        $productLabel = $this->productLabelService->find($id);

        return view('product::products.label.edit', [
            'label' => $productLabel,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductLabelRequest $request, $id): RedirectResponse
    {
        $this->productLabelService->update($request, $id);

        return $this->redirectWithMessage(RedirectMessage::UPDATE->value, notification: [
            'message'    => __('Updated successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        checkAdminHasPermissionAndThrowException('product.label.delete');

        $cat = $this->productLabelService->find($id);

        if ($cat && $cat?->products?->count() > 0) {
            return $this->redirectWithMessage(RedirectType::DELETE->value, notification: [
                'message'    => __('Label delete failed, Associted with products!'),
                'alert-type' => 'error',
            ]);
        }

        $this->productLabelService->delete($id);

        return $this->redirectWithMessage(RedirectMessage::DELETE->value, notification: [
            'message'    => __('Deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function updateStatus(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('product.label.update');

        try {
            $this->productLabelService->updateStatus($id);

            return response()->json(['success' => true, 'message' => 'Updated successfully'], 200);
        } catch (\Exception $ex) {
            logError('Unable to update status', $ex);

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
