<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('host_name');
            $table->string('address');
            $table->boolean('reachable');
            $table->decimal('latency_ms', 10, 2);
            $table->timestamp('checked_at');

            $table->index(['bot_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
};
