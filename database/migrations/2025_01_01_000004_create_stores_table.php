<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('name');
            $table->string('slug');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
