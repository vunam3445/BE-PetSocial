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
                Schema::create('messages', function (Blueprint $table) {
            $table->uuid('message_id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('sender_id');
            $table->text('content')->nullable();
            $table->text('media_url')->nullable();
            $table->string('message_type', 20)->default('text');
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('reply_to_id')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('conversation_id')->references('conversation_id')->on('conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('user_id')->on('users');
            $table->foreign('reply_to_id')->references('message_id')->on('messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
