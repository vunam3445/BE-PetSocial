<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_categories', function (Blueprint $table) {
            $table->uuid('category_id')->primary();
            $table->uuid('user_id')->nullable(); // Người tạo mục (null = mặc định hệ thống)
            $table->uuid('pet_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('applicable_species')->nullable(); // VD: ["dog", "cat"]
            $table->string('category_type', 20); // metric(biểu đồ) | event(sự kiện theo thời gian, timeline) | treatment(lịch điều trị, vaccine, có cảnh báo) | note(chỉ ghi chú)
            $table->float('interval_days')->nullable(); // Khoảng thời gian khuyến nghị giữa các bản ghi (nếu có)
            $table->string('unit', 50)->nullable(); // Đơn vị đo lường (nếu có  ví dụ: kg, cm, bpm)

            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('pet_id')->references('pet_id')->on('pets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_categories');
    }
};
