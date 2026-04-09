<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->json('embedding')->nullable();
            $table->decimal('importance', 4, 3)->default(0.5);
            $table->string('dedupe_hash', 64)->nullable();
            $table->string('source', 32)->default('auto');
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->unique(['user_id', 'dedupe_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_memories');
    }
};
