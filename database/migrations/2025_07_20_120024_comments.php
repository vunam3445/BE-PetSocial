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
        Schema::create('comments', function (Blueprint $table) {
    $table->uuid('comment_id')->primary();
    $table->uuid('post_id');
    $table->uuid('user_id');
    $table->uuid('parent_id')->nullable(); 
    $table->text('content');
    $table->timestamps();

    $table->foreign('post_id')->references('post_id')->on('posts')->onDelete('cascade');
    $table->foreign('user_id')->references('user_id')->on('users');
});

// 👇 Tách riêng self-reference cho chắc chắn
Schema::table('comments', function (Blueprint $table) {
    $table->foreign('parent_id')->references('comment_id')->on('comments')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
