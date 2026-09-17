<div class="content-wrapper">
    <div class="card mt-5">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-success" data-toggle="modal" data-target="#createModal" style="width: 155px"
                    {{ user_has_permission(session('user')['userId'], 'location.create', 'disabled') }}>
                    <i class="fas fa-plus"></i> Thêm mới Vị trí
                </button>
            </div>

            <table id="data_table_location" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã Vị trí</th>
                        <th>Tên Vị trí</th>
                        <th>Phòng Ban</th>
                        <th>Chi tiết cấp trên</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody></tbody>
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
        $('#data_table_location').DataTable({
            autoWidth: false,
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '{{ route('pages.storageLocation.location.datatable') }}',
            order: [[1, 'asc']],
            columns: [
                { data: 'stt', orderable: false, searchable: false },
                { data: 'code' },
                { data: 'name' },
                { data: 'department', orderable: false },
                { data: 'detail', orderable: false, searchable: false },
                { data: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ]
        });

        $(document).on('click', '.btn-edit', function() {
            const button = $(this);
            const modal = $('#updateModal');
            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_code').val(button.data('code'));
            modal.find('#update_name').val(button.data('name'));
            modal.find('#update_dept').val(button.data('dept'));
            modal.find('#update_wh').val(button.data('wh'));
            modal.find('#update_shelf').val(button.data('shelf'));
            modal.find('#update_tier').val(button.data('tier'));
            modal.find('#update_position').val(button.data('position'));
        });
    });
</script>
