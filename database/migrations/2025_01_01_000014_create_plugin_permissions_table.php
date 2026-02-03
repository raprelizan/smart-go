<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins');
            $table->string('permission');
            $table->timestamps();

            $table->unique(['plugin_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_permissions');
    }
};
