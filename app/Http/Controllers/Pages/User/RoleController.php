<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(){
        $roles = DB::table('roles')->orderBy('id', 'asc')->get();

        $rolePermissionMap = DB::table('role_permission')
            ->get()
            ->groupBy('role_id')
            ->map(fn ($items) => $items->pluck('permission_id')->toArray());

        $datas = $roles->map(function ($role) use ($rolePermissionMap) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permission_ids' => $rolePermissionMap->get($role->id, collect())->toArray(),
            ];
        });

        // Lấy toàn bộ quyền hiện có (không chỉ quyền đã gán cho Admin) để quyền
        // mới tạo luôn xuất hiện trong bảng, kể cả khi chưa được gán cho ai.
        $permissions = DB::table('permissions')
            ->orderBy('permission_group', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        session()->put(['title'=> 'DANH SÁCH NHÓM QUYỀN']);
        return view('pages.User.role.list', [
            'datas' => $datas,
            'permissions' => $permissions,
            'groups' => PermissionContoller::GROUPS,
        ]);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
        ], [
            'name.required' => 'Vui lòng nhập tên nhóm quyền.',
            'name.unique' => 'Tên nhóm quyền đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('roles')->insert([
            'name' => $request->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm nhóm quyền thành công!');
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|max:255|unique:roles,name,'.$request->id,
        ], [
            'name.required' => 'Vui lòng nhập tên nhóm quyền.',
            'name.unique' => 'Tên nhóm quyền đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        if ((int) $request->id === 1) {
            return redirect()->back()->with('error', 'Không thể đổi tên nhóm quyền Admin!');
        }

        DB::table('roles')->where('id', $request->id)->update([
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật nhóm quyền thành công!');
    }

    public function destroy(string|int $id){
        if ((int) $id === 1) {
            return redirect()->back()->with('error', 'Không thể xoá nhóm quyền Admin!');
        }

        DB::table('role_permission')->where('role_id', $id)->delete();
        DB::table('user_role')->where('role_id', $id)->delete();
        DB::table('roles')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Đã xoá nhóm quyền thành công!');
    }

    public function store_or_update(Request $request){
        try {
            $roleId = $request->input('role_id');
            $permissionId = $request->input('permission_id');
            $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

            if (!$roleId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu role hoặc permission'], 400);
            }

            if ($checked) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            } else {
                if ($roleId != 1) {
                    DB::table('role_permission')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->delete();
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
