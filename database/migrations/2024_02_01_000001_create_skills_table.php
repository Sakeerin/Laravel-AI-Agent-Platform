<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description');
            $table->json('parameters_schema');
            $table->string('category')->default('general');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_system')->default(false);
            $table->json('config')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(30);
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
