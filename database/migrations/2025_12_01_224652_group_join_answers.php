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
        Schema::create('group_join_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('request_id');  // 🔥 Thêm dòng này
            $table->uuid('user_id');
            $table->text('answer');
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('group_join_questions')->onDelete('cascade');
            $table->foreign('request_id')->references('id')->on('group_join_requests')->onDelete('cascade'); // 🔥 Thêm FK
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
