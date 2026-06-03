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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['customer', 'technician']); // customer or technician
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
			$table->string('fcmtoken');
            $table->foreignId('district_id')->constrained('districts');
            $table->string('password');
            $table->boolean('is_verified')->default(false);
            $table->string('otp')->nullable();
            
            // Technician extra fields (null for customer)
            $table->string('cnic_front')->nullable();
            $table->string('cnic_back')->nullable();
            $table->string('photo')->nullable();
            $table->json('certificates')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->string('experience')->nullable();
            $table->json('service_area')->nullable();
            $table->json('availability')->nullable();
            $table->enum('status', ['pending', 'review', 'active', 'rejected'])->default('pending');
            $table->enum('subscription', ['inactive', 'active'])->default('inactive');
            $table->date('subscription_end')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
