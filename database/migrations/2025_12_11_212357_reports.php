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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('report_id')->primary();

            $table->uuid('post_id');
            $table->uuid('reported_by');

            $table->string('reason');
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'resolved', 'rejected'])
                  ->default('pending');

            $table->uuid('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('post_id')
                ->references('post_id')
                ->on('posts')
                ->onDelete('cascade');

            $table->foreign('reported_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('handled_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
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
