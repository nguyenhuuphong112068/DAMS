<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tạo bảng tiers (Tầng)
        if (!Schema::hasTable('tiers')) {
            Schema::create('tiers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('shelf_id')->nullable();
                $table->unsignedBigInteger('status_id')->default(1)->nullable();
                $table->boolean('active')->default(true);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 2. Thêm cột tier_id vào bảng locations
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'tier_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedBigInteger('tier_id')->nullable()->after('shelf_id');
            });
        }

        // 3. Đảm bảo warehouse_id trên shelves được điền nếu trước đó chỉ có room_id
        if (Schema::hasTable('shelves') && Schema::hasTable('rooms')) {
            $shelvesWithoutWh = DB::table('shelves')
                ->whereNull('warehouse_id')
                ->whereNotNull('room_id')
                ->get();

            foreach ($shelvesWithoutWh as $sh) {
                $rm = DB::table('rooms')->where('id', $sh->room_id)->first();
                if ($rm && $rm->warehouse_id) {
                    DB::table('shelves')->where('id', $sh->id)->update([
                        'warehouse_id' => $rm->warehouse_id
                    ]);
                }
            }
        }

        // 4. Khởi tạo dữ liệu tầng ban đầu cho các kệ hiện có và gán cho locations
        if (Schema::hasTable('shelves') && Schema::hasTable('tiers')) {
            $shelves = DB::table('shelves')->get();
            foreach ($shelves as $shelf) {
                $tierCode = 'T1-' . $shelf->code;
                $existingTier = DB::table('tiers')->where('code', $tierCode)->first();
                $tierId = null;

                if (!$existingTier) {
                    $tierId = DB::table('tiers')->insertGetId([
                        'code'          => $tierCode,
                        'name'          => 'Tầng 1 (' . $shelf->name . ')',
                        'department_id' => $shelf->department_id,
                        'warehouse_id'  => $shelf->warehouse_id,
                        'shelf_id'      => $shelf->id,
                        'status_id'     => 1,
                        'active'        => 1,
                        'created_by'    => 'System',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                } else {
                    $tierId = $existingTier->id;
                }

                // Cập nhật locations thuộc shelf này gán vào tier tương ứng
                if (Schema::hasTable('locations') && $tierId) {
                    DB::table('locations')
                        ->where('shelf_id', $shelf->id)
                        ->whereNull('tier_id')
                        ->update(['tier_id' => $tierId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'tier_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('tier_id');
            });
        }

        Schema::dropIfExists('tiers');
    }
};
