<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lịch sử huỷ hồ sơ: hồ sơ bị xoá khỏi documents để trả vị trí về trống,
        // nên lưu lại bản chụp thông tin hồ sơ và vị trí tại thời điểm huỷ.
        if (!Schema::hasTable('document_disposals')) {
            Schema::create('document_disposals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('document_id');
                $table->string('code', 50);
                $table->string('name', 255)->nullable();
                $table->string('owner', 100)->nullable();
                $table->string('filepath', 512)->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('type_names', 500)->nullable();
                $table->date('expired_date')->nullable();
                $table->unsignedBigInteger('location_id')->nullable();
                $table->string('location_code', 50)->nullable();
                $table->string('location_name', 255)->nullable();
                $table->string('warehouse_name', 255)->nullable();
                $table->string('shelf_name', 255)->nullable();
                $table->string('tier_name', 255)->nullable();
                $table->text('reason');
                $table->string('document_created_by')->nullable();
                $table->timestamp('document_created_at')->nullable();
                $table->string('disposed_by')->nullable();
                $table->timestamp('disposed_at')->nullable();

                $table->index('department_id');
                $table->index('code');
                $table->index('disposed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_disposals');
    }
};
