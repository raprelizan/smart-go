<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('store_id')->constrained('stores');
            $table->string('name');
            $table->string('slug');
            $table->decimal('price', 10, 2);
            $table->longText('description')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
