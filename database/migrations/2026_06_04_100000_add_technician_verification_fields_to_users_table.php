<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('cnic_front_verified')->default(false)->after('cnic_back');
            $table->boolean('cnic_back_verified')->default(false)->after('cnic_front_verified');
            $table->boolean('photo_verified')->default(false)->after('photo');
            $table->boolean('certificates_verified')->default(false)->after('certificates');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cnic_front_verified',
                'cnic_back_verified',
                'photo_verified',
                'certificates_verified',
            ]);
        });
    }
};
