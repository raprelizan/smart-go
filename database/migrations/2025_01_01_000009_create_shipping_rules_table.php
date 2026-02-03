<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('wilaya_id')->constrained('wilayas');
            $table->decimal('price', 10, 2);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'product_id', 'wilaya_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
    }
};
