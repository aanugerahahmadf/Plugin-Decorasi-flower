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
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wedding_organizer_id')->default(1)->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->onDelete('set null');
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 15, 2);
                $table->integer('stock')->default(10);
                $table->decimal('discount_price', 15, 2)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->json('features')->nullable();
                $table->string('theme')->nullable();
                $table->string('color')->nullable();
                $table->integer('min_capacity')->nullable();
                $table->integer('max_capacity')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
