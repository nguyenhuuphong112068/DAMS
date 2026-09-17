
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<style>
  .step-checkbox {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #007bff; /* màu xanh bootstrap */
  }

  .step-checkbox:checked {
    box-shadow: 0 0 5px #007bff;
  }

  .role-header-actions {
    display: inline-block;
    margin-left: 6px;
  }

  .role-header-actions .btn {
    padding: 1px 6px;
    font-size: 11px;
  }

  .permission-group-row td {
    background-color: #f4f6f9;
    font-weight: bold;
  }
</style>

<div class="content-wrapper">
        <div class="card">
            <div class="card-header mt-4"></div>
            <div class="card-body">

              @php
                  $userId = session('user')['userId'];
                  $canCreateRole = user_has_permission($userId, 'role.create', 'disabled');
                  $canUpdateRole = user_has_permission($userId, 'role.update', 'disabled');
                  $canDeleteRole = user_has_permission($userId, 'role.delete', 'disabled');
                  $canAssignPermission = user_has_permission($userId, 'role.assignPermission', 'disabled');
              @endphp

              <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal"
                  style="width: 155px" {{ $canCreateRole }}>
                  <i class="fas fa-plus"></i> Thêm
              </button>

              <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 16px">
                    <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020">
                        <tr>
                            <th>Permission</th>
                            @foreach ($datas as $role)
                                <th class="text-center">
                                    {{ $role['name'] }}
                                    @if ($role['id'] != 1)
                                        <span class="role-header-actions">
                                            <button type="button" class="btn btn-warning btn-role-edit"
                                                data-id="{{ $role['id'] }}" data-name="{{ $role['name'] }}"
                                                data-toggle="modal" data-target="#UpdateRoleModal" title="Đổi tên"
                                                {{ $canUpdateRole }}>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-role-destroy"
                                                data-id="{{ $role['id'] }}" data-name="{{ $role['name'] }}" title="Xoá"
                                                {{ $canDeleteRole }}>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                       @php $currentGroup = null; @endphp
                       @forelse ($permissions as $permission)
                          @if ($currentGroup !== $permission->permission_group)
                              @php $currentGroup = $permission->permission_group; @endphp
                              <tr class="permission-group-row">
                                  <td colspan="{{ count($datas) + 1 }}">
                                      {{ $groups[$permission->permission_group] ?? 'Khác' }}
                                  </td>
                              </tr>
                          @endif
                          <tr>
                              <td>{{ $permission->display_name }}</td>
                              @foreach ($datas as $role)
                                  <td>
                                    <div class="form-check form-switch text-center">
                                      <input class="form-check-input step-checkbox"
                                            type="checkbox" role="switch"
                                            data-role="{{ $role['id'] }}"
                                            data-permission="{{ $permission->id }}"
                                            id="checkbox-{{ $permission->id }}-{{ $role['id'] }}"
                                            name="permission"
                                            {{ in_array($permission->id, $role['permission_ids']) ? 'checked' : '' }}
                                            {{ $canAssignPermission }}>
                                    </div>
                                  </td>
                              @endforeach
                          </tr>
                      @empty
                          <tr>
                              <td colspan="{{ count($datas) + 1 }}" class="text-center text-muted">
                                  Chưa có quyền nào. Vui lòng vào mục "Quyền" để thêm.
                              </td>
                          </tr>
                      @endforelse
                    </tbody>
              </table>
            </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
    <!-- /.content -->
  </div>


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
<script>
    Swal.fire({
        title: 'Thành công!',
        text: '{{ session('success') }}',
        icon: 'success',
        timer: 2000, // tự đóng sau 2 giây
        showConfirmButton: false
    });
</script>
@endif

@if (session('error'))
<script>
    Swal.fire({
        title: 'Lỗi!',
        text: '{{ session('error') }}',
        icon: 'error',
    });
</script>
@endif

<script>
  $(document).ready(function () {
      document.body.style.overflowY = "auto";
  })

  $(document).on('change', '.step-checkbox', function () {
      let roleId = $(this).data('role');
      let permissionId = $(this).data('permission');
      let checked = $(this).is(':checked');

      $.ajax({
        url: "{{ route('pages.User.role.store_or_update') }}",
        type: 'POST',
        dataType: 'json', // 👉 ép jQuery hiểu rõ kiểu dữ liệu trả về
        data: {
          _token: '{{ csrf_token() }}',
          role_id: roleId,
          permission_id: permissionId,
          checked: checked
        }
      });

  });

  $(document).on('click', '.btn-role-edit', function () {
      const button = $(this);
      const modal = $('#UpdateRoleModal');
      modal.find('input[name="id"]').val(button.data('id'));
      modal.find('input[name="name"]').val(button.data('name'));
  });

  $(document).on('click', '.btn-role-destroy', function () {
      const id = $(this).data('id');
      const name = $(this).data('name');

      Swal.fire({
          title: 'Bạn chắc chắn muốn xoá?',
          text: `Nhóm quyền: ${name}`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Đồng ý',
          cancelButtonText: 'Hủy'
      }).then((result) => {
          if (result.isConfirmed) {
              $('<form>', {
                  action: "{{ url('/User/role/destroy') }}/" + id,
                  method: 'POST'
              }).append($('<input>', {
                  type: 'hidden',
                  name: '_token',
                  value: "{{ csrf_token() }}"
              })).appendTo('body').submit();
          }
      });
  });
</script>

