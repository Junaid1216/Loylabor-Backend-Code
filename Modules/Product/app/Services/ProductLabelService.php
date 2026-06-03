<?php

namespace Modules\Product\app\Services;

use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Product\app\Models\ProductLabel;

class ProductLabelService
{
    use GenerateTranslationTrait;

    public function __construct(protected ProductLabel $productLabel)
    {
        $this->productLabel = $productLabel;
    }

    /**
     * @return mixed
     */
    public function all()
    {
        return $this->productLabel
            ->with('translation')
            ->when(request()->filled('keyword'), function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('translation', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.request()->get('keyword').'%');
                    })->orWhere('slug', 'like', '%'.request()->get('keyword').'%');
                });
            })
            ->when(request()->filled('order_by'), function ($query) {
                $query->orderBy('slug', request()->get('order_by'));
            }, function ($query) {
                $query->orderBy('slug', 'asc');
            })
            ->get();
    }

    /**
     * @return mixed
     */
    public function getPaginateBrands($page = 20)
    {
        $productLabel = $this->productLabel->with('translation');

        $productLabel->when(request()->filled('keyword'), function ($query) {
            $query->where(function ($q) {
                $q->whereHas('translation', function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.request()->get('keyword').'%');
                })->orWhere('slug', 'like', '%'.request()->get('keyword').'%');
            });
        });

        $productLabel->when(request()->filled('order_by'), function ($query) {
            $query->orderBy('slug', request()->get('order_by'));
        }, function ($query) {
            $query->orderBy('slug', 'asc');
        });

        return $productLabel->paginate($page);
    }

    /**
     * @return mixed
     */
    public function find(int $id)
    {
        return $this->productLabel->whereId($id)->with('translation')->first();
    }

    public function store($data)
    {
        $productLabel = $this->productLabel->create($data->only('slug', 'status'));

        $this->generateTranslations(
            TranslationModels::PRODUCT_LABEL,
            $productLabel,
            'product_label_id',
            $data,
        );

        return $productLabel;
    }

    public function update($data, int $id)
    {
        $productLabel = $this->productLabel->find($id);

        if ($data->filled('status') || $data->filled('slug')) {
            $productLabel->update($data->only('slug', 'status'));
        }

        $this->updateTranslations(
            $productLabel,
            $data,
            $data->validated(),
        );

        return $productLabel;
    }

    /**
     * @param  int  $id
     */
    public function delete($id)
    {
        $productLabel = $this->productLabel->find($id);
        if ($productLabel) {
            $productLabel->delete();

            return ['message' => 'Deleted successfully', 'status' => true];
        } else {
            return ['message' => 'Product label not found', 'status' => false];
        }
    }

    /**
     * @param  $data
     */
    public function updateStatus($id): array
    {
        $productLabel = $this->productLabel->find($id);
        $productLabel->status = $productLabel->status == 1 ? 0 : 1;
        $productLabel->save();

        return ['message' => 'Updated successfully', 'status' => true];
    }
}
