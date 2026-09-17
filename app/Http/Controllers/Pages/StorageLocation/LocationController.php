<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index()
    {
        $departments = DB::table('deparments')->where('active', true)->get();
        $warehouses = DB::table('warehouses')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();
        $shelves = DB::table('shelves')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();
        $tiers = DB::table('tiers')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - VỊ TRÍ']);
        return view('pages.StorageLocation.Location.list', [
            'departments' => $departments,
            'warehouses'  => $warehouses,
            'shelves'     => $shelves,
            'tiers'       => $tiers,
        ]);
    }

    // Server-side DataTables endpoint: keeps the response light no matter how many
    // locations exist (currently 60k+) by paging/filtering/sorting in SQL instead of
    // rendering every row into the Blade view at once.
    public function datatable(Request $request)
    {
        $departmentId = session('user')['selected_department_id'];

        $base = DB::table('locations')
            ->where('locations.department_id', $departmentId)
            ->leftJoin('deparments', 'locations.department_id', '=', 'deparments.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id');

        $recordsTotal = (clone $base)->count();

        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('locations.code', 'like', "%{$search}%")
                    ->orWhere('locations.name', 'like', "%{$search}%")
                    ->orWhere('warehouses.name', 'like', "%{$search}%")
                    ->orWhere('shelves.name', 'like', "%{$search}%")
                    ->orWhere('tiers.name', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $sortableColumns = ['locations.code', 'locations.name'];
        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderColumn = $sortableColumns[$orderColumnIndex - 1] ?? 'locations.code';

        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;

        $rows = $base
            ->select(
                'locations.*',
                'deparments.name as department_name',
                'warehouses.name as warehouse_name',
                'shelves.name as shelf_name',
                'tiers.name as tier_name'
            )
            ->orderBy($orderColumn, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $userId = session('user')['userId'];
        $canUpdate = user_has_permission($userId, 'location.update', 'disabled');
        $canDeActive = user_has_permission($userId, 'location.deActive', 'disabled');

        $data = [];
        foreach ($rows as $i => $row) {
            $detail = [];
            if ($row->warehouse_name) $detail[] = 'Kho: ' . e($row->warehouse_name);
            if ($row->shelf_name) $detail[] = 'Kệ: ' . e($row->shelf_name);
            if ($row->tier_name) $detail[] = 'Tầng: ' . e($row->tier_name);

            $statusBadge = $row->status_id == 1
                ? '<span class="badge badge-success">Sử dụng</span>'
                : '<span class="badge badge-danger">Ngưng</span>';

            $actions = '<button type="button" class="btn btn-warning btn-edit mr-1" '
                . 'data-id="' . e($row->id) . '" '
                . 'data-code="' . e($row->code) . '" '
                . 'data-name="' . e($row->name) . '" '
                . 'data-dept="' . e($row->department_id) . '" '
                . 'data-wh="' . e($row->warehouse_id) . '" '
                . 'data-shelf="' . e($row->shelf_id) . '" '
                . 'data-tier="' . e($row->tier_id) . '" '
                . 'data-position="' . e($row->position) . '" '
                . 'data-toggle="modal" data-target="#updateModal" ' . $canUpdate . '><i class="fas fa-edit"></i></button>'
                . '<form class="d-inline" action="' . route('pages.storageLocation.location.deActive') . '" method="POST">'
                . csrf_field()
                . '<input type="hidden" name="id" value="' . e($row->id) . '">'
                . '<input type="hidden" name="status_id" value="' . e($row->status_id) . '">'
                . '<button type="submit" class="btn btn-' . ($row->status_id == 1 ? 'danger' : 'success') . '" ' . $canDeActive . '>'
                . '<i class="fas fa-' . ($row->status_id == 1 ? 'lock' : 'unlock') . '"></i></button></form>';

            $data[] = [
                'stt'          => $start + $i + 1,
                'code'         => e($row->code),
                'name'         => e($row->name),
                'department'   => e($row->department_name),
                'detail'       => implode('<br>', $detail),
                'status'       => $statusBadge,
                'actions'      => $actions,
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:locations,code',
            'name'          => 'required',
            'department_id' => 'required',
            'position'      => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $tierId = $request->tier_id;
        $shelfId = $request->shelf_id;
        $warehouseId = $request->warehouse_id;

        // Auto deduce shelf & warehouse from tier if given
        if ($tierId && (!$shelfId || !$warehouseId)) {
            $tier = DB::table('tiers')->where('id', $tierId)->first();
            if ($tier) {
                $shelfId = $shelfId ?: $tier->shelf_id;
                $warehouseId = $warehouseId ?: $tier->warehouse_id;
            }
        }

        DB::table('locations')->insert([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $warehouseId,
            'shelf_id'      => $shelfId,
            'tier_id'       => $tierId,
            'position'      => $request->position ?: null,
            'status_id'     => 1,
            'active'        => 1,
            'created_by'    => session('user')['fullName'] ?? 'Admin',
            'created_at'    => now()
        ]);

        return redirect()->back()->with('success', 'Đã thêm vị trí thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:locations,code,' . $request->id,
            'name'          => 'required',
            'department_id' => 'required',
            'position'      => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $tierId = $request->tier_id;
        $shelfId = $request->shelf_id;
        $warehouseId = $request->warehouse_id;

        if ($tierId && (!$shelfId || !$warehouseId)) {
            $tier = DB::table('tiers')->where('id', $tierId)->first();
            if ($tier) {
                $shelfId = $shelfId ?: $tier->shelf_id;
                $warehouseId = $warehouseId ?: $tier->warehouse_id;
            }
        }

        DB::table('locations')->where('id', $request->id)->update([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $warehouseId,
            'shelf_id'      => $shelfId,
            'tier_id'       => $tierId,
            'position'      => $request->position ?: null,
            'updated_at'    => now(),
            'updated_by'    => session('user')['fullName'] ?? 'Admin',
        ]);

        return redirect()->back()->with('success', 'Cập nhật vị trí thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $location = DB::table('locations')->where('id', $id)->first();
        if (!$location) {
            return redirect()->back()->with('error', 'Không tìm thấy vị trí!');
        }

        $newActive = $location->active ? 0 : 1;
        DB::table('locations')->where('id', $id)->update([
            'active'     => $newActive,
            'status_id'  => $newActive,
            'updated_at' => now(),
            'updated_by' => session('user')['fullName'] ?? 'Admin'
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
