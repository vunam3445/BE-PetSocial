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
                Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('notification_id');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'notification_id']);
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('notification_id')->references('notification_id')->on('notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
