<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\StorageLocation\ShelfAccess;
use App\StorageLocation\ShelfAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseMapController extends Controller
{
    // Kho lớn nhất hiện có 14.640 ô, nên ngưỡng đặt trên mức đó để không kho nào bị chặn.
    private const MAX_CELLS = 16000;

    // Ở mức phóng to này chữ trong ô không còn đọc được, nên bỏ mã tài liệu khỏi payload.
    private const LIGHTWEIGHT_ABOVE = 3000;

    public function index()
    {
        $departmentId = session('user')['selected_department_id'];

        $warehouses = DB::table('warehouses')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->orderBy('code')
            ->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - SƠ ĐỒ KHO']);

        return view('pages.StorageLocation.Map.list', [
            'warehouses'   => $warehouses,
            'departmentId' => $departmentId,
        ]);
    }

    // Đếm sức chứa/đã dùng theo từng nút của một cấp, để vẽ ô tổng quan mà không
    // phải tải chi tiết từng vị trí.
    public function summary(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $warehouseId = $request->input('warehouse_id');

        $query = DB::table('locations as l')
            ->leftJoin('documents as d', 'd.location_id', '=', 'l.id')
            ->where('l.department_id', $departmentId)
            ->select(
                DB::raw('COUNT(DISTINCT l.id) as total'),
                DB::raw('COUNT(DISTINCT d.location_id) as used')
            );

        if ($warehouseId) {
            $rows = $query
                ->join('shelves as s', 's.id', '=', 'l.shelf_id')
                ->where('l.warehouse_id', $warehouseId)
                ->addSelect('l.shelf_id as id', 's.code', 's.name')
                ->groupBy('l.shelf_id', 's.code', 's.name')
                ->orderBy('s.code')
                ->get();

            $level = 'shelf';
            $coverage = ShelfAssignment::coverage($departmentId, 'warehouse_id', $warehouseId, 'shelf_id');
        } else {
            $rows = $query
                ->join('warehouses as w', 'w.id', '=', 'l.warehouse_id')
                ->addSelect('l.warehouse_id as id', 'w.code', 'w.name')
                ->groupBy('l.warehouse_id', 'w.code', 'w.name')
                ->orderBy('w.code')
                ->get();

            $level = 'warehouse';
            $coverage = ShelfAssignment::coverage($departmentId, 'department_id', $departmentId, 'warehouse_id');
        }

        // Người quản lý kệ phụ trách từng thẻ, kèm số ô họ phụ trách trong thẻ đó.
        $names = DB::table('user_management')->whereIn('id', $coverage->pluck('user_id')->unique())->pluck('fullName', 'id');
        $managers = [];
        foreach ($coverage as $row) {
            $managers[$row->group_id][] = ['name' => $names[$row->user_id] ?? '#' . $row->user_id, 'covered' => (int) $row->covered];
        }
        foreach ($rows as $row) {
            $row->managers = $managers[$row->id] ?? [];
        }

        return response()->json([
            'level' => $level,
            'nodes' => $rows,
        ]);
    }

    // Chi tiết từng ô của một kệ, hoặc của mọi kệ trong một kho khi còn dưới ngưỡng vẽ.
    public function grid(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $shelfId = $request->input('shelf_id');
        $warehouseId = $request->input('warehouse_id');

        if (!$shelfId && !$warehouseId) {
            return response()->json(['shelves' => [], 'tiers' => [], 'cells' => []]);
        }

        $scope = function () use ($departmentId, $shelfId, $warehouseId) {
            $query = DB::table('locations as l')->where('l.department_id', $departmentId);

            if ($shelfId) {
                $query->where('l.shelf_id', $shelfId);
            } else {
                $query->where('l.warehouse_id', $warehouseId);
            }

            return $query;
        };

        $total = $scope()->count();
        if ($total > self::MAX_CELLS) {
            return response()->json([
                'too_many' => true,
                'total'    => $total,
                'limit'    => self::MAX_CELLS,
            ]);
        }

        $lightweight = $total > self::LIGHTWEIGHT_ABOVE;

        $select = [
            'l.id',
            'l.code',
            'l.tier_id',
            'l.shelf_id',
            'l.position',
            'l.status_id',
            DB::raw('CASE WHEN d.id IS NULL THEN 0 ELSE 1 END as busy'),
            'd.id as doc_id',
            DB::raw('CASE WHEN d.labeled_location_id IS NOT NULL AND d.labeled_location_id <> d.location_id THEN 1 ELSE 0 END as stale'),
        ];
        if (!$lightweight) {
            $select[] = 'd.code as doc_code';
            $select[] = 'd.is_private as doc_private';
        }

        $rows = $scope()
            ->leftJoin('documents as d', 'd.location_id', '=', 'l.id')
            ->select($select)
            ->orderBy('l.shelf_id')
            ->orderBy('l.tier_id')
            ->orderBy('l.position')
            ->orderBy('l.code')
            ->get();

        $owners = $shelfId ? ShelfAccess::owners('shelf_id', $shelfId) : ShelfAccess::owners('warehouse_id', $warehouseId);
        $userId = session('user')['userId'];
        $supervisor = ShelfAccess::isSupervisor($userId);

        $cells = [];
        $seen = [];
        foreach ($rows as $row) {
            // Dữ liệu cũ có thể còn ô dính nhiều tài liệu; join trái sẽ nhân dòng nên chỉ giữ dòng đầu.
            if (isset($seen[$row->id])) {
                continue;
            }
            $seen[$row->id] = true;

            $cell = [
                'id'     => (int) $row->id,
                'code'   => $row->code,
                'tier'   => (int) $row->tier_id,
                'shelf'  => (int) $row->shelf_id,
                'pos'    => $row->position !== null ? (int) $row->position : null,
                'busy'   => (int) $row->busy,
                'off'    => (int) $row->status_id !== 1 ? 1 : 0,
            ];

            if (isset($owners[$row->id])) {
                $cell['own'] = $owners[$row->id];
                if (!$supervisor && !ShelfAccess::allows($userId, $owners, (int) $row->id)) {
                    $cell['lock'] = 1;
                }
            }

            if ($row->busy) {
                // Id tài liệu luôn gửi (kể cả ở mức thu nhỏ) vì kéo thả cần nó.
                $cell['did'] = (int) $row->doc_id;
                if ($row->stale) {
                    $cell['stale'] = 1;
                }
                if (!$lightweight) {
                    $cell['doc'] = $row->doc_private ? null : $row->doc_code;
                }
            }

            $cells[] = $cell;
        }

        $shelfIds = array_values(array_unique(array_column($cells, 'shelf')));

        $shelves = DB::table('shelves')
            ->whereIn('id', $shelfIds)
            ->select('id', 'code', 'name', 'max_tiers')
            ->orderBy('code')
            ->get();

        $tiers = DB::table('tiers')
            ->whereIn('shelf_id', $shelfIds)
            ->select('id', 'code', 'name', 'shelf_id', 'position', 'max_locations')
            ->orderBy('position')
            ->orderBy('code')
            ->get();

        return response()->json([
            'shelves'     => $shelves,
            'tiers'       => $tiers,
            'cells'       => $cells,
            'lightweight' => $lightweight,
            'managers'    => ShelfAccess::names($owners),
        ]);
    }

    // Nội dung của một ô: mỗi vị trí chỉ chứa 1 tài liệu, nhưng vẫn trả mảng để
    // dữ liệu cũ có nhiều hơn 1 vẫn hiện ra thay vì bị giấu.
    public function cell(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $locationId = (int) $request->input('location_id');

        $location = DB::table('locations as l')
            ->leftJoin('warehouses as w', 'w.id', '=', 'l.warehouse_id')
            ->leftJoin('shelves as s', 's.id', '=', 'l.shelf_id')
            ->leftJoin('tiers as t', 't.id', '=', 'l.tier_id')
            ->where('l.id', $locationId)
            ->where('l.department_id', $departmentId)
            ->select(
                'l.id',
                'l.code',
                'l.name',
                'l.status_id',
                'w.name as warehouse_name',
                's.name as shelf_name',
                't.name as tier_name'
            )
            ->first();

        if (!$location) {
            return response()->json(['message' => 'Không tìm thấy vị trí.'], 404);
        }

        $documents = DB::table('documents as d')
            ->leftJoin('statuses as st', 'st.id', '=', 'd.status_id')
            ->leftJoin('locations as ll', 'll.id', '=', 'd.labeled_location_id')
            ->where('d.location_id', $locationId)
            ->select(
                'd.id',
                'll.code as labeled_location_code',
                DB::raw('CASE WHEN d.labeled_location_id IS NOT NULL AND d.labeled_location_id <> d.location_id THEN 1 ELSE 0 END as stale'),
                'd.code',
                'd.name',
                'd.owner',
                'd.expired_date',
                'd.is_private',
                'd.created_by',
                'd.created_at',
                'st.name as status_name'
            )
            ->orderBy('d.code')
            ->get();

        $viewer = session('user')['fullName'] ?? null;
        $documents = $documents->map(function ($doc) use ($viewer) {
            if ($doc->is_private && $doc->owner !== $viewer && $doc->created_by !== $viewer) {
                $doc->name = 'Tài liệu riêng tư';
                $doc->restricted = true;
            } else {
                $doc->restricted = false;
            }

            return $doc;
        });

        $owners = ShelfAccess::owners('id', [$locationId]);

        return response()->json([
            'location'   => $location,
            'documents'  => $documents,
            'managers'   => array_values(ShelfAccess::names($owners)),
            'can_manage' => ShelfAccess::denied(session('user')['userId'], [$locationId]) === null,
        ]);
    }

    // Tra ngược một tài liệu về đúng ô của nó, để người dùng nhảy thẳng tới vị trí.
    public function locate(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $keyword = trim((string) $request->input('q'));

        if ($keyword === '') {
            return response()->json(['matches' => []]);
        }

        $matches = DB::table('documents as d')
            ->join('locations as l', 'l.id', '=', 'd.location_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'l.warehouse_id')
            ->leftJoin('shelves as s', 's.id', '=', 'l.shelf_id')
            ->where('d.department_id', $departmentId)
            ->where(function ($query) use ($keyword) {
                $query->where('d.code', 'like', "%{$keyword}%")
                    ->orWhere('d.name', 'like', "%{$keyword}%")
                    ->orWhere('l.code', 'like', "%{$keyword}%");
            })
            ->select(
                'd.code as doc_code',
                'd.name as doc_name',
                'l.id as location_id',
                'l.code as location_code',
                'l.warehouse_id',
                'l.shelf_id',
                'w.name as warehouse_name',
                's.name as shelf_name'
            )
            ->orderBy('d.code')
            ->limit(20)
            ->get();

        return response()->json(['matches' => $matches]);
    }

    // Chuyển một hồ sơ sang ô trống bằng kéo thả trên sơ đồ.
    public function move(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];
        $documentId = (int) $request->input('document_id');
        $targetId = (int) $request->input('to_location_id');
        $actor = session('user')['fullName'] ?? 'NA';

        $result = DB::transaction(function () use ($departmentId, $documentId, $targetId, $actor) {
            // Khoá ô đích trước để hai người thả cùng lúc vào một ô thì người sau phải chờ rồi bị từ chối.
            $target = DB::table('locations')
                ->where('id', $targetId)
                ->where('department_id', $departmentId)
                ->lockForUpdate()
                ->first();

            if (!$target) {
                return ['status' => 404, 'message' => 'Không tìm thấy vị trí đích.'];
            }
            if ((int) $target->status_id !== 1) {
                return ['status' => 422, 'message' => 'Vị trí ' . $target->code . ' đang ngưng sử dụng.'];
            }

            $document = DB::table('documents')
                ->where('id', $documentId)
                ->where('department_id', $departmentId)
                ->lockForUpdate()
                ->first();

            if (!$document) {
                return ['status' => 404, 'message' => 'Không tìm thấy hồ sơ.'];
            }
            if ((int) $document->location_id === $targetId) {
                return ['status' => 422, 'message' => 'Hồ sơ đã nằm ở vị trí này.'];
            }

            $denied = ShelfAccess::denied(session('user')['userId'], [$document->location_id, $targetId]);
            if ($denied) {
                return ['status' => 403, 'message' => $denied];
            }

            $occupied = DB::table('documents')->where('location_id', $targetId)->lockForUpdate()->exists();
            if ($occupied) {
                return ['status' => 409, 'message' => 'Vị trí ' . $target->code . ' đã có hồ sơ khác.'];
            }

            $source = DB::table('locations')->where('id', $document->location_id)->first();
            $labeledId = $document->labeled_location_id ?? $document->location_id;

            DB::table('documents')->where('id', $document->id)->update([
                'location_id'         => $targetId,
                'labeled_location_id' => $labeledId,
                'updated_by'          => $actor,
                'updated_at'          => now(),
            ]);

            DB::table('document_moves')->insert([
                'document_id'      => $document->id,
                'from_location_id' => $document->location_id,
                'to_location_id'   => $targetId,
                'moved_by'         => $actor,
                'created_at'       => now(),
            ]);

            AuditTrialController::log(
                'Chuyển vị trí',
                'documents',
                $document->id,
                'Vị trí: ' . ($source->code ?? '-'),
                'Chuyển hồ sơ ' . $document->code . ' từ ' . ($source->code ?? '-') . ' sang ' . $target->code
            );

            return [
                'status'   => 200,
                'document' => ['id' => (int) $document->id, 'code' => $document->code],
                'from'     => ['id' => (int) $document->location_id, 'code' => $source->code ?? null],
                'to'       => ['id' => $targetId, 'code' => $target->code],
                'stale'    => (int) $labeledId !== $targetId,
            ];
        });

        $status = $result['status'];
        unset($result['status']);

        if ($status !== 200) {
            return response()->json(['ok' => false] + $result, $status);
        }

        return response()->json(['ok' => true, 'relabel_count' => $this->relabelQuery($departmentId)->count()] + $result);
    }

    // Hồ sơ có vị trí hiện tại khác vị trí in trên nhãn, tức là cần in lại nhãn.
    public function relabel()
    {
        $departmentId = session('user')['selected_department_id'];

        $latestMove = DB::table('document_moves')
            ->select('document_id', DB::raw('MAX(id) as id'))
            ->groupBy('document_id');

        $rows = $this->relabelQuery($departmentId)
            ->leftJoin('locations as cur', 'cur.id', '=', 'd.location_id')
            ->leftJoin('locations as old', 'old.id', '=', 'd.labeled_location_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'cur.warehouse_id')
            ->leftJoin('shelves as s', 's.id', '=', 'cur.shelf_id')
            ->leftJoinSub($latestMove, 'lm', 'lm.document_id', '=', 'd.id')
            ->leftJoin('document_moves as m', 'm.id', '=', 'lm.id')
            ->select(
                'd.id',
                'd.code',
                'd.name',
                'd.is_private',
                'd.owner',
                'd.created_by',
                'old.code as old_location',
                'cur.code as new_location',
                'cur.id as new_location_id',
                'cur.warehouse_id',
                'cur.shelf_id',
                'w.name as warehouse_name',
                's.name as shelf_name',
                DB::raw('COALESCE(m.moved_by, d.updated_by) as moved_by'),
                DB::raw('COALESCE(m.created_at, d.updated_at) as moved_at')
            )
            ->orderByDesc('moved_at')
            ->limit(1000)
            ->get();

        $viewer = session('user')['fullName'] ?? null;
        $rows = $rows->map(function ($row) use ($viewer) {
            if ($row->is_private && $row->owner !== $viewer && $row->created_by !== $viewer) {
                $row->name = 'Tài liệu riêng tư';
            }
            unset($row->owner, $row->created_by);

            return $row;
        });

        return response()->json([
            'total' => $this->relabelQuery($departmentId)->count(),
            'rows'  => $rows,
        ]);
    }

    private function relabelQuery($departmentId)
    {
        return DB::table('documents as d')
            ->where('d.department_id', $departmentId)
            ->whereNotNull('d.labeled_location_id')
            ->whereColumn('d.location_id', '<>', 'd.labeled_location_id');
    }
}
