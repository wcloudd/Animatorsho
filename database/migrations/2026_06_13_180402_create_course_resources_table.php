<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_resource_category_id')
                ->nullable()
                ->constrained('course_resource_categories')
                ->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->string('status');
            $table->string('access_scope');
            $table->foreignId('course_package_id')
                ->nullable()
                ->constrained('course_packages')
                ->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_resources');
    }
};
