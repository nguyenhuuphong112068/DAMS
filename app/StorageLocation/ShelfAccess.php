<?php

namespace App\StorageLocation;

use Illuminate\Support\Facades\DB;

// Quyền thao tác trên vị trí theo phân công quản lý kệ:
//  - Vị trí đã giao cho người quản lý kệ: chỉ người được giao và cấp trên thao tác được.
//  - Vị trí chưa giao cho ai: giữ nguyên như trước, theo quyền chức năng.
// Cấp trên là người có quyền phân công (Admin, Người quản lý kho hồ sơ).
class ShelfAccess
{
    public static function isSupervisor($userId): bool
    {
        return user_has_permission($userId, ShelfManager::PERMISSION, 'boolean');
    }

    // Người phụ trách từng vị trí: [location_id => [user_id, ...]]. Vị trí chưa giao không có khoá.
    // $column/$value lọc trên bảng locations, vd ('shelf_id', 12) hoặc ('id', [1, 2, 3]).
    public static function owners(string $column, $value): array
    {
        $rows = ShelfAssignment::coveredLocations(function ($query) use ($column, $value) {
            is_array($value)
                ? $query->whereIn('l.' . $column, $value)
                : $query->where('l.' . $column, $value);
        })->get();

        $owners = [];
        foreach ($rows as $row) {
            $owners[(int) $row->location_id][] = (int) $row->user_id;
        }

        return $owners;
    }

    public static function names(array $owners): array
    {
        $ids = array_unique(array_merge([], ...array_values($owners)));

        return DB::table('user_management')->whereIn('id', $ids)->pluck('fullName', 'id')->all();
    }

    public static function allows($userId, array $owners, int $locationId): bool
    {
        return empty($owners[$locationId]) || in_array((int) $userId, $owners[$locationId], true);
    }

    // Trả về câu báo lỗi nếu người dùng không được thao tác trên một trong các vị trí, null nếu được.
    public static function denied($userId, array $locationIds): ?string
    {
        $locationIds = array_values(array_filter(array_map('intval', $locationIds)));
        if (!$locationIds || self::isSupervisor($userId)) {
            return null;
        }

        $owners = self::owners('id', $locationIds);
        foreach ($locationIds as $locationId) {
            if (self::allows($userId, $owners, $locationId)) {
                continue;
            }

            $code = DB::table('locations')->where('id', $locationId)->value('code');
            $names = array_intersect_key(self::names($owners), array_flip($owners[$locationId]));

            return 'Vị trí ' . $code . ' thuộc kệ do ' . implode(', ', $names)
                . ' phụ trách. Chỉ người phụ trách hoặc cấp quản lý kho mới được thao tác.';
        }

        return null;
    }

    // Lọc truy vấn trên bảng locations (bí danh $alias) chỉ còn vị trí người dùng được thao tác.
    public static function scopeManageable($query, $userId, string $alias = 'locations')
    {
        if (self::isSupervisor($userId)) {
            return $query;
        }

        $covering = function ($sub) use ($alias) {
            $sub->select(DB::raw(1))
                ->from('storage_assignments as a')
                ->where(function ($q) use ($alias) {
                    $q->where(fn ($x) => $x->where('a.scope_type', 'warehouse')->whereColumn('a.scope_id', $alias . '.warehouse_id'))
                        ->orWhere(fn ($x) => $x->where('a.scope_type', 'shelf')->whereColumn('a.scope_id', $alias . '.shelf_id'))
                        ->orWhere(fn ($x) => $x->where('a.scope_type', 'tier')->whereColumn('a.scope_id', $alias . '.tier_id'))
                        ->orWhere(fn ($x) => $x->where('a.scope_type', 'location')->whereColumn('a.scope_id', $alias . '.id'));
                });
        };

        return $query->where(function ($q) use ($covering, $userId) {
            $q->whereNotExists($covering)
                ->orWhereExists(function ($sub) use ($covering, $userId) {
                    $covering($sub);
                    $sub->where('a.user_id', $userId);
                });
        });
    }
}
