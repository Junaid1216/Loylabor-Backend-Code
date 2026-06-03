<?php

namespace Modules\CustomMenu\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\CustomMenu\app\Enums\DefaultMenusEnum;
use Modules\CustomMenu\app\Models\Menu;
use Modules\CustomMenu\app\Models\MenuItem;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class CustomMenuController extends Controller
{
    use GenerateTranslationTrait, RedirectHelperTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        checkAdminHasPermissionAndThrowException('menu.view');
        $menus = Menu::get();
        $languages = allLanguages();
        $defaultMenuItemList = DefaultMenusEnum::getAll();

        $select_menu = null;
        $menuItems = [];

        if ((request()->has('action') && ! empty(request()->input('menu'))) || request()->input('menu') != '0') {
            $menu_id = request()->input('menu');
            $select_menu = Menu::select('id', 'name')->find($menu_id);
            $items = new MenuItem;
            $menuItems = $items->getAllParents(request()->input('menu'));
        }

        return view('custommenu::index', compact('menus', 'languages', 'defaultMenuItemList', 'select_menu', 'menuItems'));
    }

    public function addMenuItem(Request $request): JsonResponse
    {
        checkAdminHasPermissionAndThrowException('menu.create');
        $validator = Validator::make($request->all(), [
            'link' => 'required|string|max:255',
            'label' => 'required|string|max:255',
        ], [
            'label.required' => __('Name is required'),
            'label.max' => __('The name may not be greater than 255 characters.'),
            'label.string' => __('The name must be a string.'),
            'link.required' => __('Link is required'),
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }
        try {
            $menuItem = new MenuItem;
            $menuItem->label = request()->input('label');
            $menuItem->link = request()->input('link');
            $menuItem->menu_id = request()->input('menu_id');
            $menuItem->custom_item = request()->input('custom_item');
            $menuItem->open_new_tab = request()->input('open_new_tab');
            $menuItem->sort = MenuItem::getNextSortRoot(request()->input('menu_id'));
            $menuItem->save();

            $this->generateTranslations(
                TranslationModels::MenuItem,
                $menuItem,
                'menu_item_id',
                request(),
            );

            return response()->json(['success' => true, 'message' => __('Created successfully'), 'data' => ['id' => $menuItem->id, 'label' => $menuItem->label]]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => __('Item failed to add')]);
        }
    }

    public function updateMenuName(Request $request): JsonResponse
    {
        checkAdminHasPermissionAndThrowException('menu.update');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ], [
            'name.required' => __('Name is required'),
            'name.max' => __('The name may not be greater than 255 characters.'),
            'name.string' => __('The name must be a string.'),
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }
        try {
            $id = request()->input('id');
            $name = request()->input('name');
            $code = request()->input('code');

            $menu = Menu::find($id);

            if ($code == config('app.locale')) {
                $menu->name = $name;
                $menu->save();
            }

            $this->updateTranslations(
                $menu,
                request(),
                ['name' => $name],
            );

            return response()->json(['success' => true, 'message' => __('Updated successfully')]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => __('Update Failed')]);
        }
    }

    public function updateMenu()
    {
        checkAdminHasPermissionAndThrowException('menu.update');
        $menuItems = request()->input('data');
        try {
            foreach ($menuItems as $index => $menuItemData) {
                $this->updateMenuRecursive($menuItemData, null, $index + 1);
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    private function updateMenuRecursive(array $menuItemData, $parentId = null, $sortOrder = 1)
    {
        $menuItem = MenuItem::find($menuItemData['id']);
        $menuItem->parent_id = $parentId;
        $menuItem->sort = $sortOrder;
        $menuItem->save();

        // Process children if any
        if (isset($menuItemData['children']) && is_array($menuItemData['children'])) {
            foreach ($menuItemData['children'] as $index => $child) {
                $this->updateMenuRecursive($child, $menuItem->id, $index + 1);
            }
        }
    }

    public function updateMenuItem(Request $request)
    {
        checkAdminHasPermissionAndThrowException('menu.update');
        $request->validate([
            'link' => 'required|string|max:255',
            'label' => 'required|string|max:255',
        ], [
            'label.required' => __('Name is required'),
            'label.max' => __('The name may not be greater than 255 characters.'),
            'label.string' => __('The name must be a string.'),
            'link.required' => __('Link is required'),
        ]);
        try {
            $menuItem = MenuItem::find($request->id);
            if ($request->code == config('app.locale')) {
                $menuItem->label = $request->label;
                $menuItem->link = $request->link;
                $menuItem->open_new_tab = $request->open_new_tab;
                $menuItem->save();
            }
            $this->updateTranslations(
                $menuItem,
                request(),
                ['label' => $request->label],
            );

            return $this->redirectWithMessage(RedirectType::UPDATE->value);
        } catch (Exception $e) {
            return $this->redirectWithMessage(RedirectType::ERROR->value);
        }
    }

    public function deleteMenuItem(Request $request)
    {
        checkAdminHasPermissionAndThrowException('menu.delete');
        $menuItem = MenuItem::find($request->id);
        try {
            if ($menuItem) {
                $this->deleteWithChildren($menuItem);
            }

            return $this->redirectWithMessage(RedirectType::DELETE->value);
        } catch (Exception $th) {
            return $this->redirectWithMessage(RedirectType::ERROR->value);
        }
    }

    private function deleteWithChildren($menuItem)
    {
        if ($menuItem->child()->exists()) {
            foreach ($menuItem->child as $child) {
                $this->deleteWithChildren($child);
                $child->delete();
            }
        }
        $menuItem->delete();
    }
}
