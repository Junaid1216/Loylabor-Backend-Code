<?php

namespace Modules\Frontend\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Frontend\app\Enums\ManageThemeEnum;
use Modules\Frontend\app\Models\Section;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\Language\app\Models\Language;

class FrontendController extends Controller
{
    public function homepage()
    {
        checkAdminHasPermissionAndThrowException('frontend.view');

        $themes = ManageThemeEnum::themes();

        return view('frontend::hompage-update', compact('themes'));
    }

    /**
     * @param Request $request
     */
    public function updateHomepage(Request $request)
    {
        checkAdminHasPermissionAndThrowException('frontend.update');

        $request->validate([
            'show_all_homepage' => 'sometimes|in:1,0',
            'theme'             => 'sometimes|in:' . implode(',', themeList()),
        ], [
            'show_all_homepage.in' => 'The show all homepage field must be either Active or Inactive.',
            'theme.in'             => 'The selected theme is invalid.',
        ]);

        if (strtolower(config('app.app_mode')) == 'live') {
            Setting::where('key', 'show_all_homepage')->update(['value' => 0]);
        }

        if ($request->has('show_all_homepage')) {
            Setting::where('key', 'show_all_homepage')->update(['value' => $request->get('show_all_homepage')]);

            Cache::forget('setting');

            return back()->with([
                'alert-type' => 'success',
                'message'    => 'Updated successfully',
            ]);
        }

        if ($request->has('theme')) {
            $theme = $request->get('theme');

            if (!in_array($theme, themeList())) {
                return back()->with([
                    'alert-type' => 'error',
                    'message'    => 'Invalid theme selected',
                ]);
            }

            Setting::where('key', 'theme')->update(['value' => $theme]);

            session()->put('selected_theme', $theme);

            Cache::forget('setting');
        }

        return back()->with([
            'alert-type' => 'success',
            'message'    => __('Updated successfully'),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        checkAdminHasPermissionAndThrowException('frontend.view');

        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }

        $theme = config('services.theme') ?? getSettings('theme');

        $sections = Section::whereHomeId($theme)
            ->with(['translations' => function ($query) use ($code) {
                $query->where('lang_code', $code);
            }])
            ->get();

        $languages = allLanguages();

        return view('frontend::index', compact('sections', 'languages', 'code'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        checkAdminHasPermissionAndThrowException('frontend.update');

        $section = Section::find($id);

        if ($request->has('status')) {
            $section->status = $request->status;
            $section->save();
        }

        $fields = array_diff($request->keys(), ['_token', 'section_id', 'status']);

        $images = [];

        foreach ($request->all() as $key => $value) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);

                if (!$file->isValid() || !in_array($file->extension(), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                    return back()
                        ->withErrors([$key => 'The ' . str($key)->replace('_', ' ')->title()->toString() . ' must be an image.'])
                        ->withInput();
                }

                if ($file->getSize() > 1024 * 1024) {
                    return back()
                        ->withErrors([$key => 'The ' . str($key)->replace('_', ' ')->title()->toString() . ' must not be greater than 1 MB.'])
                        ->withInput();
                }

                $images[] = $key;
            }
        }

        $global_content = $this->updateSectionContent($section?->global_content, $request, $fields, $images);

        $section->update(['global_content' => $global_content]);

        $translationsInput = $request->input('translations', []);

        foreach ($translationsInput as $langCode => $fields) {
            $section->translations()->where('lang_code', $langCode)->update([
                'content' => $fields,
            ]);
        }

        return back()->with([
            'alert-type' => 'success',
            'message'    => __('Updated successfully'),
        ]);
    }

    /**
     * @return mixed
     */
    public function updateSectionContent($content, $request, array $fields, array $images = [])
    {
        if (is_null($content)) {
            $content = new \stdClass;
        }

        foreach ($fields as $field) {
            if (isset($content->$field) && $request->has($field) && $request->filled($field)) {
                $content->$field->value = $request->$field;
            }
        }

        foreach ($images as $field) {
            if (isset($content->$field) && $request->hasFile($field)) {
                $file                   = file_upload($request->file($field), 'uploads/custom-images/', $content->$field->value ?? null);
                $content->$field->value = $file;
            }
        }

        return $content;
    }
}
