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
        Schema::create('group_join_questions', function (Blueprint $table) {
    $table->id();
    $table->uuid('group_id');
    $table->text('question'); // Nội dung câu hỏi
    $table->timestamps();

    $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('cascade');
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
