<?php

namespace App\StorageLocation;

use App\Support\RoomLayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu cho sơ đồ "Thẻ | 3D | Mặt bằng" (pages/shared/zone3d) - dùng chung cho trang Tra cứu hồ sơ công khai
 * và các màn nội bộ (Cấu Trúc Kho, Sơ Đồ Kho, Phân Chia Kệ Quản Lý).
 *
 *   zones(): khung kho (kèm mặt bằng RoomLayout) / kệ (kèm vị trí đặt) / tầng (kèm số ô xa nhất) của một bộ phận
 *   nodes(): ô ĐANG CÓ hồ sơ { 'L<id>': { t, p, s[, code, items] } } - đường dẫn Kho / Kệ / Tầng và chuỗi lọc
 *            do trình duyệt tự dựng từ zones (pages/shared/zoneViews) để gói dữ liệu nhẹ.
 */
class ZoneView
{
    /** Hạn lưu trữ còn ít hơn số ngày này thì vị trí tô "sắp hết hạn". */
    public const WARN_DAYS = 30;

    public static function zones(int $departmentId): array
    {
        $warehouses = DB::table('warehouses')
            ->where('department_id', $departmentId)
            ->where('active', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'layout_width', 'layout_depth', 'layout_polygon', 'layout_door']);

        $shelves = DB::table('shelves')
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'warehouse_id', 'layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation']);

        $tiers = DB::table('tiers as t')
            ->join('locations as l', function ($join) {
                $join->on('l.tier_id', '=', 't.id')->where('l.status_id', 1)->whereNotNull('l.position');
            })
            ->whereIn('t.shelf_id', $shelves->pluck('id'))
            ->where('t.status_id', 1)
            ->whereNotNull('t.position')
            ->groupBy('t.id', 't.shelf_id', 't.code', 't.name', 't.position')
            ->orderBy('t.position')
            ->get(['t.id', 't.shelf_id', 't.code', 't.name', 't.position', DB::raw('MAX(l.position) as max')]);

        return [
            'warehouses' => RoomLayout::attach($warehouses)->map(fn ($w) => [
                'id'          => (int) $w->id,
                'code'        => $w->code,
                'name'        => $w->name,
                'layout_room' => $w->layout_room,
            ])->values(),
            'shelves' => $shelves->map(fn ($s) => [
                'id'              => (int) $s->id,
                'code'            => $s->code,
                'name'            => $s->name,
                'warehouse_id'    => (int) $s->warehouse_id,
                'layout_x'        => $s->layout_x,
                'layout_y'        => $s->layout_y,
                'layout_width'    => $s->layout_width,
                'layout_depth'    => $s->layout_depth,
                'layout_rotation' => $s->layout_rotation,
            ])->values(),
            'tiers' => $tiers->map(fn ($t) => [
                'id'       => (int) $t->id,
                'shelf_id' => (int) $t->shelf_id,
                'code'     => $t->code,
                'name'     => $t->name,
                'position' => (int) $t->position,
                'max'      => (int) $t->max,
            ])->values(),
        ];
    }

    /**
     * Ô đang có hồ sơ của bộ phận. $detail = true (màn nội bộ, đã đăng nhập): kèm mã vị trí và hồ sơ trong ô (hồ sơ hạn chế ẩn tên / người sở hữu); false (trang công khai): chỉ toạ độ + tình trạng.
     */
    public static function nodes(int $departmentId, bool $detail): array
    {
        $query = DB::table('documents as d')
            ->join('locations as l', 'l.id', '=', 'd.location_id')
            ->where('l.department_id', $departmentId)
            ->where('l.status_id', 1)
            ->whereNotNull('l.tier_id')
            ->whereNotNull('l.position');

        $columns = ['l.id', 'l.tier_id', 'l.position', 'd.expired_date'];
        if ($detail) {
            $columns = array_merge($columns, ['l.code as location_code', 'd.code', 'd.name', 'd.owner', 'd.is_private']);
        }

        $today = now()->startOfDay();
        $nodes = [];

        foreach ($query->orderBy('d.code')->get($columns) as $row) {
            $key = 'L' . $row->id;
            $state = self::state($row->expired_date, $today);

            if (!isset($nodes[$key])) {
                $nodes[$key] = ['t' => (int) $row->tier_id, 'p' => (int) $row->position, 's' => $state];
                if ($detail) {
                    $nodes[$key] += ['code' => $row->location_code, 'items' => []];
                }
            } elseif (self::rank($state) > self::rank($nodes[$key]['s'])) {
                // Dữ liệu cũ có thể còn ô dính nhiều hồ sơ: giữ tình trạng xấu nhất
                $nodes[$key]['s'] = $state;
            }

            if ($detail) {
                $private = (bool) $row->is_private;
                // Gọn nhẹ: chỉ gửi trường có giá trị (hơn 5.000 ô một lượt)
                $nodes[$key]['items'][] = array_filter([
                    'code'    => $row->code,
                    'name'    => $private ? null : $row->name,
                    'owner'   => $private ? null : $row->owner,
                    'private' => $private ?: null,
                    'expiry'  => $row->expired_date ? Carbon::parse($row->expired_date)->format('d/m/Y') : null,
                    'state'   => $state === 'ok' ? null : $state,
                ], fn ($v) => $v !== null && $v !== '');
            }
        }

        return $nodes;
    }

    /** ok = còn hạn / không hạn, warn = hết hạn lưu trữ trong WARN_DAYS ngày tới, danger = đã quá hạn lưu trữ. */
    public static function state($expiredDate, Carbon $today): string
    {
        if (!$expiredDate) {
            return 'ok';
        }

        $expiry = Carbon::parse($expiredDate)->startOfDay();

        if ($expiry->lt($today)) {
            return 'danger';
        }

        return $today->diffInDays($expiry) <= self::WARN_DAYS ? 'warn' : 'ok';
    }

    public static function rank(string $state): int
    {
        return ['ok' => 0, 'warn' => 1, 'danger' => 2][$state] ?? 0;
    }
}
