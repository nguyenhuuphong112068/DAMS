<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_has_permission')) {
    function user_has_permission($userId, $permissionName, $typeReturn)
    {
        if (empty($userId)) {
            $result = false;
        } elseif (user_has_any_role($userId, ['Admin'])) {
            // Admin luôn có toàn quyền, không phụ thuộc dữ liệu gán trong role_permission.
            $result = true;
        } else {
            static $userPermissions = [];
            if (! isset($userPermissions[$userId])) {
                $userPermissions[$userId] = DB::table('permissions')
                    ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
                    ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
                    ->where('user_role.user_id', $userId)
                    ->pluck('permissions.name')
                    ->flip()
                    ->all();
            }

            $result = isset($userPermissions[$userId][$permissionName]);
        }

        if ($typeReturn == "boolean") {
            return $result;
        } elseif ($typeReturn == "disabled") {
            return $result ? "" : "disabled";
        }

        return $result;
    }
}

if (! function_exists('user_has_any_role')) {
    /**
     * Kiểm tra user có thuộc một trong các role được liệt kê không.
     * Role 'Admin' luôn được coi là có toàn quyền (bỏ qua $roleNames).
     * Gộp cả role chính (user_management.userGroup) lẫn các role gán qua user_role/roles
     * để tương thích với dữ liệu cũ (chỉ có userGroup, chưa gán user_role).
     */
    function user_has_any_role($userId, array $roleNames): bool
    {
        if (empty($userId)) {
            return false;
        }

        static $userRoles = [];
        if (! isset($userRoles[$userId])) {
            $primaryGroup = DB::table('user_management')->where('id', $userId)->value('userGroup');

            $assignedRoles = DB::table('user_role')
                ->join('roles', 'roles.id', '=', 'user_role.role_id')
                ->where('user_role.user_id', $userId)
                ->pluck('roles.name')
                ->all();

            $userRoles[$userId] = array_filter(array_merge([$primaryGroup], $assignedRoles));
        }

        if (in_array('Admin', $userRoles[$userId], true)) {
            return true;
        }

        return count(array_intersect($roleNames, $userRoles[$userId])) > 0;
    }
}
