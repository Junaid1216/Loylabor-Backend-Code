<?php

namespace Modules\Product\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Product\app\Models\Tag;

class TagController extends Controller
{
    use GenerateTranslationTrait, RedirectHelperTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = Tag::query()
            ->when(request()->filled('keyword'), function ($query) {
                $search = request('keyword');
                $query->whereHas('translation', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            })
            ->when(request()->filled('order_by'), function ($query) {
                $orderBy = request('order_by');
                $query->orderBy('id', $orderBy);
            }, function ($query) {
                $query->latest();
            });

        $perPage = request('par-page', 20);

        $tags = $tags->paginate($perPage ?: 20)->appends(request()->query());

        return view('product::products.tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::products.tags.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tag_translations')->where('lang_code', getSessionLanguage()),
            ],
            'slug' => 'required|unique:tags',
        ]);

        if ($request->fails()) {
            return redirect()->back()
                ->withErrors($request)
                ->withInput();
        }

        $data = $request->validated();

        $tag = Tag::create($data);

        $this->generateTranslations(
            TranslationModels::Tag,
            $tag,
            'tag_id',
            $request,
        );

        return $this->redirectWithMessage(RedirectType::CREATE->value, 'admin.product.tags.edit', ['tag' => $tag->id, 'code' => getSessionLanguage()], [
            'message'    => 'Created successfully',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $tag = Tag::find($id);

        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        return view('product::products.tags.edit', compact('tag', 'code'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::find($id);

        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tag_translations')
                    ->where('lang_code', $code)
                    ->ignore($tag->id, 'tag_id'),
            ],
            'slug' => [
                'sometimes',
                'required',
                Rule::unique('tags')->ignore($tag->id),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        $tag->update($data);

        $this->updateTranslations(
            $tag,
            $request,
            $data,
        );

        return $this->redirectWithMessage(RedirectType::UPDATE->value, 'admin.product.tags.edit', ['tag' => $tag->id, 'code' => $code], [
            'message'    => 'Updated successfully',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tag = Tag::find($id);

        if ($tag && $tag?->products?->count() > 0) {
            return $this->redirectWithMessage(RedirectType::DELETE->value, notification: [
                'message'    => __('Tag delete failed, Associted with products!'),
                'alert-type' => 'error',
            ]);
        }

        $tag->translations()->delete();

        $tag->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.product.tags.index', [], [
            'message'    => 'Deleted successfully',
            'alert-type' => 'success',
        ]);
    }
}
