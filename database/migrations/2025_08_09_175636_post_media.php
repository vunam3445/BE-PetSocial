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
        Schema::create('post_media', function (Blueprint $table) {
            $table->uuid('media_id')->primary();
            $table->uuid('post_id');
            $table->string('media_url'); // đường dẫn file
            $table->string('media_type', 10); // image | video
            $table->integer('order')->default(0); // để sắp xếp hiển thị
            $table->timestamps();

            $table->foreign('post_id')->references('post_id')->on('posts')->onDelete('cascade');
            $table->index(['post_id', 'order']); // Load media theo đúng thứ tự
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('post_media');
        Schema::enableForeignKeyConstraints();
    }
};
