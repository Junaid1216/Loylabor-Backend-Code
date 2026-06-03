<?php

namespace Modules\Language\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Modules\Language\app\Enums\AllCountriesDetailsEnum;
use Modules\Language\app\Enums\SyncLanguageType;
use Modules\Language\app\Http\Requests\LanguageRequest;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\LanguageTrait;
use Modules\Language\app\Traits\SyncModelsTrait;

class LanguageController extends Controller
{
    use LanguageTrait, RedirectHelperTrait, SyncModelsTrait;

    public function index(): View
    {
        checkAdminHasPermissionAndThrowException('language.view');
        Paginator::useBootstrap();
        $languages = Language::paginate(15);

        return view('language::index', [
            'languages' => $languages,
        ]);
    }

    public function create(): View
    {
        checkAdminHasPermissionAndThrowException('language.create');

        $existingCodes = Language::pluck('code')->toArray();

        $all_languages = AllCountriesDetailsEnum::getLanguages();

        $all_languages = $all_languages->reject(function ($language) use ($existingCodes) {
            return in_array($language->code, $existingCodes);
        });

        return view('language::create', compact('all_languages'));
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('language.store');

        $direction = $this->getTextDirection($request->code);
        $language = Language::create(array_merge(['direction' => $direction], $request->validated()));

        if ($language) {
            $code = $language->code;
            $parentDir = dirname(app_path());

            $sourcePath = $parentDir.'/lang/en.json';
            $destinationPath = $parentDir."/lang/{$code}.json";

            if (File::exists($sourcePath) && ! File::exists($destinationPath)) {
                $jsonData = File::get($sourcePath);
                File::put($destinationPath, $jsonData);
            }
        }

        $this->syncModels(SyncLanguageType::CREATE->value, boolval(SyncLanguageType::isQueueable()), $language->code);

        return $this->redirectWithMessage(
            RedirectType::CREATE->value,
            'admin.languages.index',
        );
    }

    public function edit(Language $language): View
    {
        checkAdminHasPermissionAndThrowException('language.edit');
        $existingCodes = Language::whereNot('code', $language->code)->pluck('code')->toArray();

        $all_languages = AllCountriesDetailsEnum::getLanguages();

        $all_languages = $all_languages->reject(function ($language) use ($existingCodes) {
            return in_array($language->code, $existingCodes);
        });

        return view('language::edit', compact('language', 'all_languages'));
    }

    public function update(LanguageRequest $request, Language $language): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('language.update');
        $oldCode = $language->code;

        $direction = $this->getTextDirection($request->code);
        $language->update(array_merge(['direction' => $direction], $request->validated()));

        $code = $language->code;

        if ($language && ($oldCode !== $code) && ($code !== 'en')) {
            $parentDir = dirname(app_path());

            $sourcePath = $parentDir.'/lang/en.json';
            $destinationPath = $parentDir."/lang/{$code}.json";

            if (File::exists($sourcePath) && ! File::exists($destinationPath)) {
                $jsonData = File::get($sourcePath);
                File::put($destinationPath, $jsonData);
            }

            if ($oldCode !== $code && $code !== 'en') {
                $destinationPath = $parentDir."/lang/{$oldCode}.json";
                try {
                    File::delete($destinationPath);
                } catch (Exception $ex) {
                    info($ex->getMessage());
                }
            }
        }

        $this->syncModels(SyncLanguageType::UPDATE->value, boolval(SyncLanguageType::isQueueable()), $language->code, $oldCode);

        return $this->redirectWithMessage(
            RedirectType::UPDATE->value,
            'admin.languages.index',
        );
    }

    public function destroy(Language $language): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('language.delete');
        if ($language->id == 1) {
            return $this->redirectWithMessage(
                RedirectType::ERROR->value,
                'admin.languages.index',
            );
        }

        $code = $language->code;
        if ($code == app()->getLocale() || $code == 'en') {
            return $this->redirectWithMessage(
                RedirectType::ERROR->value,
                'admin.languages.index',
            );
        }

        if ($code !== 'en' && $deleted = $language->delete()) {
            $destinationPath = dirname(app_path())."/lang/{$code}.json";
            File::delete($destinationPath);
        }

        if ($deleted) {
            $this->syncModels(SyncLanguageType::DELETE->value, boolval(SyncLanguageType::isQueueable()), $code);
        }

        sessionForgetLangChang();

        return $this->redirectWithMessage(
            RedirectType::DELETE->value,
            'admin.languages.index',
        );
    }

    public function updateStatus(Language $language): JsonResponse
    {
        checkAdminHasPermissionAndThrowException('language.update');

        if (request('column') == 'is_default') {
            if ($language->status == 0) {
                return response()->json([
                    'status' => false,
                    'message' => __('You can\'t set an inactive language as the default language'),
                ]);
            }
            if ($language->is_default == 0) {
                Language::where('is_default', 1)->update(['is_default' => 0]);
            }

            if (Language::where('is_default', 1)->whereNot('id', $language->id)->count() == 0 && $language->is_default == 1) {
                Language::where('id', 1)->update(['is_default' => 1, 'status' => 1]);
                if ($language->id != 1) {
                    $language->is_default = 0;
                }
            } else {
                $language->is_default = $language->is_default ? 0 : 1;
            }
        } elseif (request('column') == 'status') {
            if ($language->is_default == 1) {
                return response()->json([
                    'status' => false,
                    'message' => __('You can\'t inactive the default language'),
                ]);
            }
            $language->status = $language->status ? 0 : 1;
            if (isset($language) && $language->status == 0 && session()->has('lang') && session()->get('lang') == $language->code) {
                sessionForgetLangChang();
            }
        }
        $action = $language->save();

        return response()->json([
            'status' => $action,
            'message' => $action ? __('Updated successfully') : __('Language Updating Failed!'),
        ]);
    }

    protected function getTextDirection($languageCode)
    {
        $rtlLanguageCodes = ['ar', 'arc', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ku', 'ps', 'ur', 'yi'];
        if (in_array($languageCode, $rtlLanguageCodes)) {
            return 'rtl';
        }

        return 'ltr';
    }
}
