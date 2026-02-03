<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('product_id')->constrained('products');
            $table->string('full_name');
            $table->string('phone');
            $table->string('full_address');
            $table->foreignId('wilaya_id')->constrained('wilayas');
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('shipping_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('NEW');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
