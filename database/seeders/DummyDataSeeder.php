<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $dummyImage = 'backend/img/cnic-sample.jpg';
        $dummyPayment = 'payments/dummy-payment.jpg';

        // District (upsert)
        $districtPayload = ['status' => 'active'];
        if (Schema::hasColumn('districts', 'created_at')) {
            $districtPayload['created_at'] = $now;
        }
        if (Schema::hasColumn('districts', 'updated_at')) {
            $districtPayload['updated_at'] = $now;
        }

        DB::table('districts')->updateOrInsert(['name' => 'Dummy District'], $districtPayload);

        $districtId = (int) DB::table('districts')->where('name', 'Dummy District')->value('id');

        // Subscription plans (upsert)
        $plans = [
            [
                'name' => 'Basic',
                'duration_months' => 1,
                'price_pkr' => 1500,
                'saving_price' => 1200,
                'discount_percent' => 20,
                'tax_percent' => 10,
                'is_active' => 1,
                'features' => json_encode(['Priority listing', 'Limited requests']),
            ],
            [
                'name' => 'Premium',
                'duration_months' => 3,
                'price_pkr' => 4000,
                'saving_price' => 3500,
                'discount_percent' => 12,
                'tax_percent' => 10,
                'is_active' => 1,
                'features' => json_encode(['Unlimited requests', 'Featured profile', 'Priority support']),
            ],
        ];

        foreach ($plans as $p) {
            $planPayload = $p;
            if (Schema::hasColumn('subscriptions', 'created_at')) {
                $planPayload['created_at'] = $now;
            }
            if (Schema::hasColumn('subscriptions', 'updated_at')) {
                $planPayload['updated_at'] = $now;
            }

            DB::table('subscriptions')->updateOrInsert(
                ['name' => $p['name']],
                $planPayload
            );
        }

        $basicPlanId = (int) DB::table('subscriptions')->where('name', 'Basic')->value('id');
        $premiumPlanId = (int) DB::table('subscriptions')->where('name', 'Premium')->value('id');

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
                    ...(Schema::hasColumn('users', 'created_at') ? ['created_at' => $now] : []),
                    ...(Schema::hasColumn('users', 'updated_at') ? ['updated_at' => $now] : []),
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
                    'availability' => null,
                    'status' => 'active', // already verified
                    'photo' => $dummyImage,
                    'cnic_front' => $dummyImage,
                    'cnic_back' => $dummyImage,
                    'certificates' => json_encode([$dummyImage, $dummyImage]),
                    'cnic_front_verified' => 1,
                    'cnic_back_verified' => 1,
                    'photo_verified' => 1,
                    'certificates_verified' => 1,
                    'subscription_id' => ($i % 2 === 0) ? $basicPlanId : $premiumPlanId,
                    'payment_status' => 'verified',
                    'payment_screenshot' => $dummyPayment,
                    'subscription' => 'active',
                    'subscription_end' => $now->copy()->addDays(rand(10, 30))->format('Y-m-d'),
                    ...(Schema::hasColumn('users', 'created_at') ? ['created_at' => $now] : []),
                    ...(Schema::hasColumn('users', 'updated_at') ? ['updated_at' => $now] : []),
                ]
            );
        }

        // Fetch users to create bookings
        // Prefer existing real users if present (so bookings appear against your current users)
        $customers = DB::table('users')->where('user_type', 'customer')->pluck('id')->toArray();
        $technicians = DB::table('users')->where('user_type', 'technician')->pluck('id')->toArray();

        // Add Dummy Bookings
        if (count($customers) > 0 && count($technicians) > 0) {
            // Pending (no reference yet)
            for ($i = 1; $i <= 5; $i++) {
                DB::table('bookings')->insert([
                    'customer_id' => $customers[array_rand($customers)],
                    'technician_id' => $technicians[array_rand($technicians)],
                    'emergency_level' => 'low',
                    'status' => 'pending',
                    'service_date' => $now->copy()->addDays(rand(1, 5))->format('Y-m-d'),
                    'time_slot' => Carbon::createFromTime(rand(9, 17), 0, 0)->format('H:i'),
                    'address' => 'Dummy address',
                    'city' => 'Dummy city',
                    'phone' => '03001234567',
                    'booking_reference' => null,
                    'expires_at' => $now->copy()->addMinutes(5),
                    ...(Schema::hasColumn('bookings', 'created_at') ? ['created_at' => $now] : []),
                    ...(Schema::hasColumn('bookings', 'updated_at') ? ['updated_at' => $now] : []),
                ]);
            }

            // Accepted (reference exists)
            for ($i = 1; $i <= 5; $i++) {
                $bookingRef = sprintf('FXT-%s-%04d', date('Y'), random_int(1000, 9999));
                DB::table('bookings')->insert([
                    'customer_id' => $customers[array_rand($customers)],
                    'technician_id' => $technicians[array_rand($technicians)],
                    'emergency_level' => 'medium',
                    'status' => 'accepted',
                    'service_date' => $now->copy()->addDays(rand(1, 10))->format('Y-m-d'),
                    'time_slot' => Carbon::createFromTime(rand(9, 17), 0, 0)->format('H:i'),
                    'address' => 'Dummy address',
                    'city' => 'Dummy city',
                    'phone' => '03001234567',
                    'booking_reference' => $bookingRef,
                    'expires_at' => null,
                    'accepted_at' => $now,
                    ...(Schema::hasColumn('bookings', 'created_at') ? ['created_at' => $now] : []),
                    ...(Schema::hasColumn('bookings', 'updated_at') ? ['updated_at' => $now] : []),
                ]);
            }
        }
    }
}
