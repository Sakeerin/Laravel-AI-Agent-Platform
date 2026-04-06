<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_thread_id', 191);
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['channel_connection_id', 'external_thread_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_threads');
    }
};
