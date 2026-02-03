<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('order_id')->constrained('orders');
            $table->string('channel');
            $table->json('payload');
            $table->json('response')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
