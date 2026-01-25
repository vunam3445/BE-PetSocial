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

            // 1. Cột quan trọng cho Đa hình
            $table->uuid('reportable_id');   // ID của đối tượng bị báo cáo (Post ID, User ID,...)
            $table->string('reportable_type'); // Loại đối tượng (App\Models\Post, App\Models\User,...)

            $table->uuid('reported_by'); // Người thực hiện báo cáo

            $table->string('reason');
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'resolved', 'dismissed'])
                ->default('pending');

            $table->uuid('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index cho tốc độ truy vấn đa hình
            $table->index(['reportable_id', 'reportable_type']);
            $table->index('status');
            // Foreign keys
            $table->foreign('reported_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('handled_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
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
