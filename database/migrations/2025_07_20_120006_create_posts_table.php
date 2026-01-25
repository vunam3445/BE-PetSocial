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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('post_id')->primary();
            $table->uuid('author_id');
            $table->uuid('pet_id')->nullable();
            $table->text('caption')->nullable();
            $table->string('visibility', 15)->default('public');
            $table->uuid('shared_post_id')->nullable();
            $table->uuid('group_id')->nullable()->constrained('groups', 'group_id')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();

            $table->foreign('author_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('groups');
            $table->index('author_id');
            $table->index('group_id');
            $table->index('pet_id');
            $table->index(['status', 'created_at']); // Tối ưu cho lấy bài viết đã duyệt, mới nhất
            $table->index('visibility');
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('shared_post_id')->references('post_id')->on('posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
        // --- IGNORE ---
    }
};
