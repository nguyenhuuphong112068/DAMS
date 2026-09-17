<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\StorageLocation\ShelfAssignment;
use App\StorageLocation\ShelfManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Người quản lý kho hồ sơ giao Kho, Kệ, Tầng hoặc Vị Trí cho từng người quản lý kệ
// bằng cách bấm trực tiếp trên thẻ kho, thẻ kệ và sơ đồ tầng; mỗi lần bấm lưu ngay.
class StorageAssignmentController extends Controller
{
    private function allowed(): bool
    {
        return user_has_permission(session('user')['userId'], ShelfManager::PERMISSION, 'boolean');
    }

    private function departmentId(): int
    {
        return (int) session('user')['selected_department_id'];
    }

    private function forbidden()
    {
        return response()->json(['message' => 'Bạn không có quyền phân công quản lý kệ.'], 403);
    }

    // Tên người phụ trách khác kèm số vị trí, gom theo nhóm (kho/kệ/ô) từ kết quả coverage().
    private function splitCoverage(Collection $coverage, int $userId): array
    {
        $names = DB::table('user_management')
            ->whereIn('id', $coverage->pluck('user_id')->unique())
            ->pluck('fullName', 'id');

        $mine = [];
        $others = [];
        foreach ($coverage as $row) {
            if ((int) $row->user_id === $userId) {
                $mine[$row->group_id] = (int) $row->covered;
            } else {
                $others[$row->group_id][] = ['name' => $names[$row->user_id] ?? '#' . $row->user_id, 'covered' => (int) $row->covered];
            }
        }

        return [$mine, $others];
    }

    public function index()
    {
        if (!$this->allowed()) {
            return redirect()->route('pages.general.home')->with('error', 'Bạn không có quyền phân công quản lý kệ.');
        }

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - PHÂN CÔNG QUẢN LÝ KỆ']);

        return view('pages.StorageLocation.Assignment.list', [
            'managers' => ShelfManager::users()->get(),
        ]);
    }

    // Số vị trí mỗi người đang phụ trách và danh sách phạm vi của người đang chọn.
    public function summary(Request $request)
    {
        if (!$this->allowed()) {
            return $this->forbidden();
        }

        $departmentId = $this->departmentId();
        $userId = (int) $request->input('user_id');

        $covered = ShelfAssignment::coverage($departmentId, 'department_id', $departmentId, 'department_id')
            ->pluck('covered', 'user_id');

        // Bỏ một ô khỏi phạm vi cả kho sẽ tách ra hàng chục dòng tầng/vị trí, nên gom theo kệ cho dễ đọc.
        $groups = [];
        $direct = ShelfAssignment::directMap($userId, $departmentId);
        foreach ($direct as $type => $ids) {
            foreach (ShelfManager::chains($type, $ids, $departmentId) as $chain) {
                $key = $type === 'warehouse' ? 'w' . $chain['warehouse'] : 's' . $chain['shelf'];
                $groups[$key] ??= [
                    'warehouse_id' => $chain['warehouse'],
                    'shelf_id'     => $chain['shelf'] ?? null,
                    'whole'        => false,
                    'tiers'        => 0,
                    'locations'    => 0,
                ];

                match ($type) {
                    'warehouse', 'shelf' => $groups[$key]['whole'] = true,
                    'tier'               => $groups[$key]['tiers']++,
                    'location'           => $groups[$key]['locations']++,
                };
            }
        }

        $warehouseCodes = DB::table('warehouses')->whereIn('id', array_column($groups, 'warehouse_id'))->pluck('code', 'id');
        $shelfCodes = DB::table('shelves')->whereIn('id', array_filter(array_column($groups, 'shelf_id')))->pluck('code', 'id');

        foreach ($groups as &$group) {
            $group['label'] = 'Kho ' . ($warehouseCodes[$group['warehouse_id']] ?? '?')
                . ($group['shelf_id'] ? ' › Kệ ' . ($shelfCodes[$group['shelf_id']] ?? '?') : '');
            $group['detail'] = $group['whole']
                ? ($group['shelf_id'] ? 'Cả kệ' : 'Cả kho')
                : implode(', ', array_filter([
                    $group['tiers'] ? $group['tiers'] . ' tầng' : null,
                    $group['locations'] ? $group['locations'] . ' vị trí' : null,
                ]));
        }
        unset($group);

        $groups = collect($groups)->sortBy('label', SORT_NATURAL)->values();

        return response()->json([
            'covered' => $covered,
            'total'   => DB::table('locations')->where('department_id', $departmentId)->where('status_id', 1)->count(),
            'groups'  => $groups,
        ]);
    }

    public function warehouses(Request $request)
    {
        if (!$this->allowed()) {
            return $this->forbidden();
        }

        $departmentId = $this->departmentId();
        $userId = (int) $request->input('user_id');

        $warehouses = DB::table('warehouses')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $totals = DB::table('locations')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->select('warehouse_id', DB::raw('COUNT(*) as total'))
            ->groupBy('warehouse_id')
            ->pluck('total', 'warehouse_id');

        $shelves = DB::table('shelves')
            ->where('department_id', $departmentId)
            ->where('status_id', 1)
            ->select('warehouse_id', DB::raw('COUNT(*) as total'))
            ->groupBy('warehouse_id')
            ->pluck('total', 'warehouse_id');

        [$mine, $others] = $this->splitCoverage(
            ShelfAssignment::coverage($departmentId, 'department_id', $departmentId, 'warehouse_id'),
            $userId
        );

        foreach ($warehouses as $warehouse) {
            $warehouse->total   = (int) ($totals[$warehouse->id] ?? 0);
            $warehouse->shelves = (int) ($shelves[$warehouse->id] ?? 0);
            $warehouse->covered = $mine[$warehouse->id] ?? 0;
            $warehouse->others  = $others[$warehouse->id] ?? [];
        }

        return response()->json([
            'warehouses' => $warehouses,
            'direct'     => ShelfAssignment::directMap($userId, $departmentId),
        ]);
    }

    public function shelves(Request $request)
    {
        if (!$this->allowed()) {
            return $this->forbidden();
        }

        $departmentId = $this->departmentId();
        $userId = (int) $request->input('user_id');

        $warehouse = DB::table('warehouses')
            ->where('id', $request->input('warehouse_id'))
            ->where('department_id', $departmentId)
            ->first(['id', 'code', 'name']);

        if (!$warehouse) {
            return response()->json(['message' => 'Không tìm thấy kho.'], 404);
        }

        $shelves = DB::table('shelves')
            ->where('warehouse_id', $warehouse->id)
            ->where('status_id', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $totals = DB::table('locations')
            ->where('warehouse_id', $warehouse->id)
            ->where('status_id', 1)
            ->select('shelf_id', DB::raw('COUNT(*) as total'))
            ->groupBy('shelf_id')
            ->pluck('total', 'shelf_id');

        $tiers = DB::table('tiers')
            ->where('warehouse_id', $warehouse->id)
            ->where('status_id', 1)
            ->select('shelf_id', DB::raw('COUNT(*) as total'))
            ->groupBy('shelf_id')
            ->pluck('total', 'shelf_id');

        [$mine, $others] = $this->splitCoverage(
            ShelfAssignment::coverage($departmentId, 'warehouse_id', $warehouse->id, 'shelf_id'),
            $userId
        );

        foreach ($shelves as $shelf) {
            $shelf->total   = (int) ($totals[$shelf->id] ?? 0);
            $shelf->tiers   = (int) ($tiers[$shelf->id] ?? 0);
            $shelf->covered = $mine[$shelf->id] ?? 0;
            $shelf->others  = $others[$shelf->id] ?? [];
        }

        return response()->json([
            'warehouse' => $warehouse,
            'shelves'   => $shelves,
            'direct'    => ShelfAssignment::directMap($userId, $departmentId),
        ]);
    }

    // Sơ đồ một kệ: hàng là tầng, cột là vị trí; kèm ai khác đang phụ trách từng ô.
    public function grid(Request $request)
    {
        if (!$this->allowed()) {
            return $this->forbidden();
        }

        $departmentId = $this->departmentId();
        $userId = (int) $request->input('user_id');

        $shelf = DB::table('shelves')
            ->where('id', $request->input('shelf_id'))
            ->where('department_id', $departmentId)
            ->first(['id', 'code', 'name', 'warehouse_id']);

        if (!$shelf) {
            return response()->json(['message' => 'Không tìm thấy kệ.'], 404);
        }

        $warehouse = DB::table('warehouses')->where('id', $shelf->warehouse_id)->first(['id', 'code', 'name']);

        $tiers = DB::table('tiers')
            ->where('shelf_id', $shelf->id)
            ->where('status_id', 1)
            ->orderBy('position')
            ->get(['id', 'code', 'name', 'position']);

        $cells = DB::table('locations')
            ->whereIn('tier_id', $tiers->pluck('id'))
            ->whereNotNull('position')
            ->orderBy('position')
            ->get(['id', 'code', 'tier_id', 'position', 'status_id'])
            ->groupBy('tier_id');

        foreach ($tiers as $tier) {
            // [id, vị trí, mã, đang dùng] cho gọn payload với kệ dài tới hàng trăm ô.
            $tier->cells = ($cells[$tier->id] ?? collect())
                ->map(fn ($c) => [(int) $c->id, (int) $c->position, $c->code, (int) $c->status_id === 1 ? 1 : 0])
                ->values();
        }

        [, $others] = $this->splitCoverage(
            ShelfAssignment::coverage($departmentId, 'shelf_id', $shelf->id, 'id'),
            $userId
        );

        return response()->json([
            'warehouse' => $warehouse,
            'shelf'     => $shelf,
            'tiers'     => $tiers,
            'others'    => array_map(fn ($list) => array_column($list, 'name'), $others),
            'direct'    => ShelfAssignment::directMap($userId, $departmentId),
        ]);
    }

    public function toggle(Request $request)
    {
        if (!$this->allowed()) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'action'  => 'required|in:assign,unassign',
            'type'    => 'required|in:' . implode(',', array_keys(ShelfManager::SCOPES)),
            'ids'     => 'required|array|min:1|max:5000',
            'ids.*'   => 'integer',
        ], [
            'user_id.required' => 'Chưa chọn người quản lý kệ.',
            'ids.required'     => 'Chưa chọn vị trí nào.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $userId = (int) $request->input('user_id');
        if (!ShelfManager::isShelfManager($userId)) {
            return response()->json(['message' => 'Người được chọn không có vai trò "' . ShelfManager::ROLE . '".'], 422);
        }

        $departmentId = $this->departmentId();
        $type = $request->input('type');
        $ids = array_map('intval', $request->input('ids'));
        $actor = session('user')['fullName'] ?? 'Admin';
        $assign = $request->input('action') === 'assign';

        $changed = $assign
            ? ShelfAssignment::assign($userId, $departmentId, $type, $ids, $actor)
            : ShelfAssignment::unassign($userId, $departmentId, $type, $ids, $actor);

        if (!$changed) {
            return response()->json(['ok' => true, 'changed' => 0, 'message' => 'Không có thay đổi.']);
        }

        $level = mb_strtolower(ShelfManager::SCOPES[$type]['label']);
        $label = $changed === 1
            ? ShelfManager::describe(collect([(object) ['scope_type' => $type, 'scope_id' => $ids[0]]]))->first()->path
            : $changed . ' ' . $level;
        $userName = DB::table('user_management')->where('id', $userId)->value('fullName');

        AuditTrialController::log(
            $assign ? 'Phân công quản lý kệ' : 'Bỏ phân công quản lý kệ',
            'storage_assignments',
            $userId,
            $assign ? null : $userName . ': ' . $label,
            $assign ? $userName . ': ' . $label : null
        );

        return response()->json([
            'ok'      => true,
            'changed' => $changed,
            'message' => ($assign ? 'Đã giao ' : 'Đã bỏ giao ') . $label . ($assign ? ' cho ' : ' khỏi ') . $userName . '.',
        ]);
    }
}
