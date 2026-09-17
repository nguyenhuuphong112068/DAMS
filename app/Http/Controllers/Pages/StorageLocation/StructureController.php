<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\StorageLocation\TierCapacity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Gộp thao tác khai báo Kệ - Tầng - Vị Trí vào một lưới: hàng là tầng, cột là vị trí.
// Ba bảng vẫn giữ nguyên, chỉ gộp chỗ nhập liệu.
class StructureController extends Controller
{
    private const MAX_TIERS = 30;
    private const MAX_LOCATIONS = 200;
    private const MAX_BATCH_SHELVES = 50;
    // Một lượt tạo dựng tới vài chục nghìn dòng locations, nên chặn ngưỡng để
    // request không treo và transaction không phình quá lớn.
    private const MAX_BATCH_CELLS = 30000;

    public function index()
    {
        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - CẤU TRÚC KHO']);

        return view('pages.StorageLocation.Structure.list', [
            'maxTiers'        => self::MAX_TIERS,
            'maxLocations'    => self::MAX_LOCATIONS,
            'maxBatchShelves' => self::MAX_BATCH_SHELVES,
        ]);
    }

    // Danh sách kho, kèm số kệ và tỉ lệ đã dùng để chọn thẳng trên thẻ.
    public function warehouses()
    {
        $departmentId = session('user')['selected_department_id'];

        $warehouses = DB::table('warehouses')
            ->where('department_id', $departmentId)
            ->select('id', 'code', 'name', 'status_id', 'active')
            ->orderBy('code')
            ->get();

        $shelfCounts = DB::table('shelves')
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->where('status_id', 1)
            ->select('warehouse_id', DB::raw('COUNT(*) as total'))
            ->groupBy('warehouse_id')
            ->pluck('total', 'warehouse_id');

        $stats = DB::table('locations as l')
            ->leftJoin('documents as d', 'd.location_id', '=', 'l.id')
            ->whereIn('l.warehouse_id', $warehouses->pluck('id'))
            ->select('l.warehouse_id', DB::raw('COUNT(DISTINCT l.id) as total'), DB::raw('COUNT(DISTINCT d.location_id) as used'))
            ->groupBy('l.warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        foreach ($warehouses as $warehouse) {
            $warehouse->shelves = (int) ($shelfCounts[$warehouse->id] ?? 0);
            $warehouse->total   = (int) ($stats[$warehouse->id]->total ?? 0);
            $warehouse->used    = (int) ($stats[$warehouse->id]->used ?? 0);
        }

        return response()->json(['warehouses' => $warehouses]);
    }

    public function saveWarehouse(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $id = $request->input('id');

        $permission = $id ? 'warehouse.update' : 'warehouse.create';
        if (!user_has_permission(session('user')['userId'], $permission, 'boolean')) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:warehouses,code' . ($id ? ',' . $id : ''),
            'name' => 'required|string|max:255',
        ], [
            'code.required' => 'Chưa nhập mã kho.',
            'code.unique'   => 'Mã kho này đã được dùng cho kho khác.',
            'name.required' => 'Chưa nhập tên kho.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $actor = session('user')['fullName'] ?? 'Admin';

        if ($id) {
            $warehouse = DB::table('warehouses')
                ->where('id', $id)
                ->where('department_id', $departmentId)
                ->first();

            if (!$warehouse) {
                return response()->json(['message' => 'Không tìm thấy kho.'], 404);
            }

            DB::table('warehouses')->where('id', $id)->update([
                'code'       => $request->input('code'),
                'name'       => $request->input('name'),
                'updated_by' => $actor,
                'updated_at' => now(),
            ]);

            AuditTrialController::log(
                'Sửa kho',
                'warehouses',
                $id,
                $warehouse->code . ' — ' . $warehouse->name,
                $request->input('code') . ' — ' . $request->input('name')
            );
        } else {
            $id = DB::table('warehouses')->insertGetId([
                'code'          => $request->input('code'),
                'name'          => $request->input('name'),
                'department_id' => $departmentId,
                'status_id'     => 1,
                'active'        => 1,
                'created_by'    => $actor,
                'created_at'    => now(),
            ]);

            AuditTrialController::log('Tạo kho', 'warehouses', $id, null, $request->input('code') . ' — ' . $request->input('name'));
        }

        return response()->json([
            'ok'           => true,
            'warehouse_id' => $id,
            'message'      => 'Đã lưu kho ' . $request->input('code') . '.',
        ]);
    }

    // Danh sách kệ của một kho, kèm số tầng và tỉ lệ đã dùng để chọn nhanh.
    public function shelves(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];

        $shelves = DB::table('shelves')
            ->where('department_id', $departmentId)
            ->where('warehouse_id', $request->input('warehouse_id'))
            ->select('id', 'code', 'name', 'max_tiers', 'status_id')
            ->orderBy('code')
            ->get();

        $stats = DB::table('locations as l')
            ->leftJoin('documents as d', 'd.location_id', '=', 'l.id')
            ->whereIn('l.shelf_id', $shelves->pluck('id'))
            ->select('l.shelf_id', DB::raw('COUNT(DISTINCT l.id) as total'), DB::raw('COUNT(DISTINCT d.location_id) as used'))
            ->groupBy('l.shelf_id')
            ->get()
            ->keyBy('shelf_id');

        $tierCounts = DB::table('tiers')
            ->whereIn('shelf_id', $shelves->pluck('id'))
            ->where('status_id', 1)
            ->select('shelf_id', DB::raw('COUNT(*) as total'))
            ->groupBy('shelf_id')
            ->pluck('total', 'shelf_id');

        foreach ($shelves as $shelf) {
            $shelf->total = (int) ($stats[$shelf->id]->total ?? 0);
            $shelf->used  = (int) ($stats[$shelf->id]->used ?? 0);
            $shelf->tiers = (int) ($tierCounts[$shelf->id] ?? 0);
        }

        return response()->json(['shelves' => $shelves]);
    }

    // Cấu trúc đầy đủ của một kệ: mỗi tầng kèm ô nào đang chứa hồ sơ, ô nào đang khoá.
    // Lưới dựa vào đó để chặn kéo thu nhỏ qua ô đã có hồ sơ.
    public function detail(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];

        $shelf = DB::table('shelves')
            ->where('id', $request->input('shelf_id'))
            ->where('department_id', $departmentId)
            ->first();

        if (!$shelf) {
            return response()->json(['message' => 'Không tìm thấy kệ.'], 404);
        }

        $tiers = DB::table('tiers')
            ->where('shelf_id', $shelf->id)
            ->select('id', 'code', 'name', 'position', 'max_locations', 'status_id')
            ->orderBy('position')
            ->get();

        $cells = DB::table('locations as l')
            ->leftJoin('documents as d', 'd.location_id', '=', 'l.id')
            ->where('l.shelf_id', $shelf->id)
            ->whereNotNull('l.position')
            ->select('l.tier_id', 'l.position', 'l.status_id', DB::raw('CASE WHEN d.id IS NULL THEN 0 ELSE 1 END as busy'))
            ->get();

        $busy = [];
        $off = [];
        $seen = [];
        foreach ($cells as $cell) {
            // Dữ liệu cũ có thể còn ô dính nhiều hồ sơ; join trái nhân dòng nên chỉ giữ dòng đầu.
            $key = $cell->tier_id . ':' . $cell->position;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($cell->busy) {
                $busy[$cell->tier_id][] = (int) $cell->position;
            } elseif ((int) $cell->status_id !== 1) {
                $off[$cell->tier_id][] = (int) $cell->position;
            }
        }

        foreach ($tiers as $tier) {
            $tier->busy = $busy[$tier->id] ?? [];
            $tier->off  = $off[$tier->id] ?? [];
            // Không cho kéo mép trái qua ô cuối cùng đang chứa hồ sơ.
            $tier->last_busy = $tier->busy ? max($tier->busy) : 0;
        }

        return response()->json(['shelf' => $shelf, 'tiers' => $tiers]);
    }

    // Báo sớm mã đã có ngay lúc gõ, thay vì để người dùng khai xong cả lưới mới bị từ chối.
    public function previewCodes(Request $request)
    {
        $code = trim((string) $request->input('code'));

        return response()->json([
            'code'  => $code,
            'taken' => $code !== '' && DB::table('shelves')->where('code', $code)->exists(),
        ]);
    }

    public function applyMany(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];

        if (!user_has_permission(session('user')['userId'], 'shelf.create', 'boolean')) {
            return response()->json(['message' => 'Bạn không có quyền tạo kệ.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id'                   => 'required|integer',
            'groups'                         => 'required|array|min:1|max:' . self::MAX_BATCH_SHELVES,
            'groups.*.code'                  => 'required|string|max:50',
            'groups.*.name'                  => 'required|string|max:255',
            'groups.*.tiers'                 => 'required|array|min:1|max:' . self::MAX_TIERS,
            'groups.*.tiers.*.position'      => 'required|integer|min:1|max:' . self::MAX_TIERS,
            'groups.*.tiers.*.max_locations' => 'required|integer|min:1|max:' . self::MAX_LOCATIONS,
        ], [
            'groups.required'                    => 'Chưa có kệ nào để tạo.',
            'groups.max'                         => 'Một lượt tạo tối đa ' . self::MAX_BATCH_SHELVES . ' kệ. Hãy lưu bớt rồi tạo tiếp.',
            'groups.*.code.required'             => 'Có kệ chưa nhập mã.',
            'groups.*.name.required'             => 'Có kệ chưa nhập tên.',
            'groups.*.tiers.required'            => 'Có kệ chưa chọn số tầng.',
            'groups.*.tiers.max'                 => 'Tối đa ' . self::MAX_TIERS . ' tầng mỗi kệ.',
            'groups.*.tiers.*.max_locations.max' => 'Tối đa ' . self::MAX_LOCATIONS . ' vị trí mỗi tầng.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $warehouse = DB::table('warehouses')
            ->where('id', $request->input('warehouse_id'))
            ->where('department_id', $departmentId)
            ->first();

        if (!$warehouse) {
            return response()->json(['message' => 'Không tìm thấy kho.'], 404);
        }

        // Mỗi khối là một kệ mang cấu trúc tầng riêng, vì các kệ trong cùng một lượt có thể
        // khác nhau (6x50 và 19x12 chẳng hạn).
        $plan = [];
        $owner = [];

        foreach (array_values($request->input('groups')) as $index => $group) {
            $code = trim($group['code']);

            if (isset($owner[$code])) {
                return response()->json([
                    'message' => 'Mã kệ ' . $code . ' bị trùng giữa Kệ mới #' . $owner[$code] . ' và Kệ mới #' . ($index + 1) . '.',
                ], 422);
            }
            $owner[$code] = $index + 1;

            $wanted = [];
            foreach ($group['tiers'] as $row) {
                $wanted[(int) $row['position']] = (int) $row['max_locations'];
            }
            ksort($wanted);

            $plan[] = [
                'code'   => $code,
                'name'   => str_replace('{ma}', $code, trim($group['name'])),
                'wanted' => $wanted,
            ];
        }

        $codes = array_column($plan, 'code');
        $cells = array_sum(array_map(fn ($shelf) => array_sum($shelf['wanted']), $plan));

        if ($cells > self::MAX_BATCH_CELLS) {
            return response()->json([
                'message' => 'Lượt này sẽ tạo ' . number_format($cells, 0, ",", ".") . ' vị trí, vượt mức '
                    . number_format(self::MAX_BATCH_CELLS, 0, ",", ".") . ' cho một lần. Hãy chia nhỏ số kệ.',
            ], 422);
        }

        $clash = DB::table('shelves')->whereIn('code', $codes)->pluck('code');
        if ($clash->isNotEmpty()) {
            return response()->json([
                'message' => 'Mã kệ đã tồn tại: ' . $clash->take(5)->implode(', ')
                    . ($clash->count() > 5 ? '...' : '') . '. Hãy đổi mã kệ.',
            ], 422);
        }

        $tierCodes = [];
        foreach ($plan as $shelf) {
            foreach (array_keys($shelf['wanted']) as $position) {
                $tierCodes[] = $shelf['code'] . '.' . str_pad((string) $position, 2, '0', STR_PAD_LEFT);
            }
        }

        $clashTier = DB::table('tiers')->whereIn('code', $tierCodes)->pluck('code');
        if ($clashTier->isNotEmpty()) {
            return response()->json([
                'message' => 'Mã tầng đã tồn tại: ' . $clashTier->take(5)->implode(', ')
                    . ($clashTier->count() > 5 ? '...' : '') . '. Hãy đổi mã kệ.',
            ], 422);
        }

        $actor = session('user')['fullName'] ?? 'Admin';
        $now = now();

        // Mã vị trí unique toàn bảng; dữ liệu cũ nhập tay có thể đã chiếm mã dưới một kệ mới,
        // và lỗi ràng buộc giữa transaction sẽ thành trang 500 thay vì thông báo rõ ràng.
        $clashLocation = DB::table('locations')
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhere('code', 'like', str_replace(['%', '_'], ['\%', '\_'], $code) . '.%');
                }
            })
            ->limit(5)
            ->pluck('code');

        if ($clashLocation->isNotEmpty()) {
            return response()->json([
                'message' => 'Mã vị trí đã tồn tại: ' . $clashLocation->implode(', ') . '. Hãy đổi mã kệ.',
            ], 422);
        }

        // Kệ nào cũng mới tinh nên không có gì để đối chiếu: insert gộp thẳng thay vì đi qua
        // TierCapacity::sync từng tầng (vốn dành cho sửa, và ghi mỗi tầng một dòng audit).
        $created = DB::transaction(function () use ($plan, $codes, $warehouse, $departmentId, $actor, $now) {
            $shelfIds = [];
            $tierRows = [];

            foreach ($plan as $shelf) {
                $shelfId = DB::table('shelves')->insertGetId([
                    'code'          => $shelf['code'],
                    'name'          => $shelf['name'],
                    'department_id' => $departmentId,
                    'warehouse_id'  => $warehouse->id,
                    'max_tiers'     => count($shelf['wanted']),
                    'status_id'     => 1,
                    'active'        => 1,
                    'created_by'    => $actor,
                    'created_at'    => $now,
                ]);
                $shelfIds[] = $shelfId;

                foreach ($shelf['wanted'] as $position => $max) {
                    $tierRows[] = [
                        'code'          => $shelf['code'] . '.' . str_pad((string) $position, 2, '0', STR_PAD_LEFT),
                        'name'          => 'Tầng ' . str_pad((string) $position, 2, '0', STR_PAD_LEFT),
                        'department_id' => $departmentId,
                        'warehouse_id'  => $warehouse->id,
                        'shelf_id'      => $shelfId,
                        'max_locations' => $max,
                        'position'      => $position,
                        'status_id'     => 1,
                        'active'        => 1,
                        'created_by'    => $actor,
                        'created_at'    => $now,
                    ];
                }
            }

            foreach (array_chunk($tierRows, 500) as $chunk) {
                DB::table('tiers')->insert($chunk);
            }

            $tiers = DB::table('tiers')
                ->whereIn('shelf_id', array_values($shelfIds))
                ->select('id', 'code', 'shelf_id', 'max_locations')
                ->get();

            $locationRows = [];
            $locations = 0;
            foreach ($tiers as $tier) {
                for ($position = 1; $position <= $tier->max_locations; $position++) {
                    $number = str_pad((string) $position, 3, '0', STR_PAD_LEFT);
                    $locationRows[] = [
                        'code'          => $tier->code . '.' . $number,
                        'name'          => 'Vị trí ' . $number,
                        'department_id' => $departmentId,
                        'warehouse_id'  => $warehouse->id,
                        'shelf_id'      => $tier->shelf_id,
                        'tier_id'       => $tier->id,
                        'position'      => $position,
                        'status_id'     => 1,
                        'active'        => 1,
                        'created_by'    => $actor,
                        'created_at'    => $now,
                    ];

                    if (count($locationRows) === 1000) {
                        DB::table('locations')->insert($locationRows);
                        $locations += 1000;
                        $locationRows = [];
                    }
                }
            }

            if ($locationRows) {
                DB::table('locations')->insert($locationRows);
                $locations += count($locationRows);
            }

            AuditTrialController::log(
                'Tạo hàng loạt kệ',
                'shelves',
                0,
                null,
                'Kho ' . $warehouse->code . ': tạo ' . count($plan) . ' kệ (' . implode(', ', $codes) . '), '
                    . count($tierRows) . ' tầng, ' . $locations . ' vị trí'
            );

            return $locations;
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Đã tạo ' . count($plan) . ' kệ (' . implode(', ', $codes) . '), tổng '
                . number_format($created, 0, ",", ".") . ' vị trí.',
        ]);
    }

    public function apply(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $shelfId = $request->input('shelf_id');

        $permission = $shelfId ? 'shelf.update' : 'shelf.create';
        if (!user_has_permission(session('user')['userId'], $permission, 'boolean')) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code'                 => 'required|string|max:50|unique:shelves,code' . ($shelfId ? ',' . $shelfId : ''),
            'name'                 => 'required|string|max:255',
            'warehouse_id'         => 'required|integer',
            'tiers'                => 'required|array|min:1|max:' . self::MAX_TIERS,
            'tiers.*.position'     => 'required|integer|min:1|max:' . self::MAX_TIERS,
            'tiers.*.max_locations' => 'required|integer|min:1|max:' . self::MAX_LOCATIONS,
        ], [
            'code.required'  => 'Chưa nhập mã kệ.',
            'code.unique'    => 'Mã kệ này đã được dùng cho kệ khác.',
            'name.required'  => 'Chưa nhập tên kệ.',
            'tiers.required' => 'Kệ phải có ít nhất một tầng.',
            'tiers.max'      => 'Tối đa ' . self::MAX_TIERS . ' tầng mỗi kệ.',
            'tiers.*.max_locations.max' => 'Tối đa ' . self::MAX_LOCATIONS . ' vị trí mỗi tầng.',
            'tiers.*.max_locations.min' => 'Mỗi tầng phải có ít nhất 1 vị trí.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $warehouse = DB::table('warehouses')
            ->where('id', $request->input('warehouse_id'))
            ->where('department_id', $departmentId)
            ->first();

        if (!$warehouse) {
            return response()->json(['message' => 'Không tìm thấy kho.'], 404);
        }

        $wanted = [];
        foreach ($request->input('tiers') as $row) {
            $wanted[(int) $row['position']] = (int) $row['max_locations'];
        }
        ksort($wanted);

        $shelf = null;
        $existing = collect();

        if ($shelfId) {
            $shelf = DB::table('shelves')
                ->where('id', $shelfId)
                ->where('department_id', $departmentId)
                ->first();

            if (!$shelf) {
                return response()->json(['message' => 'Không tìm thấy kệ.'], 404);
            }

            // Tầng chưa có thứ tự thì lưới không vẽ được, nên để nguyên cho màn hình Tầng
            // xử lý; nếu gom vào đây chúng sẽ bị hiểu là "đã gỡ" rồi bị khoá oan.
            $existing = DB::table('tiers')
                ->where('shelf_id', $shelf->id)
                ->whereNotNull('position')
                ->get()
                ->keyBy('position');

            // Kiểm hết mọi tầng trước khi ghi bất cứ thứ gì, để không lưu được một nửa.
            $blocked = [];
            foreach ($existing as $position => $tier) {
                // Tầng bị gỡ khỏi lưới tương đương sức chứa 0.
                $newMax = $wanted[$position] ?? 0;
                $occupied = TierCapacity::occupiedAbove($tier->id, $newMax);

                if ($occupied->isNotEmpty()) {
                    $blocked[] = ($tier->name ?: $tier->code) . ': ' . $occupied->count() . ' hồ sơ ('
                        . $occupied->take(3)->implode(', ') . ($occupied->count() > 3 ? '...' : '') . ')';
                }
            }

            if ($blocked) {
                return response()->json([
                    'message' => 'Không thể thu nhỏ: các ô bị loại bỏ đang chứa hồ sơ. '
                        . implode(' | ', $blocked) . '. Vui lòng chuyển hồ sơ đi trước.',
                ], 422);
            }
        }

        $actor = session('user')['fullName'] ?? 'Admin';
        $now = now();
        $shelfCode = $request->input('code');

        // Mã tầng suy ra từ mã kệ, nên phải chắc mã chưa bị bản ghi khác chiếm.
        $newCodes = [];
        foreach (array_keys($wanted) as $position) {
            if (!$existing->has($position)) {
                $newCodes[$position] = $shelfCode . '.' . str_pad($position, 2, '0', STR_PAD_LEFT);
            }
        }

        if ($newCodes) {
            $clash = DB::table('tiers')->whereIn('code', $newCodes)->pluck('code');
            if ($clash->isNotEmpty()) {
                return response()->json([
                    'message' => 'Mã tầng đã tồn tại: ' . $clash->implode(', ') . '. Hãy đổi mã kệ.',
                ], 422);
            }
        }

        $result = DB::transaction(function () use (
            $shelf, $shelfId, $shelfCode, $request, $departmentId, $warehouse,
            $wanted, $existing, $newCodes, $actor, $now
        ) {
            if ($shelfId) {
                DB::table('shelves')->where('id', $shelf->id)->update([
                    'code'         => $shelfCode,
                    'name'         => $request->input('name'),
                    'warehouse_id' => $warehouse->id,
                    'max_tiers'    => count($wanted),
                    'updated_at'   => $now,
                    'updated_by'   => $actor,
                ]);
                $targetShelfId = $shelf->id;
            } else {
                $targetShelfId = DB::table('shelves')->insertGetId([
                    'code'          => $shelfCode,
                    'name'          => $request->input('name'),
                    'department_id' => $departmentId,
                    'warehouse_id'  => $warehouse->id,
                    'max_tiers'     => count($wanted),
                    'status_id'     => 1,
                    'active'        => 1,
                    'created_by'    => $actor,
                    'created_at'    => $now,
                ]);
            }

            $totals = ['created' => 0, 'locked' => 0, 'unlocked' => 0];
            $tiersAdded = 0;
            $tiersRemoved = 0;

            foreach ($wanted as $position => $max) {
                $tier = $existing->get($position);

                if ($tier) {
                    DB::table('tiers')->where('id', $tier->id)->update([
                        'max_locations' => $max,
                        'warehouse_id'  => $warehouse->id,
                        // Tầng từng bị gỡ nay được thêm lại thì mở dùng trở lại.
                        'status_id'     => 1,
                        'active'        => 1,
                        'updated_at'    => $now,
                        'updated_by'    => $actor,
                    ]);

                    $oldMax = $tier->max_locations !== null ? (int) $tier->max_locations : null;
                    $tierId = $tier->id;
                } else {
                    $tierId = DB::table('tiers')->insertGetId([
                        'code'          => $newCodes[$position],
                        'name'          => 'Tầng ' . str_pad($position, 2, '0', STR_PAD_LEFT),
                        'department_id' => $departmentId,
                        'warehouse_id'  => $warehouse->id,
                        'shelf_id'      => $targetShelfId,
                        'max_locations' => $max,
                        'position'      => $position,
                        'status_id'     => 1,
                        'active'        => 1,
                        'created_by'    => $actor,
                        'created_at'    => $now,
                    ]);

                    $oldMax = null;
                    $tiersAdded++;
                }

                $changes = TierCapacity::sync($tierId, $oldMax, $max);
                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $changes[$key];
                }
            }

            // Tầng bị gỡ khỏi lưới: khoá lại, không xoá, vì mã và lịch sử vẫn phải tra được.
            foreach ($existing as $position => $tier) {
                if (isset($wanted[$position])) {
                    continue;
                }

                $totals['locked'] += DB::table('locations')
                    ->where('tier_id', $tier->id)
                    ->where('status_id', 1)
                    ->update(['status_id' => 0, 'active' => 0, 'updated_at' => $now, 'updated_by' => $actor]);

                DB::table('tiers')->where('id', $tier->id)->update([
                    'status_id'  => 0,
                    'active'     => 0,
                    'updated_at' => $now,
                    'updated_by' => $actor,
                ]);

                $tiersRemoved++;
            }

            AuditTrialController::log(
                $shelfId ? 'Sửa cấu trúc kệ' : 'Tạo cấu trúc kệ',
                'shelves',
                $targetShelfId,
                $shelfId ? 'Số tầng: ' . $existing->count() : null,
                'Số tầng: ' . count($wanted) . ' (thêm ' . $tiersAdded . ' tầng, gỡ ' . $tiersRemoved
                    . ' tầng, tạo ' . $totals['created'] . ' vị trí, khoá ' . $totals['locked']
                    . ', mở lại ' . $totals['unlocked'] . ')'
            );

            return [
                'shelf_id'      => $targetShelfId,
                'tiers_added'   => $tiersAdded,
                'tiers_removed' => $tiersRemoved,
                'totals'        => $totals,
            ];
        });

        $parts = [];
        if ($result['tiers_added']) {
            $parts[] = 'thêm ' . $result['tiers_added'] . ' tầng';
        }
        if ($result['tiers_removed']) {
            $parts[] = 'gỡ ' . $result['tiers_removed'] . ' tầng';
        }
        if ($result['totals']['created']) {
            $parts[] = 'tạo ' . $result['totals']['created'] . ' vị trí';
        }
        if ($result['totals']['locked']) {
            $parts[] = 'khoá ' . $result['totals']['locked'] . ' vị trí';
        }
        if ($result['totals']['unlocked']) {
            $parts[] = 'mở lại ' . $result['totals']['unlocked'] . ' vị trí';
        }

        return response()->json([
            'ok'       => true,
            'shelf_id' => $result['shelf_id'],
            'message'  => 'Đã lưu cấu trúc kệ ' . $shelfCode . ($parts ? ': ' . implode(', ', $parts) . '.' : '.'),
        ]);
    }
}
