<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionContoller extends Controller
{
    /**
     * Nhóm chức năng dùng để phân loại quyền khi tạo mới, hiển thị trong
     * dropdown ở form thêm/sửa quyền và làm tiêu đề nhóm trong bảng Nhóm Quyền.
     */
    public const GROUPS = [
        1 => 'Dữ Liệu Gốc',
        2 => 'Vị Trí Lưu Trữ',
        3 => 'Sơ Đồ Kho',
        4 => 'Quản Lý Lưu Trữ',
        5 => 'Luân Chuyển Hồ Sơ',
        7 => 'Phân Quyền & Người Dùng',
        8 => 'Khác',
    ];

    public function index(){
        $datas = DB::table('permissions')
            ->orderBy('permission_group','asc')
            ->orderBy('id','asc')
            ->get();

        session()->put(['title'=> 'DANH SÁCH QUYỀN']);
        return view('pages.User.permission.list',[
            'datas' => $datas,
            'groups' => self::GROUPS,
        ]);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|regex:/^[a-z0-9_\.]+$/|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'permission_group' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Vui lòng nhập mã quyền.',
            'name.regex' => 'Mã quyền chỉ gồm chữ thường, số, dấu chấm và gạch dưới (vd: document.create).',
            'name.unique' => 'Mã quyền đã tồn tại.',
            'display_name.required' => 'Vui lòng nhập tên hiển thị.',
            'permission_group.required' => 'Vui lòng chọn nhóm chức năng.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('permissions')->insert([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'permission_group' => $request->permission_group,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm quyền thành công!');
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:permissions,id',
            'name' => 'required|string|max:150|regex:/^[a-z0-9_\.]+$/|unique:permissions,name,'.$request->id,
            'display_name' => 'required|string|max:255',
            'permission_group' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Vui lòng nhập mã quyền.',
            'name.regex' => 'Mã quyền chỉ gồm chữ thường, số, dấu chấm và gạch dưới (vd: document.create).',
            'name.unique' => 'Mã quyền đã tồn tại.',
            'display_name.required' => 'Vui lòng nhập tên hiển thị.',
            'permission_group.required' => 'Vui lòng chọn nhóm chức năng.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('permissions')->where('id', $request->id)->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'permission_group' => $request->permission_group,
            'description' => $request->description,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật quyền thành công!');
    }

    public function destroy(string|int $id){
        DB::table('role_permission')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Đã xoá quyền thành công!');
    }
}
