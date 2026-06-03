<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dummyImage = 'https://ranglerzbeta.in/homeservices/public/backend/img/admin-auth-bg.jpg';
        
        // Add a dummy district to avoid foreign key constraint error
        $districtId = DB::table('districts')->insertGetId([
            'name' => 'Dummy District',
            'status' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Add Dummy Customers
        for ($i = 1; $i <= 5; $i++) {
            DB::table('users')->updateOrInsert(
                ['email' => 'customer' . $i . '@example.com'],
                [
                    'user_type' => 'customer',
                    'district_id' => $districtId,
                    'name' => 'Customer ' . $i,
                    'phone' => '1234567890' . $i,
                    'password' => Hash::make('password123'),
                    'is_verified' => 1,
                    'status' => 'active',
                    'photo' => $dummyImage,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        // Add Dummy Technicians
        for ($i = 1; $i <= 5; $i++) {
            DB::table('users')->updateOrInsert(
                ['email' => 'technician' . $i . '@example.com'],
                [
                    'user_type' => 'technician',
                    'district_id' => $districtId,
                    'name' => 'Technician ' . $i,
                    'phone' => '9876543210' . $i,
                    'password' => Hash::make('password123'),
                    'is_verified' => 1,
                    'bio' => 'Expert technician in field ' . $i,
                    'skills' => json_encode(['Plumbing', 'Electrical', 'AC Repair']),
                    'experience' => rand(1, 10) . ' years',
                    'service_area' => json_encode(['City Center', 'Suburbs']),
                    'availability' => json_encode(['Monday-Friday' => '09:00 AM - 05:00 PM']),
                    'status' => 'active',
                    'photo' => $dummyImage,
                    'cnic_front' => $dummyImage,
                    'cnic_back' => $dummyImage,
                    'certificates' => json_encode([$dummyImage, $dummyImage]),
                    'subscription' => 'Premium Plan',
                    'subscription_end' => Carbon::now()->addDays(rand(10, 30))->format('Y-m-d'),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        // Fetch users to create bookings
        $customers = DB::table('users')->where('user_type', 'customer')->pluck('id')->toArray();
        $technicians = DB::table('users')->where('user_type', 'technician')->pluck('id')->toArray();

        // Add Dummy Bookings
        if (count($customers) > 0 && count($technicians) > 0) {
            for ($i = 1; $i <= 10; $i++) {
                $bookingRef = 'BKG-' . strtoupper(Str::random(8)) . '-' . $i;
                DB::table('bookings')->updateOrInsert(
                    ['booking_reference' => $bookingRef],
                    [
                        'customer_id' => $customers[array_rand($customers)],
                        'technician_id' => $technicians[array_rand($technicians)],
                        'service_date' => Carbon::now()->addDays(rand(1, 10))->format('Y-m-d'),
                        'time_slot' => Carbon::createFromTime(rand(9, 17), 0, 0)->format('H:i'),
                        'status' => 'pending', 
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            }
        }
    }
}
