<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mặt bằng kho (Vị Trí Lưu Trữ > Cấu Trúc Kho > Mặt bằng kho) - port từ WMS. Đơn vị mét.
 *
 *   warehouses.layout_width / layout_depth : khung bao của phòng (chiều ngang / chiều sâu)
 *   warehouses.layout_polygon              : các góc phòng [[x,y],...] (JSON), null = hình chữ nhật width × depth
 *   warehouses.layout_door                 : cửa ra vào {edge, offset, width} (JSON), null = chưa có cửa
 *   shelves.layout_x / layout_y            : TÂM kệ trên mặt bằng, gốc là góc trên-trái; null = chưa đặt
 *   shelves.layout_width / layout_depth    : bề ngang (mặt trước) và bề sâu của kệ
 *   shelves.layout_rotation                : hướng mặt trước 0 = xuống, 90 = trái, 180 = lên, 270 = phải
 *
 * Không đụng tới warehouses.layout_path / layout_name (file PDF sơ đồ kho đính kèm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'layout_width')) {
                $table->decimal('layout_width', 6, 2)->nullable();
                $table->decimal('layout_depth', 6, 2)->nullable();
                $table->text('layout_polygon')->nullable();
                $table->string('layout_door', 120)->nullable();
            }
        });

        Schema::table('shelves', function (Blueprint $table) {
            if (!Schema::hasColumn('shelves', 'layout_x')) {
                $table->decimal('layout_x', 6, 2)->nullable();
                $table->decimal('layout_y', 6, 2)->nullable();
                $table->decimal('layout_width', 5, 2)->nullable();
                $table->decimal('layout_depth', 5, 2)->nullable();
                $table->unsignedSmallInteger('layout_rotation')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->dropColumn(['layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['layout_width', 'layout_depth', 'layout_polygon', 'layout_door']);
        });
    }
};
