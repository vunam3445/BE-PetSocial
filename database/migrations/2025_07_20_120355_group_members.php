<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
              Schema::create('group_members', function (Blueprint $table) {
            $table->uuid('group_id');
            $table->uuid('user_id');
            $table->string('role', 50)->default('member');
            $table->timestamp('joined_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            
            $table->primary(['group_id', 'user_id']);
            $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index('user_id'); // Tìm xem user này tham gia những group nào
            $table->index(['group_id', 'role']); // Tìm admin của group
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
