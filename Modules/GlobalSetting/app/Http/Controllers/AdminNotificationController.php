<?php

namespace Modules\GlobalSetting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\GlobalSetting\app\Models\AdminNotification;

class AdminNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $query = AdminNotification::query();

        $query->when(request()->filled('search'), function ($q) {
            $q->where('message', 'like', '%'.request('search').'%')->orWhere('title', 'like', '%'.request('search').'%');
        });

        $query->when(request()->filled('type'), function ($q) {
            $type = request('type') == 'unread' ? 0 : 1;

            $q->where('is_read', $type);
        });

        $query->when(request()->filled('alert_type'), function ($q) {
            $q->where('type', request('alert_type'));
        });

        $query->when(request()->filled('order'), function ($q) {
            $date = request('order') == 'asc' ? 'asc' : 'desc';

            $q->orderBy('created_at', $date);
        });

        $data['notifications'] = $query->paginate(40)->withQueryString();
        $data['totalNotificationsCount'] = AdminNotification::count();
        $data['unreadCount'] = AdminNotification::where('is_read', 0)->count();
        $data['readCount'] = $data['totalNotificationsCount'] - $data['unreadCount'];
        $data['infoCount'] = AdminNotification::where('type', 'info')->count();
        $data['successCount'] = AdminNotification::where('type', 'success')->count();
        $data['warningCount'] = AdminNotification::where('type', 'warning')->count();
        $data['dangerCount'] = AdminNotification::where('type', 'danger')->count();

        self::forgetCache();

        return view('globalsetting::notifications.index', $data);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $notification = AdminNotification::findOrFail($id);

        $notification->update([
            'is_read' => 1,
        ]);

        $notification->refresh();

        self::forgetCache();

        return view('globalsetting::notifications.show', compact('notification'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        AdminNotification::findOrFail($id)->delete();

        self::forgetCache();

        return to_route('admin.notifications.index')->with([
            'message' => 'Deleted successfully',
            'alert-type' => 'success',
        ]);
    }

    public function markAsRead(Request $request)
    {
        if ($request->has('ids') && $request->filled('ids')) {
            AdminNotification::whereIn('id', $request->ids)->update(['is_read' => true]);
        } else {
            AdminNotification::where('is_read', false)->update(['is_read' => true]);
        }

        self::forgetCache();

        $notification = __('Messages marked as read successfully');

        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return back()->with($notification);
    }

    public static function forgetCache()
    {
        Cache::forget('admin-notifications');
        Cache::rememberForever('admin-notifications', function () {
            return AdminNotification::where('is_read', 0)->latest()->get();
        });
    }

    public function deleteAll(Request $request)
    {
        if ($request->has('ids') && $request->filled('ids')) {
            AdminNotification::whereIn('id', $request->ids)->delete();
        } else {
            AdminNotification::query()->delete();
        }

        self::forgetCache();

        return back()->with([
            'message' => $request->filled('ids') ? 'Deleted successfully' : 'Deleted successfully',
            'alert-type' => 'success',
        ]);
    }
}
