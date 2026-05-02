<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('source_hash', 32)->index();
            $table->text('source_text');
            $table->string('target_locale', 10)->index();
            $table->text('translated_text');
            $table->timestamps();

            $table->unique(['source_hash', 'target_locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
