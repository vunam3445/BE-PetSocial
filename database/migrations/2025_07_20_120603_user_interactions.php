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
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->uuid('interaction_id')->primary();
            $table->uuid('user_id');
            $table->uuid('post_id')->nullable(); // Cho phép null để lưu follow_pet
            $table->string('action_type', 20);    // like, comment, share, follow_pet, unfollow_pet
            $table->integer('weight')->default(1); // Trọng số để AI tính toán nhanh
            $table->json('metadata')->nullable();  // Lưu tags của bài viết hoặc pet_id
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users');
            $table->index(['user_id', 'created_at']); // Tối ưu cho AI query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
        // --- IGNORE ---
    }
};
