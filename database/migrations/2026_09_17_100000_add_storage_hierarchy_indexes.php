<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mọi truy vấn theo cấp cha (vị trí của một tầng, tầng của một kệ, ô của một kho) đều lọc
// theo các khoá ngoại này, nhưng bảng chưa có index nên mỗi lần quét toàn bộ 60k+ vị trí.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['tier_id', 'position'], 'locations_tier_position_index');
            $table->index('shelf_id', 'locations_shelf_id_index');
            $table->index('warehouse_id', 'locations_warehouse_id_index');
        });

        Schema::table('tiers', function (Blueprint $table) {
            $table->index(['shelf_id', 'position'], 'tiers_shelf_position_index');
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->index('warehouse_id', 'shelves_warehouse_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_tier_position_index');
            $table->dropIndex('locations_shelf_id_index');
            $table->dropIndex('locations_warehouse_id_index');
        });

        Schema::table('tiers', function (Blueprint $table) {
            $table->dropIndex('tiers_shelf_position_index');
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->dropIndex('shelves_warehouse_id_index');
        });
    }
};
