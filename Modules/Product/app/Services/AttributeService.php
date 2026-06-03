<?php

namespace Modules\Product\app\Services;

use Illuminate\Http\Request;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Product\app\Models\Attribute;
use Modules\Product\app\Models\AttributeValue;

class AttributeService
{
    use GenerateTranslationTrait;

    /**
     * @var mixed
     */
    private $attribute;

    /**
     * @var mixed
     */
    private $attributeValue;

    /**
     * @var mixed
     */
    private $product;

    /**
     * @param Attribute      $attribute
     * @param AttributeValue $attributeValue
     * @param ProductService $product
     */
    public function __construct(Attribute $attribute, AttributeValue $attributeValue, ProductService $product)
    {
        $this->attribute      = $attribute;
        $this->attributeValue = $attributeValue;
        $this->product        = $product;
    }

    // get all attributes

    /**
     * @return mixed
     */
    public function getAllAttributes()
    {
        $att = $this->attribute->with('values')->paginate(20);
        $att->appends(request()->query());

        return $att;
    }

    // get all attributes for select

    /**
     * @return mixed
     */
    public function getAllAttributesForSelect()
    {
        return $this->attribute->with('values')->where('status', 1)->get();
    }

    // store attribute and attribute values

    /**
     * @return mixed
     */
    public function storeAttribute($request)
    {
        $attribute = $this->attribute->create([
            'slug' => $request->slug,
        ]);

        $this->generateTranslations(
            TranslationModels::Attribute,
            $attribute,
            'attribute_id',
            $request,
        );

        return $attribute;
    }

    /**
     * @return mixed
     */
    public function getById($id)
    {
        return $this->attribute->with('values')->find($id);
    }

    /**
     * @param Request      $request
     * @param $attribute
     */
    public function updateAttribute(Request $request, $attribute)
    {
        if ($request->filled('slug')) {
            $attribute->update([
                'slug' => $request->slug,
            ]);
        }

        $this->updateTranslations(
            $attribute,
            $request,
            $request->all(),
        );
    }

    /**
     * @param $id
     */
    public function deleteAttribute($id)
    {
        $attribute = $this->attribute->find($id);
        if ($attribute) {
            if ($attribute->values) {
                foreach ($attribute->values as $value) {
                    $value->delete();
                }
                $attribute->delete();
            }

            return ['message' => 'Deleted successfully', 'status' => true];
        } else {
            return ['message' => 'Attribute not found', 'status' => false];
        }
    }

    /**
     * @param $data
     */
    public function deleteValue($data)
    {
        $value = $this->attributeValue->where('id', $data['id'])->where('attribute_id', $data['attribute_id'])->first();
        if ($value) {
            $value->delete();
        }
    }

    /**
     * @return mixed
     */
    public function getValues($request)
    {
        return $this->attribute->whereIn('id', $request->attribute)->with('values')->get();
    }
}
