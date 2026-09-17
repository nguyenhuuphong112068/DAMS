<?php

namespace App\StorageLocation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Người quản lý kệ chỉ phụ trách phần kho được giao. Phạm vi giao có thể là cả Kho, một Kệ,
// một Tầng hoặc từng Vị Trí; giao cấp trên thì bao trùm mọi cấp dưới nó.
class ShelfManager
{
    // Code nhận diện vai trò theo tên, nên đổi tên vai trò trong màn hình Nhóm Quyền phải sửa ở đây.
    public const ROLE = 'Quản lý kệ';
    public const PERMISSION = 'storageAssignment.assign';

    // Thứ tự từ rộng tới hẹp; khoá cũng là tiền tố cột khoá ngoại (warehouse_id, shelf_id, tier_id).
    public const SCOPES = [
        'warehouse' => ['label' => 'Kho',    'table' => 'warehouses'],
        'shelf'     => ['label' => 'Kệ',     'table' => 'shelves'],
        'tier'      => ['label' => 'Tầng',   'table' => 'tiers'],
        'location'  => ['label' => 'Vị Trí', 'table' => 'locations'],
    ];

    // Gộp role chính (userGroup) lẫn role gán qua user_role, giống user_has_any_role().
    // Không dùng user_has_any_role() vì hàm đó coi Admin là có mọi vai trò.
    public static function users()
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        return DB::table('user_management')
            ->where('isActive', 1)
            ->where(function ($query) use ($roleId) {
                $query->where('userGroup', self::ROLE)
                    ->orWhereIn('id', DB::table('user_role')->where('role_id', $roleId)->select('user_id'));
            })
            ->select('id', 'userName', 'fullName', 'deparment')
            ->orderBy('fullName');
    }

    public static function isShelfManager($userId): bool
    {
        return self::users()->where('id', $userId)->exists();
    }

    // Chuỗi cấp cha của từng nút, kể cả chính nó: [id => ['warehouse' => 1, 'shelf' => 5, ...]].
    // Nút không tồn tại hoặc không thuộc bộ phận đang chọn thì bị bỏ qua.
    public static function chains(string $type, array $ids, $departmentId): array
    {
        $rows = DB::table(self::SCOPES[$type]['table'])
            ->whereIn('id', $ids)
            ->where('department_id', $departmentId)
            ->get();

        $chains = [];
        foreach ($rows as $row) {
            $chain = [];
            foreach (array_keys(self::SCOPES) as $level) {
                if ($level === $type) {
                    $chain[$level] = (int) $row->id;
                    break;
                }
                $chain[$level] = (int) $row->{$level . '_id'};
            }
            $chains[(int) $row->id] = $chain;
        }

        return $chains;
    }

    // Gắn mã và đường dẫn "Kho › Kệ › Tầng › Vị Trí" cho từng phân công, tải theo lô để tránh N+1.
    public static function describe(Collection $assignments): Collection
    {
        $ids = array_fill_keys(array_keys(self::SCOPES), []);
        foreach ($assignments as $assignment) {
            $ids[$assignment->scope_type][] = $assignment->scope_id;
        }

        $rows = [];
        $rows['location'] = DB::table('locations')
            ->whereIn('id', $ids['location'])
            ->get(['id', 'code', 'warehouse_id', 'shelf_id', 'tier_id'])
            ->keyBy('id');

        $ids['tier'] = array_merge($ids['tier'], $rows['location']->pluck('tier_id')->all());
        $rows['tier'] = DB::table('tiers')
            ->whereIn('id', array_filter(array_unique($ids['tier'])))
            ->get(['id', 'code', 'name', 'warehouse_id', 'shelf_id'])
            ->keyBy('id');

        $ids['shelf'] = array_merge($ids['shelf'], $rows['location']->pluck('shelf_id')->all(), $rows['tier']->pluck('shelf_id')->all());
        $rows['shelf'] = DB::table('shelves')
            ->whereIn('id', array_filter(array_unique($ids['shelf'])))
            ->get(['id', 'code', 'warehouse_id'])
            ->keyBy('id');

        $ids['warehouse'] = array_merge(
            $ids['warehouse'],
            $rows['location']->pluck('warehouse_id')->all(),
            $rows['tier']->pluck('warehouse_id')->all(),
            $rows['shelf']->pluck('warehouse_id')->all()
        );
        $rows['warehouse'] = DB::table('warehouses')
            ->whereIn('id', array_filter(array_unique($ids['warehouse'])))
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        return $assignments->each(function ($assignment) use ($rows) {
            $type = $assignment->scope_type;
            $node = $rows[$type]->get($assignment->scope_id);

            $assignment->level = self::SCOPES[$type]['label'];
            $assignment->missing = !$node;
            $assignment->code = $node->code ?? '#' . $assignment->scope_id;

            if (!$node) {
                $assignment->path = $assignment->level . ' ' . $assignment->code . ' (không còn tồn tại)';
                return;
            }

            $parts = [];
            foreach (array_keys(self::SCOPES) as $level) {
                $current = $level === $type ? $node : $rows[$level]->get($node->{$level . '_id'} ?? null);
                if ($current) {
                    $parts[] = $level === 'tier' && $current->name
                        ? $current->name
                        : self::SCOPES[$level]['label'] . ' ' . $current->code;
                }
                if ($level === $type) {
                    break;
                }
            }
            $assignment->path = implode(' › ', $parts);
        });
    }
}
