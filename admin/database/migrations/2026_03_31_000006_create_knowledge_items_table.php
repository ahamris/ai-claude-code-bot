<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->text('content');
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index('bot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_items');
    }
};
