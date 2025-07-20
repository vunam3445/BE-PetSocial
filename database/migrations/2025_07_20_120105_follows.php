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
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('follower_id');
            $table->uuid('followed_id');
            $table->timestamp('followed_at')->useCurrent();

            $table->primary(['follower_id', 'followed_id']);
            $table->foreign('follower_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('followed_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
        // --- IGNORE ---
    }
};
