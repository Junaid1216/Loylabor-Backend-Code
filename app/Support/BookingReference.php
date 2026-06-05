<?php

namespace App\Support;

use App\Models\Booking;

class BookingReference
{
    public static function generate(): string
    {
        $prefix = 'FXT';
        $year = date('Y');

        do {
            $code = sprintf('%s-%s-%04d', $prefix, $year, random_int(1000, 9999));
        } while (Booking::where('booking_reference', $code)->exists());

        return $code;
    }
}
