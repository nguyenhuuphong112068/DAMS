<?php

namespace App\Http\Controllers\Pages\StorageLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index()
    {
        $datas = DB::table('rooms')
            ->where('rooms.department_id', session('user')['selected_department_id'])
            ->leftJoin('deparments', 'rooms.department_id', '=', 'deparments.id')
            ->leftJoin('warehouses', 'rooms.warehouse_id', '=', 'warehouses.id')
            ->select('rooms.*', 'deparments.name as department_name', 'warehouses.name as warehouse_name')
            ->orderBy('rooms.code', 'asc')
            ->get();

        $departments = DB::table('deparments')->where('active', true)->get();
        $warehouses = DB::table('warehouses')
            ->where('department_id', session('user')['department_id'])
            ->where('active', true)->get();

        session()->put(['title' => 'VỊ TRÍ LƯU TRỮ - PHÒNG']);
        return view('pages.StorageLocation.Room.list', [
            'datas' => $datas,
            'departments' => $departments,
            'warehouses' => $warehouses
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:rooms,code',
            'name' => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('rooms')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id' => $request->warehouse_id,
            'status_id' => 1,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),

        ]);
        return redirect()->back()->with('success', 'Đã thêm phòng thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:rooms,code,' . $request->id,
            'name' => 'required',
            'department_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('rooms')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'department_id' => $request->department_id,
            'warehouse_id' => $request->warehouse_id,
            'updated_at' => now(),
            'updated_by' => session('user')['fullName'],
        ]);

        return redirect()->back()->with('success', 'Cập nhật phòng thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $status_id = $request->status_id;

        DB::table('rooms')->where('id', $id)->update([
            'status_id' => ($status_id == 1 ? 0 : 1),
            'updated_at' => now(),
            'updated_by' => session('user')['fullName'],
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
