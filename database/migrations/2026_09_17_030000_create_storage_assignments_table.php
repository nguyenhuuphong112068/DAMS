<?php

use App\StorageLocation\ShelfManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vai trò "Quản lý kệ" và việc phân công phạm vi kho cho vai trò này:
     *  - storage_assignments: mỗi dòng giao cho một user một Kho, Kệ, Tầng hoặc Vị Trí.
     *  - storageAssignment.assign: quyền phân công, cấp cho Admin và "Người quản lý kho hồ sơ".
     */

    // Dữ liệu thật đang lưu tên vai trò là "quan lý" (thiếu dấu), nên gán theo cả hai cách viết.
    private const WAREHOUSE_MANAGER_ROLES = ['Admin', 'Người quản lý kho hồ sơ', 'Người quan lý kho hồ sơ'];

    // Quyền xem tối thiểu để người quản lý kệ mở được vị trí, sơ đồ kho và hồ sơ.
    private const SHELF_MANAGER_VIEWS = [
        'warehouse.view', 'shelf.view', 'tier.view', 'location.view', 'map.view', 'document.view',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('storage_assignments')) {
            Schema::create('storage_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('department_id');
                $table->string('scope_type', 20); // warehouse | shelf | tier | location
                $table->unsignedBigInteger('scope_id');
                $table->string('assigned_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'scope_type', 'scope_id']);
                $table->index(['department_id', 'scope_type', 'scope_id']);
            });
        }

        DB::table('roles')->updateOrInsert(['name' => ShelfManager::ROLE], [
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        DB::table('permissions')->updateOrInsert(
            ['name' => ShelfManager::PERMISSION],
            [
                'display_name'     => 'Phân Công Kho/Kệ/Tầng/Vị Trí Cho Quản Lý Kệ',
                'permission_group' => 2,
                'updated_at'       => now(),
                'created_at'       => now(),
            ]
        );

        $this->grant(self::WAREHOUSE_MANAGER_ROLES, [ShelfManager::PERMISSION]);
        $this->grant([ShelfManager::ROLE], self::SHELF_MANAGER_VIEWS);
    }

    private function grant(array $roleNames, array $permissionNames): void
    {
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_assignments');

        $permissionId = DB::table('permissions')->where('name', ShelfManager::PERMISSION)->value('id');
        if ($permissionId) {
            DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        $roleId = DB::table('roles')->where('name', ShelfManager::ROLE)->value('id');
        if ($roleId) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('user_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
