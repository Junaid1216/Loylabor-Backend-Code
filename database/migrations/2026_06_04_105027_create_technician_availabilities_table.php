<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('technician_availability', function (Blueprint $table) {
        $table->id();
        $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
        $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
        $table->time('start_time')->nullable();
        $table->time('end_time')->nullable();
        $table->boolean('is_available')->default(true);
        $table->date('specific_date')->nullable();  // Special case ke liye (e.g., holiday)
        $table->timestamps();
        
        // Unique constraint: ek technician ka ek din sirf ek baar
        $table->unique(['technician_id', 'day', 'specific_date']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_availabilities');
    }
};
