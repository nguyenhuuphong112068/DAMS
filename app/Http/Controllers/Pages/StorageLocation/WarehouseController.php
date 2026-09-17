<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function index()
    {
        $datas = DB::table('warehouses')
            ->where('department_id', session('user')['selected_department_id'])
            ->leftJoin('deparments', 'warehouses.department_id', '=', 'deparments.id')
            ->select('warehouses.*', 'deparments.name as department_name')
            ->orderBy('warehouses.code', 'asc')
            ->get();

        $departments = DB::table('deparments')->where('active', true)->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - KHO']);
        return view('pages.StorageLocation.Warehouse.list', [
            'datas' => $datas,
            'departments' => $departments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:warehouses,code',
            'name' => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('warehouses')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'department_id' => $request->department_id,
            'status_id' => 1,
            'created_by' => session('user')['fullName'],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm kho thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:warehouses,code,' . $request->id,
            'name' => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('warehouses')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'department_id' => $request->department_id,
            'updated_by' => session('user')['fullName'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật kho thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $status_id = $request->status_id;

        DB::table('warehouses')->where('id', $id)->update([
            'status_id' => ($status_id == 1 ? 0 : 1),
            'updated_by' => session('user')['fullName'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
