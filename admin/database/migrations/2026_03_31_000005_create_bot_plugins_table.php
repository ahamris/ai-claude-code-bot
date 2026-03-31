<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('plugin_name');
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->default('{}');
            $table->timestamps();

            $table->unique(['bot_id', 'plugin_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_plugins');
    }
};
