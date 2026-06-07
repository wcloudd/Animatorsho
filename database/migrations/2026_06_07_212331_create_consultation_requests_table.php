<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile');
            $table->text('note')->nullable();
            $table->string('level')->nullable();
            $table->string('interest')->nullable();
            $table->string('age')->nullable();
            $table->string('status');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('mobile');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
