<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_follows', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('pet_id');
            $table->timestamp('followed_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->primary(['user_id', 'pet_id']);

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('pet_id')->references('pet_id')->on('pets')->onDelete('cascade');
            $table->index('pet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_follows');
    }
};
