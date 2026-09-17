<div class="content-wrapper">
    <div class="card shadow-none bg-transparent mx-3 mt-3">
        <div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createModal"
                    {{ user_has_permission(session('user')['userId'], 'tier.create', 'disabled') }}>
                    <i class="fas fa-plus mr-1"></i> Thêm Tầng
                </button>
            </div>

            <table id="data_table_tier" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã Tầng</th>
                        <th>Tên Tầng</th>
                        <th>Phòng Ban</th>
                        <th>Thuộc Kệ</th>
                        <th>Thuộc Kho</th>
                        <th class="text-center">Số Vị Trí Tối Đa</th>
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
                            <td>{{ $data->shelf_name ?? 'N/A' }}</td>
                            <td>{{ $data->warehouse_name ?? 'N/A' }}</td>
                            <td class="text-center">{{ $data->max_locations ?? '-' }}</td>
                            <td class="text-center">
                                @if($data->status_id == 1 && $data->active == 1)
                                    <span class="badge badge-success">Sử dụng</span>
                                @else
                                    <span class="badge badge-danger">Ngưng</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-warning btn-edit mr-1"
                                    data-id="{{ $data->id }}"
                                    data-code="{{ $data->code }}"
                                    data-name="{{ $data->name }}"
                                    data-dept="{{ $data->department_id }}"
                                    data-wh="{{ $data->warehouse_id }}"
                                    data-shelf="{{ $data->shelf_id }}"
                                    data-maxloc="{{ $data->max_locations }}"
                                    data-position="{{ $data->position }}"
                                    data-toggle="modal"
                                    data-target="#updateModal"
                                    {{ user_has_permission(session('user')['userId'], 'tier.update', 'disabled') }}>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form class="d-inline" action="{{ route('pages.storageLocation.tier.deActive') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data->id }}">
                                    <button type="submit" class="btn btn-{{ ($data->status_id == 1 && $data->active == 1) ? 'danger' : 'success' }}"
                                        {{ user_has_permission(session('user')['userId'], 'tier.deActive', 'disabled') }}>
                                        <i class="fas fa-{{ ($data->status_id == 1 && $data->active == 1) ? 'lock' : 'unlock' }}"></i>
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
        $('#data_table_tier').DataTable({
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

        // Filter shelf by warehouse in create modal
        $('#create_tier_wh').on('change', function() {
            const whId = $(this).val();
            const shelfSelect = $('#create_tier_shelf');
            shelfSelect.val('');
            if (!whId) {
                shelfSelect.find('option').show();
            } else {
                shelfSelect.find('option').each(function() {
                    const optWh = $(this).attr('data-wh');
                    if (!$(this).val() || optWh == whId || !optWh) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // Auto-select warehouse when shelf is chosen
        $('#create_tier_shelf').on('change', function() {
            const whId = $(this).find(':selected').attr('data-wh');
            if (whId) {
                $('#create_tier_wh').val(whId);
            }
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
            modal.find('#update_max_locations').val(button.data('maxloc'));
            modal.find('#update_position').val(button.data('position'));
        });
    });
</script>
