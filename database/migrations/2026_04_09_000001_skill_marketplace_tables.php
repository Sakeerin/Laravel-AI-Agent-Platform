<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('general');
            $table->string('version', 32)->default('1.0.0');
            $table->json('tags')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->json('manifest');
            $table->timestamps();
        });

        Schema::create('user_skill_installs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_package_id')->constrained('skill_packages')->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->string('installed_version', 32);
            $table->timestamps();

            $table->unique(['user_id', 'skill_package_id']);
        });

        Schema::create('skill_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('version', 32);
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->string('source', 32)->default('builtin')->after('requires_approval');
            $table->foreignId('skill_package_id')->nullable()->after('source')->constrained('skill_packages')->nullOnDelete();
            $table->string('manifest_version', 32)->nullable()->after('skill_package_id');
            $table->json('permissions')->nullable()->after('manifest_version');
            $table->unsignedInteger('rate_limit_per_minute')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_installs');
        Schema::dropIfExists('skill_revisions');

        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign(['skill_package_id']);
            $table->dropColumn([
                'source',
                'skill_package_id',
                'manifest_version',
                'permissions',
                'rate_limit_per_minute',
            ]);
        });

        Schema::dropIfExists('skill_packages');
    }
};
