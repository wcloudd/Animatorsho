<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_package_id')->constrained()->restrictOnDelete();
            $table->string('order_number')->unique();
            $table->string('status');
            $table->string('payment_type');
            $table->unsignedBigInteger('amount_toman');
            $table->unsignedBigInteger('final_amount_toman');
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
