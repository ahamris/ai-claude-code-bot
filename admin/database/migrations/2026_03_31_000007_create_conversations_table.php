<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('telegram_user_id')->nullable();
            $table->text('user_message');
            $table->text('assistant_message');
            $table->timestamp('created_at')->nullable();

            $table->index(['bot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
