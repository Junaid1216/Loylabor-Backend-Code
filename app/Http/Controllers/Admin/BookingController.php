<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\GlobalSetting\app\Models\Setting;

class BookingController extends Controller
{
    private function bookingExpiryMinutes(): int
    {
        $value = null;
        try {
            $value = getSettings('booking_request_expiry_minutes');
        } catch (\Throwable $e) {
            $value = null;
        }

        if (is_object($value)) {
            $value = null;
        }

        $minutes = (int) ($value ?: 5);
        return $minutes < 1 ? 5 : $minutes;
    }

    public function index()
    {
        $bookings = Booking::with(['customer', 'technician'])->latest()->paginate(10);
        return view('admin.bookings.index', compact('bookings'));
    }

    public function settings()
    {
        $minutes = $this->bookingExpiryMinutes();

        return view('admin.bookings.settings', compact('minutes'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'booking_request_expiry_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $minutes = (string) $request->booking_request_expiry_minutes;
        $setting = Setting::where('key', 'booking_request_expiry_minutes')->first();

        if ($setting) {
            $setting->update(['value' => $minutes]);
        } else {
            $setting = new Setting();
            $setting->key = 'booking_request_expiry_minutes';
            $setting->value = $minutes;
            $setting->save();
        }

        Cache::forget('setting');

        return redirect()
            ->route('admin.bookings.settings')
            ->with('success', 'Booking request expiry minutes updated.');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted successfully.');
    }
}
