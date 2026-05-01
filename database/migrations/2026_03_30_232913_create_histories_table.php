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
        // Drop the view to avoid collision
        DB::statement('DROP VIEW IF EXISTS histories;');

        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // topup, withdrawal, order
            $table->bigInteger('transaction_id'); // ID original record
            $table->string('reference_number');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('info')->nullable(); // bank info or package info
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
