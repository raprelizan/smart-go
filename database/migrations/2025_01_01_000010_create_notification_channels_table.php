<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('channel');
            $table->json('settings')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['merchant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
