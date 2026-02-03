<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_senders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number_id');
            $table->text('access_token_encrypted');
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_senders');
    }
};
