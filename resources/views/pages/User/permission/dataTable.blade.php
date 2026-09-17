<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="card">

              <div class="card-header mt-4">
                {{-- <h3 class="card-title">Ghi Chú Nếu Có</h3> --}}

              </div>

              <!-- /.card-Body -->
              <div class="card-body">

                <button class="btn btn-success btn-create mb-2" data-toggle="modal" data-target="#createModal"
                    style="width: 155px"
                    {{ user_has_permission(session('user')['userId'], 'permission.create', 'disabled') }}>
                    <i class="fas fa-plus"></i> Thêm
                </button>

                <table id="data_table_permission" class="table table-bordered table-striped" style="font-size: 16px">
                  <thead style = "position: sticky; top: 60px; background-color: white; z-index: 1020" >

                    <tr>
                    <th>STT</th>
                    <th>Mã Quyền</th>
                    <th>Tên Hiển Thị</th>
                    <th>Nhóm Chức Năng</th>
                    <th>Mô Tả</th>
                    <th>Edit</th>
                    <th>Xoá</th>
                  </tr>
                  </thead>
                  <tbody>

                  @foreach ($datas as $data)
                    <tr>
                      <td>{{ $loop->iteration}} </td>
                      <td>{{ $data->name}}</td>
                      <td>{{ $data->display_name}}</td>
                      <td>{{ $groups[$data->permission_group] ?? $data->permission_group }}</td>
                      <td>{{ $data->description}}</td>

                      <td class="text-center align-middle">
                        <button type="button" class="btn btn-warning btn-edit" data-id="{{ $data->id }}"
                            data-name="{{ $data->name }}"
                            data-displayname="{{ $data->display_name }}"
                            data-group="{{ $data->permission_group }}"
                            data-description="{{ $data->description }}"
                            data-toggle="modal" data-target="#UpdateModal"
                            {{ user_has_permission(session('user')['userId'], 'permission.update', 'disabled') }}>
                            <i class="fas fa-edit"></i>
                        </button>
                      </td>

                      <td class="text-center align-middle">
                        <form class="form-destroy"
                            action="{{ route('pages.User.permission.destroy', ['id' => $data->id]) }}"
                            method="post">
                            @csrf
                            <button type="submit" class="btn btn-danger" data-name="{{ $data->display_name }}"
                                {{ user_has_permission(session('user')['userId'], 'permission.delete', 'disabled') }}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach

                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
    </section>
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

  $('.btn-edit').click(function () {
      const button = $(this);
      const modal = $('#UpdateModal');

      modal.find('input[name="id"]').val(button.data('id'));
      modal.find('input[name="name"]').val(button.data('name'));
      modal.find('input[name="display_name"]').val(button.data('displayname'));
      modal.find('select[name="permission_group"]').val(button.data('group'));
      modal.find('input[name="description"]').val(button.data('description'));
  });

  $('.form-destroy').on('submit', function (e) {
      e.preventDefault();
      const form = this;
      const name = $(form).find('button[type="submit"]').data('name');

      Swal.fire({
          title: 'Bạn chắc chắn muốn xoá?',
          text: `Quyền: ${name}`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Đồng ý',
          cancelButtonText: 'Hủy'
      }).then((result) => {
          if (result.isConfirmed) {
              form.submit();
          }
      });
  });
</script>
