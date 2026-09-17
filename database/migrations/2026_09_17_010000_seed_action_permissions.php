<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Danh mục quyền cho các nút thao tác (Thêm/Sửa/Xoá) trên các trang CRUD.
     * permission_group tương ứng với App\Http\Controllers\Pages\User\PermissionContoller::GROUPS.
     */
    private const PERMISSIONS = [
        // 1. Dữ Liệu Gốc
        ['name' => 'department.create', 'display_name' => 'Thêm Phòng Ban', 'permission_group' => 1],
        ['name' => 'department.update', 'display_name' => 'Sửa Phòng Ban', 'permission_group' => 1],
        ['name' => 'department.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Phòng Ban', 'permission_group' => 1],
        ['name' => 'status.create', 'display_name' => 'Thêm Trạng Thái', 'permission_group' => 1],
        ['name' => 'status.update', 'display_name' => 'Sửa Trạng Thái', 'permission_group' => 1],
        ['name' => 'status.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Trạng Thái', 'permission_group' => 1],
        ['name' => 'documentType.create', 'display_name' => 'Thêm Loại Tài Liệu', 'permission_group' => 1],
        ['name' => 'documentType.update', 'display_name' => 'Sửa Loại Tài Liệu', 'permission_group' => 1],
        ['name' => 'documentType.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Loại Tài Liệu', 'permission_group' => 1],

        // 2. Vị Trí Lưu Trữ
        ['name' => 'warehouse.create', 'display_name' => 'Thêm Kho', 'permission_group' => 2],
        ['name' => 'warehouse.update', 'display_name' => 'Sửa Kho', 'permission_group' => 2],
        ['name' => 'warehouse.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Kho', 'permission_group' => 2],
        ['name' => 'room.create', 'display_name' => 'Thêm Phòng', 'permission_group' => 2],
        ['name' => 'room.update', 'display_name' => 'Sửa Phòng', 'permission_group' => 2],
        ['name' => 'room.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Phòng', 'permission_group' => 2],
        ['name' => 'shelf.create', 'display_name' => 'Thêm Kệ', 'permission_group' => 2],
        ['name' => 'shelf.update', 'display_name' => 'Sửa Kệ', 'permission_group' => 2],
        ['name' => 'shelf.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Kệ', 'permission_group' => 2],
        ['name' => 'tier.create', 'display_name' => 'Thêm Tầng', 'permission_group' => 2],
        ['name' => 'tier.update', 'display_name' => 'Sửa Tầng', 'permission_group' => 2],
        ['name' => 'tier.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Tầng', 'permission_group' => 2],
        ['name' => 'location.create', 'display_name' => 'Thêm Vị Trí', 'permission_group' => 2],
        ['name' => 'location.update', 'display_name' => 'Sửa Vị Trí', 'permission_group' => 2],
        ['name' => 'location.deActive', 'display_name' => 'Vô Hiệu/Kích Hoạt Vị Trí', 'permission_group' => 2],
        // 4. Quản Lý Tài Liệu
        ['name' => 'document.create', 'display_name' => 'Thêm Tài Liệu', 'permission_group' => 4],
        ['name' => 'document.update', 'display_name' => 'Sửa Tài Liệu', 'permission_group' => 4],
        ['name' => 'document.dispose', 'display_name' => 'Huỷ Tài Liệu', 'permission_group' => 4],

        // 7. Phân Quyền & Người Dùng
        ['name' => 'user.create', 'display_name' => 'Thêm Người Dùng', 'permission_group' => 7],
        ['name' => 'user.update', 'display_name' => 'Sửa Người Dùng', 'permission_group' => 7],
        ['name' => 'user.deActive', 'display_name' => 'Vô Hiệu Người Dùng', 'permission_group' => 7],
        ['name' => 'role.create', 'display_name' => 'Thêm Nhóm Quyền', 'permission_group' => 7],
        ['name' => 'role.update', 'display_name' => 'Đổi Tên Nhóm Quyền', 'permission_group' => 7],
        ['name' => 'role.delete', 'display_name' => 'Xoá Nhóm Quyền', 'permission_group' => 7],
        ['name' => 'role.assignPermission', 'display_name' => 'Gán Quyền Cho Nhóm Quyền', 'permission_group' => 7],
        ['name' => 'permission.create', 'display_name' => 'Thêm Quyền', 'permission_group' => 7],
        ['name' => 'permission.update', 'display_name' => 'Sửa Quyền', 'permission_group' => 7],
        ['name' => 'permission.delete', 'display_name' => 'Xoá Quyền', 'permission_group' => 7],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'permission_group' => $permission['permission_group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 'name'))->delete();
    }
};
