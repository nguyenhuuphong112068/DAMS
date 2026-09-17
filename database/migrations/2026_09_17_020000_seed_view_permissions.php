<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Danh mục quyền xem (View Permissions) cho thanh menu LeftNAV và các chức năng.
     * permission_group tương ứng với App\Http\Controllers\Pages\User\PermissionContoller::GROUPS.
     */
    private const VIEW_PERMISSIONS = [
        // 1. Dữ Liệu Gốc
        ['name' => 'department.view',   'display_name' => 'Xem Phòng Ban',          'permission_group' => 1],
        ['name' => 'status.view',       'display_name' => 'Xem Trạng Thái',         'permission_group' => 1],
        ['name' => 'documentType.view', 'display_name' => 'Xem Loại Tài Liệu',      'permission_group' => 1],
        ['name' => 'department.switch', 'display_name' => 'Chuyển Bộ Phận',         'permission_group' => 1],

        // 2. Vị Trí Lưu Trữ
        ['name' => 'warehouse.view',    'display_name' => 'Xem Kho',                'permission_group' => 2],
        ['name' => 'shelf.view',        'display_name' => 'Xem Kệ',                 'permission_group' => 2],
        ['name' => 'tier.view',         'display_name' => 'Xem Tầng',               'permission_group' => 2],
        ['name' => 'location.view',     'display_name' => 'Xem Vị Trí',             'permission_group' => 2],

        // 3. Sơ Đồ Kho
        ['name' => 'map.view',          'display_name' => 'Xem Sơ Đồ Kho',          'permission_group' => 3],

        // 4. Quản Lý Lưu Trữ
        ['name' => 'document.view',     'display_name' => 'Xem Quản Lý Lưu Trữ',   'permission_group' => 4],

        // 5. Luân Chuyển Hồ Sơ
        ['name' => 'routing.view',      'display_name' => 'Xem Luân Chuyển Hồ Sơ',  'permission_group' => 5],

        // 7. Phân Quyền & Người Dùng
        ['name' => 'admin.view',        'display_name' => 'Xem Tab Quản Trị',       'permission_group' => 7],
        ['name' => 'user.view',         'display_name' => 'Xem Người Dùng',         'permission_group' => 7],
        ['name' => 'role.view',         'display_name' => 'Xem Nhóm Quyền',         'permission_group' => 7],
        ['name' => 'permission.view',   'display_name' => 'Xem Quyền',              'permission_group' => 7],
        ['name' => 'auditTrail.view',   'display_name' => 'Xem Audit Trail',        'permission_group' => 7],
    ];

    public function up(): void
    {
        $insertedIds = [];

        foreach (self::VIEW_PERMISSIONS as $p) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $p['name']],
                [
                    'display_name'     => $p['display_name'],
                    'permission_group' => $p['permission_group'],
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );

            $id = DB::table('permissions')->where('name', $p['name'])->value('id');
            if ($id) {
                $insertedIds[$p['name']] = $id;
            }
        }

        // 1. Gán toàn bộ quyền mới cho vai trò Admin (id = 1 hoặc role có tên 'Admin')
        $adminRoles = DB::table('roles')->where('name', 'Admin')->pluck('id')->all();
        foreach ($adminRoles as $adminRoleId) {
            foreach ($insertedIds as $permId) {
                $exists = DB::table('role_permission')
                    ->where('role_id', $adminRoleId)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('role_permission')->insert([
                        'role_id'       => $adminRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // 2. Gán quyền xem dữ liệu gốc, vị trí lưu trữ, sơ đồ kho, tài liệu cho "Người quản lý kho hồ sơ" và "Người xem"
        $operationalRoles = DB::table('roles')
            ->whereIn('name', ['Người quản lý kho hồ sơ', 'Người xem'])
            ->pluck('id')
            ->all();

        $operationalPermNames = [
            'department.view', 'status.view', 'documentType.view',
            'warehouse.view', 'shelf.view', 'tier.view', 'location.view',
            'map.view', 'document.view',
        ];

        foreach ($operationalRoles as $roleId) {
            foreach ($operationalPermNames as $permName) {
                if (isset($insertedIds[$permName])) {
                    $permId = $insertedIds[$permName];
                    $exists = DB::table('role_permission')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permId)
                        ->exists();
                    if (!$exists) {
                        DB::table('role_permission')->insert([
                            'role_id'       => $roleId,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $permNames = array_column(self::VIEW_PERMISSIONS, 'name');
        $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id')->all();

        if (!empty($permIds)) {
            DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }
    }
};
