<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('telegram_token');
            $table->json('chat_ids')->default('[]');
            $table->text('system_prompt');

            $table->integer('ai_max_turns')->default(25);
            $table->integer('ai_timeout')->default(300);
            $table->string('ai_working_dir')->nullable();

            $table->boolean('memory_enabled')->default(true);
            $table->boolean('memory_auto_learn')->default(true);
            $table->string('memory_data_dir')->nullable();

            $table->boolean('monitoring_enabled')->default(false);
            $table->integer('health_check_interval')->default(300);
            $table->integer('security_scan_interval')->default(900);

            $table->string('formatter_mode')->default('plain');
            $table->boolean('formatter_strip_markdown')->default(true);

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
