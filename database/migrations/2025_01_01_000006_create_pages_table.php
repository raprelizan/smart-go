<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('version');
            $table->string('status')->default('DRAFT');
            $table->json('content');
            $table->timestamps();

            $table->index(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
