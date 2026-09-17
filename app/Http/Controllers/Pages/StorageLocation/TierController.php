<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TierController extends Controller
{
    public function index()
    {
        $datas = DB::table('tiers')
            ->where('tiers.department_id', session('user')['selected_department_id'])
            ->leftJoin('deparments', 'tiers.department_id', '=', 'deparments.id')
            ->leftJoin('warehouses', 'tiers.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'tiers.shelf_id', '=', 'shelves.id')
            ->select('tiers.*', 'deparments.name as department_name', 'warehouses.name as warehouse_name', 'shelves.name as shelf_name')
            ->orderBy('tiers.code', 'asc')
            ->get();

        $departments = DB::table('deparments')->where('active', true)->get();
        $warehouses = DB::table('warehouses')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();
        $shelves = DB::table('shelves')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - TẦNG']);
        return view('pages.StorageLocation.Tier.list', [
            'datas'       => $datas,
            'departments' => $departments,
            'warehouses'  => $warehouses,
            'shelves'     => $shelves
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:tiers,code',
            'name'          => 'required',
            'department_id' => 'required',
            'max_locations' => 'nullable|integer|min:1',
            'position'      => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        // Tự động tìm warehouse_id từ shelf nếu shelf được chọn nhưng warehouse_id chưa gửi
        $warehouseId = $request->warehouse_id;
        if (!$warehouseId && $request->shelf_id) {
            $shelf = DB::table('shelves')->where('id', $request->shelf_id)->first();
            if ($shelf) {
                $warehouseId = $shelf->warehouse_id;
            }
        }

        DB::table('tiers')->insert([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $warehouseId,
            'shelf_id'      => $request->shelf_id,
            'max_locations' => $request->max_locations ?: null,
            'position'      => $request->position ?: null,
            'status_id'     => 1,
            'active'        => 1,
            'created_by'    => session('user')['fullName'] ?? 'Admin',
            'created_at'    => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm tầng thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:tiers,code,' . $request->id,
            'name'          => 'required',
            'department_id' => 'required',
            'max_locations' => 'nullable|integer|min:1',
            'position'      => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $warehouseId = $request->warehouse_id;
        if (!$warehouseId && $request->shelf_id) {
            $shelf = DB::table('shelves')->where('id', $request->shelf_id)->first();
            if ($shelf) {
                $warehouseId = $shelf->warehouse_id;
            }
        }

        DB::table('tiers')->where('id', $request->id)->update([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $warehouseId,
            'shelf_id'      => $request->shelf_id,
            'max_locations' => $request->max_locations ?: null,
            'position'      => $request->position ?: null,
            'updated_at'    => now(),
            'updated_by'    => session('user')['fullName'] ?? 'Admin',
        ]);

        return redirect()->back()->with('success', 'Cập nhật tầng thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $tier = DB::table('tiers')->where('id', $id)->first();
        if (!$tier) {
            return redirect()->back()->with('error', 'Không tìm thấy tầng!');
        }

        $newActive = $tier->active ? 0 : 1;
        DB::table('tiers')->where('id', $id)->update([
            'active'     => $newActive,
            'status_id'  => $newActive,
            'updated_at' => now(),
            'updated_by' => session('user')['fullName'] ?? 'Admin',
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
