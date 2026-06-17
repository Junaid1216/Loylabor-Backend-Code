<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('booking_reference');
            }
        });

        // Add `expired` to status enum (MySQL)
        try {
            DB::statement(
                "ALTER TABLE `bookings` MODIFY `status` ENUM('pending','accepted','rejected','completed','cancelled','expired') NOT NULL DEFAULT 'pending'"
            );
        } catch (\Throwable $e) {
            // Ignore if database driver doesn't support ENUM modify or already updated
        }
    }

    public function down(): void
    {
        // Best-effort rollback: remove expired value and drop expires_at column
        try {
            DB::statement(
                "ALTER TABLE `bookings` MODIFY `status` ENUM('pending','accepted','rejected','completed','cancelled') NOT NULL DEFAULT 'pending'"
            );
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};

