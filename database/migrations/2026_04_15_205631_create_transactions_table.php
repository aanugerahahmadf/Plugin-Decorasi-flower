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
        // Temporarily disable foreign key checks to safely drop old linked tables
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('payments');
        Schema::dropIfExists('topups');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('banks');
        Schema::enableForeignKeyConstraints();
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['topup', 'order']);
            $table->string('reference_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_gateway')->default('midtrans');
            $table->string('payment_method')->nullable();
            $table->string('snap_token')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed, expired
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
