// database/migrations/2024_01_01_000004_create_bookings_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->enum('emergency_level', ['low', 'medium', 'high', 'emergency'])->default('low');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->date('service_date');
            $table->time('time_slot');
            $table->text('service_details')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('phone');
            $table->text('additional_notes')->nullable();
            $table->string('booking_reference')->unique();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('bookings');
    }
};