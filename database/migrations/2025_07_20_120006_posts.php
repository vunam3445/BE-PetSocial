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
            $table->text('caption')->nullable();
            $table->text('media_url')->nullable();
            $table->string('media_type', 10)->nullable();
            $table->string('visibility', 15)->default('public');
            $table->uuid('shared_post_id')->nullable();
            $table->uuid('group_id')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('user_id')->on('users');
            $table->foreign('group_id')->references('group_id')->on('groups');
        });
        Schema::table('posts', function (Blueprint $table) {
        $table->foreign('shared_post_id')->references('post_id')->on('posts');
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
