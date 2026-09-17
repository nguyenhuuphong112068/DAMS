<div class="content-wrapper">
    <div class="card mt-5">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-success" data-toggle="modal" data-target="#createModal" style="width: 155px"
                    {{ user_has_permission(session('user')['userId'], 'warehouse.create', 'disabled') }}>
                    <i class="fas fa-plus"></i> Thêm mới Kho
                </button>
            </div>

            <table id="data_table_warehouse" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã Kho</th>
                        <th>Tên Kho</th>
                        <th>Phòng Ban</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>
                            <td>{{ $data->department_name }}</td>
                            <td class="text-center">
                                @if ($data->status_id == 1)
                                    <span class="badge badge-success">Sử dụng</span>
                                @else
                                    <span class="badge badge-danger">Ngưng</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-warning btn-edit mr-1"
                                    data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                    data-name="{{ $data->name }}" data-dept="{{ $data->department_id }}"
                                    data-toggle="modal" data-target="#updateModal"
                                    {{ user_has_permission(session('user')['userId'], 'warehouse.update', 'disabled') }}>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form class="d-inline" action="{{ route('pages.storageLocation.warehouse.deActive') }}"
                                    method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    <input type="hidden" name="status_id" value="{{ $data->status_id }}">
                                    <button type="submit"
                                        class="btn btn-{{ $data->status_id == 1 ? 'danger' : 'success' }}"
                                        {{ user_has_permission(session('user')['userId'], 'warehouse.deActive', 'disabled') }}>
                                        <i class="fas fa-{{ $data->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
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
            timer: 1500,
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        $('#data_table_warehouse').DataTable({
            autoWidth: false,
            responsive: true,
            order: [[1, 'asc']],
            columnDefs: [{
                targets: 0,
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            }]
        });

        $(document).on('click', '.btn-edit', function() {
            const button = $(this);
            const modal = $('#updateModal');
            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_code').val(button.data('code'));
            modal.find('#update_name').val(button.data('name'));
            modal.find('#update_dept').val(button.data('dept'));
        });
    });
</script>
