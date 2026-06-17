<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name', 100)->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'duration_months')) {
                $table->integer('duration_months')->default(1);
            }
            if (!Schema::hasColumn('subscriptions', 'price_pkr')) {
                $table->decimal('price_pkr', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('subscriptions', 'saving_price')) {
                $table->decimal('saving_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'discount_percent')) {
                $table->integer('discount_percent')->default(0)->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'tax_percent')) {
                $table->integer('tax_percent')->default(10)->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'is_active')) {
                $table->boolean('is_active')->default(true)->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'features')) {
                $table->longText('features')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No down: avoid dropping columns on production data.
    }
};

