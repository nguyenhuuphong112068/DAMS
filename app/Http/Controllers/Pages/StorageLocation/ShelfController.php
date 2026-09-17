<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShelfController extends Controller
{
    public function index()
    {
        $datas = DB::table('shelves')
            ->where('shelves.department_id', session('user')['selected_department_id'])
            ->leftJoin('deparments', 'shelves.department_id', '=', 'deparments.id')
            ->leftJoin('warehouses', 'shelves.warehouse_id', '=', 'warehouses.id')
            ->select('shelves.*', 'deparments.name as department_name', 'warehouses.name as warehouse_name')
            ->orderBy('shelves.code', 'asc')
            ->get();

        $departments = DB::table('deparments')->where('active', true)->get();
        $warehouses = DB::table('warehouses')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)
            ->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - KỆ']);
        return view('pages.StorageLocation.Shelf.list', [
            'datas'       => $datas,
            'departments' => $departments,
            'warehouses'  => $warehouses,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:shelves,code',
            'name'          => 'required',
            'department_id' => 'required',
            'max_tiers'     => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('shelves')->insert([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $request->warehouse_id,
            'max_tiers'     => $request->max_tiers ?: null,
            'status_id'     => 1,
            'active'        => 1,
            'created_by'    => session('user')['fullName'] ?? 'Admin',
            'created_at'    => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm kệ thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|unique:shelves,code,' . $request->id,
            'name'          => 'required',
            'department_id' => 'required',
            'max_tiers'     => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('shelves')->where('id', $request->id)->update([
            'code'          => $request->code,
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id'  => $request->warehouse_id,
            'max_tiers'     => $request->max_tiers ?: null,
            'updated_at'    => now(),
            'updated_by'    => session('user')['fullName'] ?? 'Admin'
        ]);

        return redirect()->back()->with('success', 'Cập nhật kệ thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $shelf = DB::table('shelves')->where('id', $id)->first();
        if (!$shelf) {
            return redirect()->back()->with('error', 'Không tìm thấy kệ!');
        }

        $newActive = $shelf->active ? 0 : 1;
        DB::table('shelves')->where('id', $id)->update([
            'active'     => $newActive,
            'status_id'  => $newActive,
            'updated_at' => now(),
            'updated_by' => session('user')['fullName'] ?? 'Admin'
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
