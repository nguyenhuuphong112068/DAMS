<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\RoomLayout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TRA CỨU VỊ TRÍ HỒ SƠ - TRANG CÔNG KHAI (không cần đăng nhập), port từ "Tra cứu tồn kho" của WMS
 *
 * Mở từ nút "Tra cứu hồ sơ" ở trang đăng nhập. Người dùng chọn MỘT bộ phận, gõ mã hồ sơ, tên hồ sơ, người
 * sở hữu hoặc mã vị trí (quét được mã QR trên nhãn hồ sơ bằng camera - QR mã hoá MÃ VỊ TRÍ); trang trả về
 * hồ sơ đang nằm ở Kho / Kệ / Tầng / Vị trí nào, xem được 3 kiểu: Thẻ, 3D từng kệ, Mặt bằng cả kho
 * (pages.shared.zone3d).
 *
 * Màn hình CHỈ ĐỌC. Để trang nhẹ (một bộ phận có hàng chục nghìn vị trí) và không lộ dữ liệu:
 *   - sơ đồ 3D / Mặt bằng chỉ nhận khung kho / kệ / tầng (số ô) + danh sách ô ĐANG CÓ hồ sơ (không kèm thông tin hồ sơ);
 *   - chỉ hồ sơ KHỚP từ khoá mới gửi chi tiết (mã, tên, người sở hữu, loại, hạn lưu trữ), tối đa MAX_MATCHES;
 *   - hồ sơ hạn chế (is_private) chỉ khớp theo đúng mã hồ sơ / mã vị trí, và không hiện tên / người sở hữu.
 */
class PublicDocumentLookupController extends Controller
{
    /** Số hồ sơ khớp tối đa trả về một lượt tra cứu. */
    public const MAX_MATCHES = 300;

    /** Hạn lưu trữ còn ít hơn số ngày này thì vị trí tô "sắp hết hạn". */
    private const WARN_DAYS = 30;

    public function index(Request $request)
    {
        $departments = DB::table('deparments')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'shortName']);

        $departmentId = (int) $request->query('department_id', 0);
        $keyword = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        $department = $departmentId ? $departments->firstWhere('id', $departmentId) : null;

        // Chỉ có một bộ phận thì chọn sẵn
        if (!$department && $departments->count() === 1 && !$request->has('department_id')) {
            $department = $departments->first();
        }

        return view('public.documentLookup', [
            'departments' => $departments,
            'departmentId' => $department ? (int) $department->id : 0,
            'department'  => $department,
            'keyword'     => $keyword,
            'result'      => $department && $request->has('department_id') ? $this->lookup((int) $department->id, $keyword) : null,
            'submitted'   => $request->has('department_id'),
            'maxMatches'  => self::MAX_MATCHES,
        ]);
    }

    private function lookup(int $departmentId, string $keyword): array
    {
        $zones = $this->zones($departmentId);
        $today = now()->startOfDay();

        // Mọi ô đang có hồ sơ của bộ phận (để vẽ thùng trên 3D / Mặt bằng) - chỉ toạ độ + tình trạng hạn lưu trữ
        $occupied = DB::table('documents as d')
            ->join('locations as l', 'l.id', '=', 'd.location_id')
            ->where('l.department_id', $departmentId)
            ->where('l.status_id', 1)
            ->whereNotNull('l.tier_id')
            ->whereNotNull('l.position')
            ->get(['l.id', 'l.tier_id', 'l.position', 'd.expired_date']);

        $nodes = [];
        foreach ($occupied as $row) {
            $key = 'L' . $row->id;
            $state = $this->state($row->expired_date, $today);
            // Dữ liệu cũ có thể còn ô dính nhiều hồ sơ: giữ tình trạng xấu nhất
            if (isset($nodes[$key]) && $this->rank($nodes[$key]['s']) >= $this->rank($state)) {
                continue;
            }
            $nodes[$key] = ['t' => (int) $row->tier_id, 'p' => (int) $row->position, 's' => $state];
        }

        $matches = $keyword === '' ? collect() : $this->matches($departmentId, $keyword);
        $truncated = $matches->count() > self::MAX_MATCHES;
        $matches = $matches->take(self::MAX_MATCHES);

        $types = DB::table('document_has_types as h')
            ->join('document_types as t', 't.id', '=', 'h.document_type_id')
            ->whereIn('h.document_id', $matches->pluck('id'))
            ->get(['h.document_id', 't.name'])
            ->groupBy('document_id')
            ->map(fn ($rows) => $rows->pluck('name')->implode(', '));

        $located = [];
        $unzoned = [];
        foreach ($matches as $row) {
            $item = $this->item($row, $types[$row->id] ?? '', $today);

            if (!$row->location_id || !$row->tier_id || !$row->position) {
                $unzoned[] = $item + ['location' => $row->location_code];
                continue;
            }

            $key = 'L' . $row->location_id;
            $located[$key]['row'] = $row;
            $located[$key]['items'][] = $item;
        }

        // Vị trí khớp: bổ sung chi tiết vào node để 3D / Mặt bằng / hộp chi tiết đọc chung
        $tree = [];
        foreach ($located as $key => $loc) {
            $row = $loc['row'];
            $path = $this->pathOf($row);
            $state = collect($loc['items'])->reduce(fn ($worst, $x) => $this->rank($x['state']) > $this->rank($worst) ? $x['state'] : $worst, 'ok');
            $search = mb_strtolower($row->location_code . ' ' . $path . ' ' . collect($loc['items'])
                ->map(fn ($x) => $x['code'] . ' ' . $x['name'] . ' ' . $x['owner'])->implode(' '));

            $nodes[$key] = [
                't'      => (int) $row->tier_id,
                'p'      => (int) $row->position,
                's'      => $state,
                'match'  => true,
                'code'   => $row->location_code,
                'path'   => $path,
                'search' => $search,
                'items'  => $loc['items'],
            ];

            $wName = $row->warehouse_name ?: 'Chưa gán kho';
            $sName = $row->shelf_name ?: 'Chưa gán kệ';
            $tName = $row->tier_name ?: 'Chưa gán tầng';
            $tree[$wName]['name'] = $wName;
            $tree[$wName]['code'] = $row->warehouse_code;
            $tree[$wName]['shelves'][$sName]['name'] = $sName;
            $tree[$wName]['shelves'][$sName]['code'] = $row->shelf_code;
            $tree[$wName]['shelves'][$sName]['tiers'][$tName]['name'] = $tName;
            $tree[$wName]['shelves'][$sName]['tiers'][$tName]['position'] = (int) $row->tier_position;
            $tree[$wName]['shelves'][$sName]['tiers'][$tName]['locations'][] = [
                'key'   => $key,
                'code'  => $row->location_code,
                'path'  => $path,
                'state' => $state,
                'items' => $loc['items'],
                'search' => $search,
            ];
        }

        // Sắp xếp kho / kệ theo mã (tự nhiên), tầng theo thứ tự, ô theo mã vị trí
        $natural = fn ($a, $b) => strnatcasecmp((string) ($a['code'] ?? $a['name']), (string) ($b['code'] ?? $b['name']));
        $tree = array_values($tree);
        usort($tree, $natural);
        $shelfCount = 0;
        foreach ($tree as &$wh) {
            $wh['shelves'] = array_values($wh['shelves']);
            usort($wh['shelves'], $natural);
            $shelfCount += count($wh['shelves']);
            foreach ($wh['shelves'] as &$shelf) {
                $shelf['tiers'] = array_values($shelf['tiers']);
                usort($shelf['tiers'], fn ($a, $b) => $a['position'] <=> $b['position']);
                foreach ($shelf['tiers'] as &$tier) {
                    usort($tier['locations'], fn ($a, $b) => strnatcasecmp((string) $a['code'], (string) $b['code']));
                }
                unset($tier);
            }
            unset($shelf);
        }
        unset($wh);

        return [
            'zones'     => $zones,
            'nodes'     => $nodes,
            'warehouses' => $tree,
            'unzoned'   => $unzoned,
            'truncated' => $truncated,
            'totals'    => [
                'documents'  => $matches->count(),
                'locations'  => count($located),
                'shelves'    => $shelfCount,
                'warehouses' => count($tree),
                'unzoned'    => count($unzoned),
                'occupied'   => count($nodes),
            ],
        ];
    }

    /**
     * Khung kho / kệ / tầng của bộ phận cho sơ đồ 3D / Mặt bằng: kho kèm mặt bằng (RoomLayout), kệ kèm vị trí đặt,
     * tầng kèm số ô xa nhất (max). Chỉ tính kệ / tầng / ô đang dùng.
     */
    private function zones(int $departmentId): array
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
     * Hồ sơ của bộ phận khớp từ khoá (không phân biệt dấu / hoa thường theo collation CSDL): mã hồ sơ, tên,
     * người sở hữu, mã vị trí. Hồ sơ hạn chế chỉ khớp theo mã hồ sơ / mã vị trí.
     */
    private function matches(int $departmentId, string $keyword)
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword) . '%';

        return DB::table('documents as d')
            ->leftJoin('locations as l', 'l.id', '=', 'd.location_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'l.warehouse_id')
            ->leftJoin('shelves as s', 's.id', '=', 'l.shelf_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tier_id')
            ->where('d.department_id', $departmentId)
            ->where(function ($q) use ($like) {
                $q->where('d.code', 'like', $like)
                    ->orWhere('l.code', 'like', $like)
                    ->orWhere(function ($open) use ($like) {
                        $open->where(fn ($p) => $p->whereNull('d.is_private')->orWhere('d.is_private', 0))
                            ->where(fn ($p) => $p->where('d.name', 'like', $like)->orWhere('d.owner', 'like', $like));
                    });
            })
            ->orderBy('d.code')
            ->limit(self::MAX_MATCHES + 1)
            ->get([
                'd.id', 'd.code', 'd.name', 'd.owner', 'd.is_private', 'd.expired_date',
                'd.location_id', 'l.code as location_code', 'l.tier_id', 'l.position',
                'w.code as warehouse_code', 'w.name as warehouse_name',
                's.code as shelf_code', 's.name as shelf_name',
                't.name as tier_name', 't.position as tier_position',
            ]);
    }

    /** Một hồ sơ để hiện trên thẻ / hộp chi tiết; hồ sơ hạn chế ẩn tên + người sở hữu + loại. */
    private function item($row, string $types, Carbon $today): array
    {
        $private = (bool) $row->is_private;

        return [
            'code'    => $row->code,
            'name'    => $private ? '' : (string) $row->name,
            'owner'   => $private ? '' : (string) $row->owner,
            'types'   => $private ? '' : $types,
            'private' => $private,
            'expiry'  => $row->expired_date ? Carbon::parse($row->expired_date)->format('d/m/Y') : '—',
            'state'   => $this->state($row->expired_date, $today),
        ];
    }

    private function pathOf($row): string
    {
        return ($row->warehouse_name ?: 'Chưa gán kho') . ' / ' . ($row->shelf_name ?: 'Chưa gán kệ') . ' / '
            . ($row->tier_name ?: 'Chưa gán tầng');
    }

    /** ok = còn hạn / không hạn, warn = hết hạn lưu trữ trong WARN_DAYS ngày tới, danger = đã quá hạn lưu trữ. */
    private function state($expiredDate, Carbon $today): string
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

    private function rank(string $state): int
    {
        return ['ok' => 0, 'warn' => 1, 'danger' => 2][$state] ?? 0;
    }
}
