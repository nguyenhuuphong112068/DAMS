<?php

namespace App\StorageLocation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Giao / bỏ giao phạm vi cho người quản lý kệ và luôn giữ dữ liệu ở dạng gọn nhất:
//  - Giao đủ mọi con đang dùng của một nút thì gộp lên thành giao cả nút cha.
//  - Bỏ một phần bên trong phạm vi đã giao rộng hơn thì tách phạm vi đó ra thành các phần còn lại.
// Nhờ vậy giao cả Kho vẫn là một dòng, và kệ tạo thêm về sau cũng tự thuộc về người đó.
class ShelfAssignment
{
    private int $userId;
    private int $departmentId;
    private string $actor;

    // Trạng thái phân công của người đang sửa, khoá "cấp:id". Kéo chọn một vùng có thể
    // chạm hàng trăm ô, nên xử lý trong bộ nhớ rồi ghi xuống theo lô một lần.
    private array $owned = [];
    private array $added = [];
    private array $removed = [];

    private function __construct(int $userId, int $departmentId, string $actor)
    {
        $this->userId = $userId;
        $this->departmentId = $departmentId;
        $this->actor = $actor;

        DB::table('storage_assignments')
            ->where('user_id', $userId)
            ->get(['scope_type', 'scope_id'])
            ->each(function ($row) {
                $this->owned[$row->scope_type . ':' . $row->scope_id] = true;
            });
    }

    private static function levels(): array
    {
        return array_keys(ShelfManager::SCOPES);
    }

    private static function childLevel(string $type): ?string
    {
        $levels = self::levels();

        return $levels[array_search($type, $levels) + 1] ?? null;
    }

    private static function parentLevel(string $type): ?string
    {
        $levels = self::levels();
        $index = array_search($type, $levels);

        return $index > 0 ? $levels[$index - 1] : null;
    }

    private static function activeChildren(string $type, int $id): Collection
    {
        return DB::table(ShelfManager::SCOPES[self::childLevel($type)]['table'])
            ->where($type . '_id', $id)
            ->where('status_id', 1)
            ->pluck('id')
            ->map(fn ($childId) => (int) $childId);
    }

    private function has(string $type, int $id): bool
    {
        $key = $type . ':' . $id;

        return isset($this->added[$key]) || (isset($this->owned[$key]) && !isset($this->removed[$key]));
    }

    private function add(string $type, int $id): void
    {
        $key = $type . ':' . $id;
        unset($this->removed[$key]);
        if (!isset($this->owned[$key])) {
            $this->added[$key] = true;
        }
    }

    private function remove(string $type, int $id): void
    {
        $key = $type . ':' . $id;
        unset($this->added[$key]);
        if (isset($this->owned[$key])) {
            $this->removed[$key] = true;
        }
    }

    // Bỏ mọi phân công nằm bên trong một nút (không gồm chính nút đó).
    private function removeInside(string $type, int $id): bool
    {
        $changed = false;
        $levels = self::levels();
        $held = [];
        foreach ($this->owned + $this->added as $key => $_) {
            [$level, $nodeId] = explode(':', $key);
            if ($this->has($level, (int) $nodeId)) {
                $held[$level][] = (int) $nodeId;
            }
        }

        foreach (array_slice($levels, array_search($type, $levels) + 1) as $level) {
            if (empty($held[$level])) {
                continue;
            }

            $inside = DB::table(ShelfManager::SCOPES[$level]['table'])
                ->whereIn('id', $held[$level])
                ->where($type . '_id', $id)
                ->pluck('id');

            foreach ($inside as $nodeId) {
                $this->remove($level, (int) $nodeId);
                $changed = true;
            }
        }

        return $changed;
    }

    private function flush(): void
    {
        $removedByType = [];
        foreach ($this->removed as $key => $_) {
            [$type, $id] = explode(':', $key);
            $removedByType[$type][] = (int) $id;
            unset($this->owned[$key]);
        }

        foreach ($removedByType as $type => $ids) {
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table('storage_assignments')
                    ->where('user_id', $this->userId)
                    ->where('scope_type', $type)
                    ->whereIn('scope_id', $chunk)
                    ->delete();
            }
        }

        $now = now();
        $rows = [];
        foreach ($this->added as $key => $_) {
            [$type, $id] = explode(':', $key);
            $rows[] = [
                'user_id'       => $this->userId,
                'department_id' => $this->departmentId,
                'scope_type'    => $type,
                'scope_id'      => (int) $id,
                'assigned_by'   => $this->actor,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
            $this->owned[$key] = true;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('storage_assignments')->insertOrIgnore($chunk);
        }

        $this->added = [];
        $this->removed = [];
    }

    // Trả về số nút được giao mới (không tính nút vốn đã nằm trong phạm vi giao).
    public static function assign(int $userId, int $departmentId, string $type, array $ids, string $actor): int
    {
        return DB::transaction(function () use ($userId, $departmentId, $type, $ids, $actor) {
            $ledger = new self($userId, $departmentId, $actor);
            $parentType = self::parentLevel($type);
            $parents = [];
            $count = 0;

            foreach (ShelfManager::chains($type, $ids, $departmentId) as $chain) {
                $covered = false;
                foreach ($chain as $level => $nodeId) {
                    $covered = $covered || $ledger->has($level, $nodeId);
                }
                if ($covered) {
                    continue;
                }

                if ($type !== 'location') {
                    $ledger->removeInside($type, $chain[$type]);
                }
                $ledger->add($type, $chain[$type]);
                $count++;

                if ($parentType) {
                    $parents[$chain[$parentType]] = $chain;
                }
            }

            // Gộp dần lên trên: tầng đủ ô -> cả tầng, kệ đủ tầng -> cả kệ, kho đủ kệ -> cả kho.
            $level = $type;
            while ($parents && $parentType) {
                $next = [];
                $grandType = self::parentLevel($parentType);

                foreach ($parents as $parentId => $chain) {
                    $children = self::activeChildren($parentType, $parentId);
                    $full = $children->isNotEmpty()
                        && $children->every(fn ($childId) => $ledger->has($level, $childId));

                    if ($full) {
                        $ledger->removeInside($parentType, $parentId);
                        $ledger->add($parentType, $parentId);

                        if ($grandType) {
                            $next[$chain[$grandType]] = $chain;
                        }
                    }
                }

                $parents = $next;
                $level = $parentType;
                $parentType = $grandType;
            }

            $ledger->flush();

            return $count;
        });
    }

    // Trả về số nút thực sự bị bỏ giao.
    public static function unassign(int $userId, int $departmentId, string $type, array $ids, string $actor): int
    {
        return DB::transaction(function () use ($userId, $departmentId, $type, $ids, $actor) {
            $ledger = new self($userId, $departmentId, $actor);
            $count = 0;

            foreach (ShelfManager::chains($type, $ids, $departmentId) as $chain) {
                // Phạm vi rộng nhất đang bao trùm nút này (nếu có).
                $covering = null;
                foreach ($chain as $level => $nodeId) {
                    if ($level !== $type && $ledger->has($level, $nodeId)) {
                        $covering = $level;
                        break;
                    }
                }

                $changed = $type !== 'location' && $ledger->removeInside($type, $chain[$type]);

                if ($ledger->has($type, $chain[$type])) {
                    $ledger->remove($type, $chain[$type]);
                    $changed = true;
                }

                if ($covering) {
                    // Tách phạm vi bao trùm: giữ lại mọi nhánh anh em dọc đường đi xuống nút bị bỏ.
                    $ledger->remove($covering, $chain[$covering]);

                    for ($level = $covering; $level !== $type; $level = $child) {
                        $child = self::childLevel($level);
                        foreach (self::activeChildren($level, $chain[$level]) as $siblingId) {
                            if ($siblingId !== $chain[$child]) {
                                $ledger->add($child, $siblingId);
                            }
                        }
                    }
                    $changed = true;
                }

                $count += $changed ? 1 : 0;
            }

            $ledger->flush();

            return $count;
        });
    }

    // Số vị trí đang dùng mà mỗi người phụ trách, gom theo một cột của bảng locations.
    // Tách làm bốn nhánh theo cấp để mỗi nhánh đi đúng index, rồi UNION để bỏ trùng.
    public static function coverage(int $departmentId, string $column, $value, string $groupBy): Collection
    {
        $union = null;

        foreach (self::levels() as $level) {
            $key = $level === 'location' ? 'id' : $level . '_id';

            $query = DB::table('storage_assignments as a')
                ->join('locations as l', 'l.' . $key, '=', 'a.scope_id')
                ->where('a.scope_type', $level)
                ->where('a.department_id', $departmentId)
                ->where('l.status_id', 1)
                ->where('l.' . $column, $value)
                ->select('a.user_id', 'l.id as location_id', 'l.' . $groupBy . ' as group_id');

            $union = $union ? $union->union($query) : $query;
        }

        return DB::query()
            ->fromSub($union, 'c')
            ->select('group_id', 'user_id', DB::raw('COUNT(*) as covered'))
            ->groupBy('group_id', 'user_id')
            ->get();
    }

    // Phân công trực tiếp của một người, dạng {cấp: [id...]} để giao diện tự suy ra phần kế thừa.
    public static function directMap(int $userId, int $departmentId): array
    {
        $map = array_fill_keys(self::levels(), []);

        DB::table('storage_assignments')
            ->where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->get(['scope_type', 'scope_id'])
            ->each(function ($row) use (&$map) {
                $map[$row->scope_type][] = (int) $row->scope_id;
            });

        return $map;
    }
}
