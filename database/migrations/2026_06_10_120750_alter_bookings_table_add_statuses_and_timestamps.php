<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','accepted','on_the_way','work_started','rejected','completed','cancelled') NOT NULL DEFAULT 'pending'");
            $table->timestamp('on_the_way_at')->nullable()->after('accepted_at');
            $table->timestamp('work_started_at')->nullable()->after('on_the_way_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('on_the_way_at');
            $table->dropColumn('work_started_at');
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','accepted','rejected','completed','cancelled') NOT NULL DEFAULT 'pending'");
        });
    }
};
