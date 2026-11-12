<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\RoomLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * VỊ TRÍ LƯU TRỮ - MẶT BẰNG KHO (port từ "Định Khu > Mặt bằng kho/phòng" của WMS)
 *
 * Mở từ màn Cấu Trúc Kho (nút "Mặt bằng kho" khi đang xem danh sách kệ của một kho): vẽ hình dạng phòng
 * (đa giác), đặt cửa ra vào, kéo thả từng kệ vào đúng chỗ trong phòng; bên phải là cả kho dựng 3D.
 * Đơn vị mét. Phòng lưu dạng đa giác (App\Support\RoomLayout), kệ lưu tâm + kích thước + hướng mặt trước.
 *
 * DAMS không có cấp "Cột" như WMS: mỗi kệ được trả về như MỘT cột duy nhất để dùng chung bộ vẽ 3D
 * (pages/shared/shelf3d), tầng 1 nằm dưới cùng, mỗi ô vị trí là một ô ngang (sâu 1 dãy).
 */
class FloorPlanController extends Controller
{
    /* Mặt bằng (m): phòng tối đa, kệ nhỏ / lớn nhất. */
    private const MAX_ROOM = 200;
    private const MIN_SHELF = 0.2;
    private const MAX_SHELF = 50;

    /** Hướng mặt trước của kệ trên sơ đồ, xoay theo chiều kim đồng hồ. */
    private const ROTATIONS = [0, 90, 180, 270];

    private function departmentId(): int
    {
        return (int) session('user')['selected_department_id'];
    }

    private function warehouse($id)
    {
        return DB::table('warehouses')
            ->where('id', (int) $id)
            ->where('department_id', $this->departmentId())
            ->first();
    }

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }

    /**
     * Mặt bằng một kho: hình dạng phòng, cửa ra vào và mọi kệ đang dùng kèm vị trí đặt trong phòng
     * + khung tầng/ô (để dựng cả kho ở 3D).
     */
    public function layout(Request $request)
    {
        $warehouse = $this->warehouse($request->input('warehouse_id'));

        if (!$warehouse) {
            return $this->fail('Không tìm thấy kho.', 404);
        }

        $shelves = DB::table('shelves')
            ->where('department_id', $this->departmentId())
            ->where('warehouse_id', $warehouse->id)
            ->where('status_id', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation']);

        $shelfMap = self::shelfFrames($shelves->pluck('id')->all());

        $out = $shelves->map(function ($shelf) use ($shelfMap) {
            $frame = $shelfMap[$shelf->id] ?? ['tiers' => [], 'total' => 0, 'used' => 0];

            return [
                'id'         => (int) $shelf->id,
                'code'       => $shelf->code,
                'name'       => $shelf->name,
                'tier_order' => 'bottom_up',
                // Một cột duy nhất mang tên kệ (bộ vẽ 3D dùng chung với WMS vốn có cấp Cột)
                'columns'    => $frame['tiers'] ? [[
                    'name'     => $shelf->name,
                    'position' => 1,
                    'tiers'    => $frame['tiers'],
                ]] : [],
                'total'      => $frame['total'],
                'used'       => $frame['used'],
                'layout'     => $shelf->layout_x === null ? null : [
                    'x'        => (float) $shelf->layout_x,
                    'y'        => (float) $shelf->layout_y,
                    'width'    => (float) $shelf->layout_width,
                    'depth'    => (float) $shelf->layout_depth,
                    'rotation' => (int) $shelf->layout_rotation,
                ],
            ];
        });

        $room = RoomLayout::payload($warehouse);

        return response()->json([
            'warehouse' => [
                'id'     => (int) $warehouse->id,
                'code'   => $warehouse->code,
                'name'   => $warehouse->name,
                'layout' => $room ? [
                    'width'   => $room['width'],
                    'depth'   => $room['depth'],
                    'polygon' => $room['polygon'],
                    'door'    => $room['door'],
                ] : null,
            ],
            'shelves' => $out->values(),
            'limits'  => self::limits(),
        ]);
    }

    /**
     * Khung tầng / ô của các kệ: mỗi tầng {name, code, position, max (ô xa nhất), depth: 1, busy ['p:1', ...]}.
     * Chỉ tính tầng / ô đang dùng (status_id = 1) có thứ tự. Dùng chung cho trang tra cứu công khai.
     *
     * @return array<int, array{tiers: array, total: int, used: int}>
     */
    public static function shelfFrames(array $shelfIds): array
    {
        if (!$shelfIds) {
            return [];
        }

        $tiers = DB::table('tiers')
            ->whereIn('shelf_id', $shelfIds)
            ->where('status_id', 1)
            ->whereNotNull('position')
            ->orderBy('position')
            ->get(['id', 'shelf_id', 'code', 'name', 'position']);

        $cells = DB::table('locations')
            ->whereIn('shelf_id', $shelfIds)
            ->where('status_id', 1)
            ->whereNotNull('position')
            ->get(['id', 'tier_id', 'position']);

        $busy = DB::table('documents')
            ->whereIn('location_id', $cells->pluck('id'))
            ->pluck('location_id')
            ->flip();

        $cellsByTier = $cells->groupBy('tier_id');
        $out = [];

        foreach ($tiers as $tier) {
            $own = $cellsByTier[$tier->id] ?? collect();
            $max = (int) $own->max('position');
            if ($max < 1) {
                continue;
            }

            $busyKeys = [];
            foreach ($own as $cell) {
                if (isset($busy[$cell->id])) {
                    $busyKeys[] = (int) $cell->position . ':1';
                }
            }

            $frame = &$out[$tier->shelf_id];
            $frame['tiers'][] = [
                'name'     => $tier->name,
                'code'     => $tier->code,
                'position' => (int) $tier->position,
                'max'      => $max,
                'depth'    => 1,
                'busy'     => $busyKeys,
            ];
            $frame['total'] = ($frame['total'] ?? 0) + $own->count();
            $frame['used'] = ($frame['used'] ?? 0) + count($busyKeys);
            unset($frame);
        }

        return $out;
    }

    /** Lưu mặt bằng kho: hình dạng phòng, cửa ra vào, vị trí / kích thước / hướng của từng kệ. */
    public function saveLayout(Request $request)
    {
        if (!user_has_permission(session('user')['userId'], 'warehouse.update', 'boolean')) {
            return $this->fail('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        $max = self::MAX_ROOM;
        $validator = Validator::make($request->all(), [
            'warehouse_id'       => ['required', 'integer'],
            'room.polygon'       => ['required', 'array', 'min:3', 'max:' . RoomLayout::MAX_POINTS],
            'room.polygon.*'     => ['required', 'array', 'size:2'],
            'room.polygon.*.*'   => ['required', 'numeric', 'min:0', 'max:' . $max],
            'room.door'          => ['nullable', 'array'],
            'room.door.edge'     => ['required_with:room.door', 'integer', 'min:0'],
            'room.door.offset'   => ['required_with:room.door', 'numeric', 'min:0', 'max:' . $max],
            'room.door.width'    => ['required_with:room.door', 'numeric', 'min:' . RoomLayout::DOOR_MIN, 'max:' . RoomLayout::DOOR_MAX],
            'shelves'            => ['array'],
            'shelves.*.id'       => ['required', 'integer'],
            'shelves.*.placed'   => ['required', 'boolean'],
            'shelves.*.x'        => ['nullable', 'numeric', 'min:0', 'max:' . $max],
            'shelves.*.y'        => ['nullable', 'numeric', 'min:0', 'max:' . $max],
            'shelves.*.width'    => ['nullable', 'numeric', 'min:' . self::MIN_SHELF, 'max:' . self::MAX_SHELF],
            'shelves.*.depth'    => ['nullable', 'numeric', 'min:' . self::MIN_SHELF, 'max:' . self::MAX_SHELF],
            'shelves.*.rotation' => ['nullable', Rule::in(self::ROTATIONS)],
        ], [
            'room.polygon.required' => 'Chưa vẽ hình dạng phòng.',
            'room.polygon.min'      => 'Hình dạng phòng cần ít nhất 3 góc.',
            'room.polygon.max'      => 'Hình dạng phòng tối đa ' . RoomLayout::MAX_POINTS . ' góc.',
            'room.polygon.*.*.max'  => 'Phòng tối đa ' . $max . ' m mỗi chiều.',
            'room.polygon.*.*.min'  => 'Toạ độ góc phòng không được âm.',
            'room.door.width.min'   => 'Cửa rộng tối thiểu ' . RoomLayout::DOOR_MIN . ' m.',
            'room.door.width.max'   => 'Cửa rộng tối đa ' . RoomLayout::DOOR_MAX . ' m.',
            'shelves.*.width.min'   => 'Bề ngang kệ tối thiểu ' . self::MIN_SHELF . ' m.',
            'shelves.*.depth.min'   => 'Bề sâu kệ tối thiểu ' . self::MIN_SHELF . ' m.',
            'shelves.*.width.max'   => 'Bề ngang kệ tối đa ' . self::MAX_SHELF . ' m.',
            'shelves.*.depth.max'   => 'Bề sâu kệ tối đa ' . self::MAX_SHELF . ' m.',
            'shelves.*.rotation.in' => 'Hướng mặt trước của kệ không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first());
        }

        $warehouse = $this->warehouse($request->input('warehouse_id'));

        if (!$warehouse) {
            return $this->fail('Không tìm thấy kho.', 404);
        }

        $polygon = RoomLayout::decodePolygon($request->input('room.polygon'));
        $door = $request->input('room.door');

        if (!$polygon || !RoomLayout::isSimple($polygon)) {
            return $this->fail('Hình dạng phòng không hợp lệ: các cạnh không được cắt nhau hoặc trùng góc. Hãy kéo lại các góc phòng.');
        }

        // Quy ước chạy theo chiều kim đồng hồ: đa giác vẽ ngược thì đảo thứ tự góc và đổi chỉ số cạnh của cửa.
        if (RoomLayout::signedArea($polygon) < 0) {
            $n = count($polygon);
            $polygon = array_reverse($polygon);
            if ($door) {
                $door['edge'] = ($n - 2 - (int) $door['edge'] + $n) % $n;
                $door['offset'] = max(0, RoomLayout::edgeLength($polygon, (int) $door['edge']) - (float) $door['offset'] - (float) $door['width']);
            }
        }

        if (abs(RoomLayout::signedArea($polygon)) < 1.0) {
            return $this->fail('Phòng quá nhỏ (diện tích dưới 1 m²).');
        }

        if (min(array_column($polygon, 0)) > 0.001 || min(array_column($polygon, 1)) > 0.001) {
            return $this->fail('Mặt bằng chưa được căn về góc (0,0). Hãy mở lại màn hình mặt bằng rồi lưu lần nữa.');
        }

        [$roomWidth, $roomDepth] = RoomLayout::bounds($polygon);
        $doorJson = null;

        if ($door) {
            $edge = (int) $door['edge'];

            if ($edge >= count($polygon)) {
                return $this->fail('Cửa ra vào không nằm trên cạnh nào của phòng.');
            }

            $len = RoomLayout::edgeLength($polygon, $edge);
            $width = round((float) $door['width'], 2);
            $offset = round((float) $door['offset'], 2);

            if ($offset + $width > $len + 0.01) {
                return $this->fail('Cửa ra vào (rộng ' . $width . ' m) vượt ra ngoài cạnh tường dài ' . round($len, 2) . ' m. Hãy kéo cửa vào trong hoặc thu hẹp cửa.');
            }

            $doorJson = json_encode(['edge' => $edge, 'offset' => max(0, $offset), 'width' => $width]);
        }

        $room = [
            'layout_width'   => $this->metre($roomWidth),
            'layout_depth'   => $this->metre($roomDepth),
            // Hình chữ nhật thì không lưu đa giác (đọc lại từ width × depth)
            'layout_polygon' => RoomLayout::isRectangle($polygon) ? null : RoomLayout::encode($polygon),
            'layout_door'    => $doorJson,
        ];

        $current = DB::table('shelves')
            ->where('department_id', $this->departmentId())
            ->where('warehouse_id', $warehouse->id)
            ->get(['id', 'code', 'layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation'])
            ->keyBy('id');

        $plans = [];
        foreach ((array) $request->input('shelves', []) as $item) {
            $shelf = $current->get((int) $item['id']);
            if (!$shelf) {
                return $this->fail('Có kệ không thuộc kho này.');
            }

            $placed = filter_var($item['placed'], FILTER_VALIDATE_BOOLEAN);
            $plans[$shelf->id] = $placed ? [
                'layout_x'        => $this->metre($item['x'] ?? 0),
                'layout_y'        => $this->metre($item['y'] ?? 0),
                'layout_width'    => $this->metre($item['width'] ?? 1),
                'layout_depth'    => $this->metre($item['depth'] ?? 0.5),
                'layout_rotation' => (int) ($item['rotation'] ?? 0),
            ] : array_fill_keys(['layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation'], null);
        }

        // Kệ phải nằm trọn trong phòng và không chồng lên nhau (kệ xoay 90/270 thì đổi ngang - sâu).
        $boxes = [];
        foreach ($plans as $id => $plan) {
            if ($plan['layout_x'] === null) {
                continue;
            }
            $box = $this->footprint($plan);
            if (!RoomLayout::contains($polygon, $box)) {
                return $this->fail('Kệ ' . $current[$id]->code . ' nằm ra ngoài phòng. Hãy kéo kệ vào trong hoặc chỉnh lại hình dạng phòng.');
            }
            foreach ($boxes as $otherId => $other) {
                if ($box[0] < $other[2] - 0.001 && $other[0] < $box[2] - 0.001 && $box[1] < $other[3] - 0.001 && $other[1] < $box[3] - 0.001) {
                    return $this->fail('Kệ ' . $current[$id]->code . ' đang chồng lên ' . $current[$otherId]->code . '. Hãy dời một trong hai kệ.');
                }
            }
            $boxes[$id] = $box;
        }

        $changed = DB::transaction(function () use ($warehouse, $room, $plans, $current, $polygon) {
            $actor = session('user')['fullName'] ?? session('user')['userName'] ?? 'NA';
            $now = now();
            $changed = 0;

            if ($this->differs($warehouse, $room)) {
                DB::table('warehouses')->where('id', $warehouse->id)->update($room + ['updated_by' => $actor, 'updated_at' => $now]);
                $changed++;
            }

            foreach ($plans as $id => $plan) {
                if (!$this->differs($current[$id], $plan)) {
                    continue;
                }
                DB::table('shelves')->where('id', $id)->update($plan + ['updated_by' => $actor, 'updated_at' => $now]);
                $changed++;
            }

            if ($changed) {
                $placed = count(array_filter($plans, fn ($plan) => $plan['layout_x'] !== null));
                AuditTrialController::log(
                    'Cập nhật mặt bằng kho',
                    'warehouses',
                    $warehouse->id,
                    null,
                    $warehouse->code . ' — Phòng ' . $room['layout_width'] . ' × ' . $room['layout_depth'] . ' m'
                        . ($room['layout_polygon'] ? ' (' . count($polygon) . ' góc)' : '') . ', đặt ' . $placed . ' kệ'
                );
            }

            return $changed;
        });

        return response()->json([
            'ok'      => true,
            'message' => $changed ? 'Đã lưu mặt bằng kho ' . $warehouse->code . '.' : 'Mặt bằng không có gì thay đổi.',
        ]);
    }

    public static function limits(): array
    {
        return [
            'maxRoom'   => self::MAX_ROOM,
            'minShelf'  => self::MIN_SHELF,
            'maxShelf'  => self::MAX_SHELF,
            'doorWidth' => RoomLayout::DOOR_DEFAULT,
            'doorMin'   => RoomLayout::DOOR_MIN,
            'doorMax'   => RoomLayout::DOOR_MAX,
            'maxPoints' => RoomLayout::MAX_POINTS,
        ];
    }

    /** Có cột nào khác giá trị đang lưu không (so dạng chuỗi như cột decimal trả về). */
    private function differs($row, array $fields): bool
    {
        foreach ($fields as $column => $value) {
            if ((string) ($row->{$column} ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }

        return false;
    }

    /** Khung chiếm chỗ của một kệ trên mặt bằng: [trái, trên, phải, dưới] (m). */
    private function footprint(array $plan): array
    {
        $turned = in_array((int) $plan['layout_rotation'], [90, 270], true);
        $w = (float) ($turned ? $plan['layout_depth'] : $plan['layout_width']);
        $d = (float) ($turned ? $plan['layout_width'] : $plan['layout_depth']);
        $x = (float) $plan['layout_x'];
        $y = (float) $plan['layout_y'];

        return [$x - $w / 2, $y - $d / 2, $x + $w / 2, $y + $d / 2];
    }

    /** Số mét làm tròn 2 chữ số, cùng dạng chuỗi với cột decimal để so thay đổi không bị lệch. */
    private function metre($value): string
    {
        return number_format(round((float) $value, 2), 2, '.', '');
    }
}
