<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Support\BookingReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Customer: Book a technician
    public function bookTechnician(Request $request)
    {
        $request->validate([
            'technician_id' => 'required|exists:users,id',
            'emergency_level' => 'required|in:low,medium,high,emergency',
            'service_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required|date_format:H:i',
            'service_details' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'additional_notes' => 'nullable|string'
        ]);

        $customer = $request->user();
        $technician = User::where('id', $request->technician_id)
                         ->where('user_type', 'technician')
                         ->where('status', 'active')
                         ->first();

        if (!$technician) {
            return response()->json(['error' => 'Technician not found or not active'], 404);
        }

        // Check if technician is available on that date/time
        if (!$this->isTechnicianAvailable($technician->id, $request->service_date, $request->time_slot)) {
            return response()->json(['error' => 'Technician not available at this time'], 400);
        }

        // Check for existing booking conflict
        $existingBooking = Booking::where('technician_id', $technician->id)
            ->where('service_date', $request->service_date)
            ->where('time_slot', $request->time_slot)
            ->whereNotIn('status', ['cancelled', 'completed', 'rejected'])
            ->exists();

        if ($existingBooking) {
            return response()->json(['error' => 'This time slot is already booked'], 400);
        }

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'emergency_level' => $request->emergency_level,
            'status' => 'pending',
            'service_date' => $request->service_date,
            'time_slot' => $request->time_slot,
            'service_details' => $request->service_details,
            'address' => $request->address,
            'city' => $request->city,
            'phone' => $request->phone ?? $customer->phone,
            'additional_notes' => $request->additional_notes,
            'booking_reference' => null,
        ]);

        // Send notification to technician (via push, sms, or email)
        $this->notifyTechnician($technician, $booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking request sent successfully',
            'booking' => $booking->load('customer', 'technician'),
            'note' => 'Reference code will be generated when technician confirms the booking.',
        ], 201);
    }

    // Technician: Get all booking requests
    public function getBookingRequests(Request $request)
    {
        $technician = $request->user();

        if ($technician->user_type !== 'technician') {
            return response()->json(['error' => 'Only technicians can view booking requests'], 403);
        }

        $status = $request->get('status', 'pending');
        
        $bookings = Booking::where('technician_id', $technician->id)
            ->when($status, function($query, $status) {
                if ($status !== 'all') {
                    return $query->where('status', $status);
                }
                return $query;
            })
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    // Technician: Accept a booking request
    public function acceptBooking(Request $request, $bookingId)
    {
        $technician = $request->user();

        if ($technician->user_type !== 'technician') {
            return response()->json(['error' => 'Only technicians can accept bookings'], 403);
        }

        $booking = Booking::where('id', $bookingId)
            ->where('technician_id', $technician->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or already processed'], 404);
        }

        // Check if time slot still available
        $existingAccepted = Booking::where('technician_id', $technician->id)
            ->where('service_date', $booking->service_date)
            ->where('time_slot', $booking->time_slot)
            ->whereIn('status', ['accepted', 'pending'])
            ->where('id', '!=', $bookingId)
            ->exists();

        if ($existingAccepted) {
            return response()->json(['error' => 'This time slot is no longer available'], 400);
        }

        $referenceCode = $booking->booking_reference ?: BookingReference::generate();

        $booking->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'booking_reference' => $referenceCode,
        ]);

        // Send notification to customer
        $this->notifyCustomer($booking->customer, $booking, 'accepted');

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully',
            'booking' => $booking->fresh(),
            'booking_reference' => $referenceCode,
        ]);
    }

    // Technician: Reject a booking request
    public function rejectBooking(Request $request, $bookingId)
    {
        $technician = $request->user();

        if ($technician->user_type !== 'technician') {
            return response()->json(['error' => 'Only technicians can reject bookings'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string'
        ]);

        $booking = Booking::where('id', $bookingId)
            ->where('technician_id', $technician->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or already processed'], 404);
        }

        $booking->update([
            'status' => 'rejected',
            'cancellation_reason' => $request->reason ?? 'Rejected by technician'
        ]);

        // Notify customer
        $this->notifyCustomer($booking->customer, $booking, 'rejected');

        return response()->json([
            'success' => true,
            'message' => 'Booking rejected'
        ]);
    }

    // Customer: Get my bookings
    public function myBookings(Request $request)
    {
        $customer = $request->user();

        $status = $request->get('status', 'all');
        
        $bookings = Booking::where('customer_id', $customer->id)
            ->when($status !== 'all', function($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with('technician')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    // Customer: Cancel booking
    public function cancelBooking(Request $request, $bookingId)
    {
        $customer = $request->user();

        $request->validate([
            'reason' => 'nullable|string'
        ]);

        $booking = Booking::where('id', $bookingId)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or cannot be cancelled'], 404);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason ?? 'Cancelled by customer'
        ]);

        // Notify technician
        $this->notifyTechnician($booking->technician, $booking, 'cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
    }

    // Technician: Complete booking
    public function completeBooking(Request $request, $bookingId)
    {
        $technician = $request->user();

        if ($technician->user_type !== 'technician') {
            return response()->json(['error' => 'Only technicians can complete bookings'], 403);
        }

        $booking = Booking::where('id', $bookingId)
            ->where('technician_id', $technician->id)
            ->where('status', 'accepted')
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or not in accepted status'], 404);
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as completed'
        ]);
    }

    // Customer: Get single booking details
    public function getBookingDetails(Request $request, $bookingId)
    {
        $user = $request->user();

        $booking = Booking::where('id', $bookingId)
            ->where(function($query) use ($user) {
                $query->where('customer_id', $user->id)
                      ->orWhere('technician_id', $user->id);
            })
            ->with(['customer', 'technician'])
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    // Helper: Check technician availability
    private function isTechnicianAvailable($technicianId, $date, $timeSlot)
    {
        // Check if technician has any accepted booking at that time
        $conflictingBooking = Booking::where('technician_id', $technicianId)
            ->where('service_date', $date)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', ['accepted', 'pending'])
            ->exists();

        if ($conflictingBooking) {
            return false;
        }

        // Check if day is within technician's working hours
        $day = strtolower(date('l', strtotime($date)));
        
        $availability = DB::table('technician_availability')
            ->where('technician_id', $technicianId)
            ->where('day', $day)
            ->where('is_available', true)
            ->first();

        if (!$availability) {
            return false;
        }

        // Check if time slot is within working hours
        $requestTime = date('H:i:s', strtotime($timeSlot));
        
        return $requestTime >= $availability->start_time && $requestTime <= $availability->end_time;
    }

    // Helper: Notify technician (simplified - you can add real notifications)
    private function notifyTechnician($technician, $booking, $type = 'new')
    {
        // TODO: Implement real notifications (Firebase, OneSignal, SMS, Email)
        // For now, just log or send email
        \Log::info("Notification to {$technician->email}: New booking request #{$booking->booking_reference}");
    }

    // Helper: Notify customer
    private function notifyCustomer($customer, $booking, $status)
    {
        \Log::info("Notification to {$customer->email}: Booking #{$booking->booking_reference} is {$status}");
    }
}