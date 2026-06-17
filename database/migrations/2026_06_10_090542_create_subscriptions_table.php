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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('duration_months');
            $table->decimal('price_pkr', 10, 2);
            $table->decimal('saving_price', 10, 2)->nullable();
            $table->integer('discount_percent')->default(0)->nullable();
            $table->integer('tax_percent')->default(10)->nullable();
            $table->boolean('is_active')->default(true)->nullable();
            $table->longText('features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
