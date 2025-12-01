<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pet_health_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('pet_id');
            $table->uuid('category_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('value', 100)->nullable(); // VD: 2.5kg, 38°C...
            $table->text('image_url')->nullable();
            $table->date('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('pet_id')->references('pet_id')->on('pets')->onDelete('cascade');
            $table->foreign('category_id')->references('category_id')->on('health_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_health_logs');
    }
};
