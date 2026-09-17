<style>
    .truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
    }

    /* Thanh lọc gọn */
    .search-section {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        padding: 10px 12px 4px;
        margin-bottom: 12px;
        border-left: 4px solid #3b82f6;
    }

    .search-section .search-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
    }

    .search-section .form-row > [class*="col"] {
        margin-bottom: 6px;
    }

    .search-section .form-control,
    .search-section .btn {
        height: 32px;
        font-size: 0.85rem;
    }

    .search-section .select2-container--bootstrap4 .select2-selection--single {
        height: 32px !important;
        font-size: 0.85rem;
    }

    .search-section .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 30px;
        padding-left: 8px;
    }

    .search-section .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 30px;
    }

    .document-tabs .nav-link {
        font-weight: 600;
        color: #475569;
    }

    .document-tabs .nav-link.active {
        color: #003a4f;
    }

    #data_table_document td,
    #data_table_disposal td {
        vertical-align: middle;
    }
</style>

<div class="content-wrapper">
    <ul class="nav nav-tabs document-tabs mt-3 mx-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-documents-link" data-toggle="tab" href="#tab-documents" role="tab">
                <i class="fas fa-folder-open"></i> Danh sách tài liệu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-disposals-link" data-toggle="tab" href="#tab-disposals" role="tab">
                <i class="fas fa-history"></i> Lịch sử huỷ hồ sơ
            </a>
        </li>
    </ul>

    <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-documents" role="tabpanel">
    <div class="search-section mt-3 mx-3">
        <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 8px;">
            <div class="search-title mr-auto"><i class="fas fa-search text-primary"></i> TÌM KIẾM TÀI LIỆU</div>
            @if ($expiredCount > 0)
                <span class="badge badge-danger px-2 py-1" style="font-size: 0.8rem; cursor: pointer;"
                    id="btn-filter-expired" title="Lọc tài liệu đã hết hạn">
                    <i class="fas fa-exclamation-triangle"></i> {{ $expiredCount }} tài liệu hết hạn
                </span>
            @endif
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createModal"
                style="font-weight: 600;"
                {{ user_has_permission(session('user')['userId'], 'document.create', 'disabled') }}>
                <i class="fas fa-plus-circle"></i> THÊM TÀI LIỆU
            </button>
            @if (isset(session('user')['userGroup']) && session('user')['userGroup'] == 'Admin')
                <button class="btn btn-dark btn-sm" id="btn-config-camera" style="font-weight: 600;"
                    title="Cấu hình camera cho mạng nội bộ">
                    <i class="fas fa-tools"></i> CÀI ĐẶT CAMERA
                </button>
            @endif
        </div>
        <div class="form-row">
            <div class="col-lg-3 col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" id="btn-scan-qr" type="button" title="Quét mã bằng Camera">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <input type="text" id="quick-search-input" class="form-control"
                        placeholder="Tên, mã hoặc quét QR...">
                </div>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-type" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả loại</option>
                    @foreach ($document_types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-expiry-status" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả hiệu lực</option>
                    <option value="valid">Còn hiệu lực</option>
                    <option value="expired">Đã hết hạn</option>
                </select>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-warehouse" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả kho</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-shelf" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả kệ</option>
                </select>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-tier" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả tầng</option>
                </select>
            </div>
            <div class="col-lg col-md-3 col-6">
                <select id="filter-location" class="form-control select2" style="width: 100%;">
                    <option value="">Tất cả vị trí</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" id="btn-reset-filter" title="Xóa bộ lọc">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-none bg-transparent mx-3">
        <div class="card-body p-0">
            <table id="data_table_document" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Vị trí lưu trữ</th>
                        <th class="text-center">QR Code</th>
                        <th>Tên Tài liệu</th>
                        <th>Loại Tài liệu</th>
                        <th>Ngày hết hạn</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    </div>

    <div class="tab-pane fade" id="tab-disposals" role="tabpanel">
        <div class="search-section mt-3 mx-3" style="border-left-color: #dc3545;">
            <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 8px;">
                <div class="search-title mr-auto"><i class="fas fa-history text-danger"></i> LỊCH SỬ HUỶ HỒ SƠ</div>
            </div>
            <div class="form-row">
                <div class="col-lg-4 col-md-6">
                    <input type="text" id="disposal-search-input" class="form-control"
                        placeholder="Mã, tên hồ sơ, vị trí, lý do, người huỷ...">
                </div>
            </div>
        </div>

        <div class="card shadow-none bg-transparent mx-3">
            <div class="card-body p-0">
                <table id="data_table_disposal" class="table table-bordered table-striped w-100">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Vị trí đã trả</th>
                            <th>Tên Tài liệu</th>
                            <th>Loại Tài liệu</th>
                            <th>Lý do huỷ</th>
                            <th>Người huỷ</th>
                            <th>Thời gian huỷ</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

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
    // Tải vị trí theo tầng (dùng chung cho bộ lọc và modal thêm/sửa). Bỏ qua response cũ nếu đổi tầng liên tục.
    window.loadDocumentLocations = function($select, tierId, selectedId, placeholder) {
        placeholder = placeholder || '-- Chọn Vị trí --';
        const requestId = ($select.data('locationRequest') || 0) + 1;
        $select.data('locationRequest', requestId);
        $select.html(`<option value="">${placeholder}</option>`).val('');
        if (!tierId) {
            $select.trigger('change.select2');
            return $.Deferred().resolve().promise();
        }
        return $.getJSON('{{ route('pages.documentStorage.document.locations') }}', {
            tier_id: tierId,
            include_id: selectedId || ''
        }).done(function(items) {
            if ($select.data('locationRequest') !== requestId) return;
            items.forEach(item => $select.append($('<option>').val(item.id).text(item.name)));
            if (selectedId) $select.val(String(selectedId));
            $select.trigger('change.select2');
        });
    };

    // Kho > Kệ > Tầng > Vị trí cho modal thêm/sửa. Chọn cấp thấp sẽ tự điền các cấp cao hơn;
    // đổi cấp cao sẽ bỏ chọn các cấp thấp không còn thuộc về nó.
    window.initStorageCascade = function(prefix, $modal) {
        const $wh = $(`#${prefix}_warehouse_id`);
        const $shelf = $(`#${prefix}_shelf_id`);
        const $tier = $(`#${prefix}_tier_id`);
        const $loc = $(`#${prefix}_location_id`);

        const shelfToWarehouse = {};
        $shelf.find('option[value!=""]').each(function() {
            shelfToWarehouse[this.value] = $(this).attr('data-warehouse') || '';
        });
        const tierToShelf = {};
        $tier.find('option[value!=""]').each(function() {
            tierToShelf[this.value] = $(this).attr('data-shelf') || '';
        });

        // Chỉ hiện kệ/tầng thuộc cấp trên đang chọn; chưa chọn cấp trên thì hiện tất cả
        function filterOptions() {
            const whId = $wh.val();
            const shelfId = $shelf.val();
            $shelf.find('option').each(function() {
                $(this).toggle(!this.value || !whId || shelfToWarehouse[this.value] == whId);
            });
            $tier.find('option').each(function() {
                const tierShelf = tierToShelf[this.value];
                const visible = !this.value || (shelfId ? tierShelf == shelfId : (!whId || shelfToWarehouse[tierShelf] == whId));
                $(this).toggle(visible);
            });
        }

        function clearMismatchedLocation() {
            const meta = $loc.data('meta');
            if (!$loc.val() || !meta) return;
            if (($tier.val() && meta.tier_id != $tier.val()) ||
                ($shelf.val() && meta.shelf_id != $shelf.val()) ||
                ($wh.val() && meta.warehouse_id != $wh.val())) {
                $loc.data('meta', null).val(null).trigger('change');
            }
        }

        $wh.on('change', function() {
            const whId = $wh.val();
            if (whId && $shelf.val() && shelfToWarehouse[$shelf.val()] != whId) $shelf.val('');
            if (whId && $tier.val() && shelfToWarehouse[tierToShelf[$tier.val()]] != whId) $tier.val('');
            clearMismatchedLocation();
            filterOptions();
        });

        $shelf.on('change', function() {
            const shelfId = $shelf.val();
            if (shelfId) {
                $wh.val(shelfToWarehouse[shelfId]);
                if ($tier.val() && tierToShelf[$tier.val()] != shelfId) $tier.val('');
            }
            clearMismatchedLocation();
            filterOptions();
        });

        $tier.on('change', function() {
            const tierId = $tier.val();
            if (tierId) {
                $shelf.val(tierToShelf[tierId]);
                $wh.val(shelfToWarehouse[tierToShelf[tierId]]);
            }
            clearMismatchedLocation();
            filterOptions();
        });

        $loc.select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $modal,
            placeholder: '-- Chọn Vị trí --',
            allowClear: true,
            language: {
                noResults: () => 'Không tìm thấy vị trí',
                searching: () => 'Đang tìm...',
                loadingMore: () => 'Đang tải thêm...'
            },
            ajax: {
                url: '{{ route('pages.documentStorage.document.locationSearch') }}',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    page: params.page || 1,
                    warehouse_id: $wh.val(),
                    shelf_id: $shelf.val(),
                    tier_id: $tier.val()
                }),
                processResults: data => data
            },
            templateResult: item => item.path
                ? $('<div>').append($('<div>').text(item.text), $('<small class="text-muted">').text(item.path))
                : item.text
        });

        $loc.on('select2:select', function(e) {
            const item = e.params.data;
            $loc.data('meta', item);
            $wh.val(item.warehouse_id || '');
            $shelf.val(item.shelf_id || '');
            $tier.val(item.tier_id || '');
            filterOptions();
        });
        $loc.on('select2:clear', () => $loc.data('meta', null));

        filterOptions();

        return {
            // Điền sẵn vị trí hiện tại (modal sửa)
            setLocation(item) {
                $wh.val(item.warehouse_id || '');
                $shelf.val(item.shelf_id || '');
                $tier.val(item.tier_id || '');
                $loc.empty().append(new Option('-- Chọn Vị trí --', '', false, false));
                if (item.id) $loc.append(new Option(item.name, item.id, true, true));
                $loc.data('meta', item.id ? item : null).trigger('change');
                filterOptions();
            }
        };
    };

    $(document).ready(function() {
        const escapeHtml = (value) => $('<div>').text(value == null ? '' : String(value)).html();

        // Dữ liệu phân cấp cho bộ lọc Kho > Kệ > Tầng > Vị trí
        const filterShelves = {!! json_encode($shelves->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'warehouse_id' => $s->warehouse_id])->values(), JSON_HEX_TAG) !!};
        const filterTiers = {!! json_encode($tiers->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'shelf_id' => $t->shelf_id])->values(), JSON_HEX_TAG) !!};

        function fillOptions($select, items, placeholder) {
            const current = $select.val();
            let html = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                html += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
            });
            $select.html(html);
            if (items.some(item => String(item.id) === current)) $select.val(current);
        }

        function refreshHierarchyOptions() {
            const warehouse = $('#filter-warehouse').val();
            // Kệ/Tầng/Vị trí chỉ hiện khi đã chọn cấp cha, tránh danh sách dài trùng tên
            fillOptions($('#filter-shelf'), warehouse ? filterShelves.filter(s => String(s.warehouse_id) === warehouse) : [], 'Tất cả kệ');
            const shelf = $('#filter-shelf').val();
            fillOptions($('#filter-tier'), shelf ? filterTiers.filter(t => String(t.shelf_id) === shelf) : [], 'Tất cả tầng');
        }

        const csrfToken = '{{ csrf_token() }}';
        const disposeUrl = '{{ route('pages.documentStorage.document.dispose') }}';
        const canUpdateDocument = @json($canUpdate);
        const canDisposeDocument = @json($canDispose);

        const table = $('#data_table_document').DataTable({
            autoWidth: false,
            responsive: true,
            processing: true,
            serverSide: true,
            searchDelay: 400,
            dom: "<'row'<'col-sm-12'l>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>",
            lengthMenu: [
                [10, 25, 50, 100, 500, -1],
                [10, 25, 50, 100, 500, 'Tất cả']
            ],
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route('pages.documentStorage.document.data') }}',
                data: function(d) {
                    d.type_id = $('#filter-type').val();
                    d.expiry = $('#filter-expiry-status').val();
                    d.warehouse_id = $('#filter-warehouse').val();
                    d.shelf_id = $('#filter-shelf').val();
                    d.tier_id = $('#filter-tier').val();
                    d.location_id = $('#filter-location').val();
                },
                error: function(xhr) {
                    if (xhr.status === 401) window.location.reload();
                }
            },
            columns: [{
                    data: null,
                    orderable: false,
                    render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1
                },
                {
                    data: 'location_code',
                    render: (data, type, row) => escapeHtml(data || row.location_name)
                },
                {
                    data: 'code',
                    orderable: false,
                    className: 'text-center',
                    render: (data, type, row) =>
                        `<div class="qr-code-table d-inline-block" id="qr-table-${row.id}" data-id="${row.id}"
                            data-code="${escapeHtml(row.code)}" style="cursor: pointer" title="Click để in nhãn"></div>`
                },
                {
                    data: 'name',
                    render: data => escapeHtml(data)
                },
                {
                    data: 'type_names',
                    orderable: false,
                    render: data => data ? escapeHtml(data) : '-'
                },
                {
                    data: 'expired_display',
                    render: data => data || '-'
                },
                {
                    data: 'status_id',
                    className: 'text-center',
                    render: data => data == 1 ?
                        '<span class="badge badge-success">Sử dụng</span>' :
                        '<span class="badge badge-danger">Ngưng</span>'
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center text-nowrap',
                    render: (data, type, row) => {
                        const e = escapeHtml;
                        const active = row.status_id == 1;
                        return `
                            <button type="button" class="btn btn-sm btn-info btn-view-details"
                                data-id="${row.id}" data-code="${e(row.code)}" data-name="${e(row.name)}" data-owner="${e(row.owner)}"
                                data-filepath="${e(row.filepath)}" data-dept="${e(row.department_name)}"
                                data-location="${e(row.location_name)}" data-warehouse="${e(row.warehouse_name || 'N/A')}"
                                data-shelf="${e(row.shelf_name || 'N/A')}" data-tier="${e(row.tier_name || 'N/A')}"
                                data-expired="${e(row.expired_display || 'N/A')}" data-private="${row.is_private ? 1 : 0}"
                                data-types="${e(row.type_names || 'N/A')}" data-status="${active ? 'Sử dụng' : 'Ngưng'}"
                                title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                            ${row.file_url ? `<a href="${e(row.file_url)}" target="_blank" class="btn btn-sm btn-primary" title="File"><i class="fas fa-file-download"></i></a>` : ''}
                            <button type="button" class="btn btn-sm btn-warning btn-edit"
                                data-id="${row.id}" data-code="${e(row.code)}" data-name="${e(row.name)}"
                                data-filepath="${e(row.filepath)}"
                                data-dept="${e(row.department_id)}" data-location="${e(row.location_id)}"
                                data-location-name="${e(row.location_name)}"
                                data-warehouse="${e(row.warehouse_id)}" data-shelf="${e(row.shelf_id)}"
                                data-tier="${e(row.tier_id)}" data-expired="${e(row.expired_date)}"
                                data-types="${e(JSON.stringify(row.type_ids))}"
                                data-toggle="modal" data-target="#updateModal" title="Sửa" ${canUpdateDocument ? '' : 'disabled'}><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-danger btn-dispose"
                                data-id="${row.id}" data-code="${e(row.code)}" data-name="${e(row.name)}"
                                data-location="${e(row.location_name)}" title="Huỷ hồ sơ" ${canDisposeDocument ? '' : 'disabled'}><i class="fas fa-trash-alt"></i></button>`;
                    }
                }
            ],
            createdRow: function(rowEl, data) {
                if (data.is_expired) $(rowEl).addClass('table-danger');
            }
        });

        // Huỷ hồ sơ: bắt buộc nhập lý do; hồ sơ bị gỡ khỏi danh sách và vị trí được trả về trống
        let disposalTable = null;

        $(document).on('click', '.btn-dispose', function() {
            const btn = $(this);
            Swal.fire({
                title: 'Huỷ hồ sơ?',
                icon: 'warning',
                html: `<div class="text-left" style="font-size: 0.9rem;">
                        <div><b>Mã:</b> ${escapeHtml(btn.attr('data-code'))}</div>
                        <div><b>Tên:</b> ${escapeHtml(btn.attr('data-name')) || '-'}</div>
                        <div><b>Vị trí sẽ trả về trống:</b> ${escapeHtml(btn.attr('data-location')) || '-'}</div>
                        <div class="text-danger mt-2">Hồ sơ sẽ bị gỡ khỏi danh sách và chỉ còn trong Lịch sử huỷ hồ sơ.</div>
                    </div>`,
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do huỷ hồ sơ...',
                inputValidator: value => !value || !value.trim() ? 'Vui lòng nhập lý do huỷ hồ sơ.' : undefined,
                showCancelButton: true,
                confirmButtonText: 'Huỷ hồ sơ',
                cancelButtonText: 'Đóng',
                confirmButtonColor: '#dc3545',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: reason => new Promise(resolve => {
                    $.ajax({
                        url: disposeUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: { _token: csrfToken, id: btn.attr('data-id'), reason: reason.trim() }
                    }).done(resolve).fail(xhr => {
                        Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'Không thể huỷ hồ sơ, vui lòng thử lại.');
                        resolve(false);
                    });
                })
            }).then(result => {
                if (!result.value) return;
                Swal.fire({ title: 'Đã huỷ!', text: result.value.message, icon: 'success', timer: 1800, showConfirmButton: false });
                table.draw(false);
                if (disposalTable) disposalTable.draw(false);
            });
        });

        function initDisposalTable() {
            disposalTable = $('#data_table_disposal').DataTable({
                autoWidth: false,
                responsive: true,
                processing: true,
                serverSide: true,
                searchDelay: 400,
                dom: "<'row'<'col-sm-12'l>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>",
                order: [[6, 'desc']],
                ajax: {
                    url: '{{ route('pages.documentStorage.document.disposals') }}',
                    error: function(xhr) {
                        if (xhr.status === 401) window.location.reload();
                    }
                },
                columns: [{
                        data: null,
                        orderable: false,
                        render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1
                    },
                    {
                        data: 'location_code',
                        render: (data, type, row) => (escapeHtml(data || row.location_name) || '-')
                            + (row.location_path ? `<br><small class="text-muted">${escapeHtml(row.location_path)}</small>` : '')
                    },
                    { data: 'name', render: data => escapeHtml(data) || '-' },
                    { data: 'type_names', orderable: false, render: data => data ? escapeHtml(data) : '-' },
                    { data: 'reason', orderable: false, render: data => escapeHtml(data) },
                    { data: 'disposed_by', render: data => escapeHtml(data) || '-' },
                    { data: 'disposed_display', className: 'text-nowrap', render: data => data || '-' }
                ]
            });
        }

        $('#tab-disposals-link').on('shown.bs.tab', function() {
            if (!disposalTable) {
                initDisposalTable();
            } else {
                disposalTable.columns.adjust().draw(false);
            }
        });
        $('#tab-documents-link').on('shown.bs.tab', () => table.columns.adjust());

        $('#disposal-search-input').on('input', function() {
            if (disposalTable) disposalTable.search($(this).val().trim()).draw();
        });

        // Search & Filter Logic
        let suppressReload = false;

        function reloadTable() {
            if (!suppressReload) table.draw();
        }

        $('#quick-search-input').on('input', function() {
            table.search($(this).val().trim()).draw();
        });

        $('#filter-warehouse, #filter-shelf, #filter-tier').on('change', function() {
            if (suppressReload) return;
            suppressReload = true;
            refreshHierarchyOptions();
            $('#filter-shelf, #filter-tier').trigger('change.select2');
            loadDocumentLocations($('#filter-location'), $('#filter-tier').val(), null, 'Tất cả vị trí');
            suppressReload = false;
            reloadTable();
        });
        $('#filter-location, #filter-type, #filter-expiry-status').on('change', reloadTable);

        // Quick filter from badge
        $('#btn-filter-expired').click(function() {
            $('#filter-expiry-status').val('expired').trigger('change');
        });

        $('#btn-reset-filter').click(function() {
            suppressReload = true;
            $('#quick-search-input').val('');
            $('#filter-type, #filter-expiry-status, #filter-warehouse').val('').trigger('change');
            refreshHierarchyOptions();
            $('#filter-shelf, #filter-tier').val('').trigger('change');
            loadDocumentLocations($('#filter-location'), null, null, 'Tất cả vị trí');
            suppressReload = false;
            table.search('').draw();
        });

        // Detail View Logic
        $(document).on('click', '.btn-view-details', function() {
            const btn = $(this);
            const modal = $('#detailModal');

            modal.find('#detail_name').text(btn.data('name'));
            modal.find('#detail_code').text(btn.data('code'));
            modal.find('#detail_dept').text(btn.data('dept'));
            modal.find('#detail_types').text(btn.data('types'));
            modal.find('#detail_warehouse').text(btn.data('warehouse'));
            modal.find('#detail_shelf').text(btn.data('shelf'));
            modal.find('#detail_tier').text(btn.data('tier'));
            modal.find('#detail_location').text(btn.data('location'));
            modal.find('#detail_owner').text(btn.data('owner') || 'N/A');
            modal.find('#detail_expired').text(btn.data('expired'));
            modal.find('#detail_status').text(btn.data('status'));

            const filepath = btn.data('filepath');
            if (filepath) {
                modal.find('#detail_filepath').text(filepath);
                const fullUrl = filepath.startsWith('http') ? filepath : '{{ asset('') }}' +
                    filepath;
                modal.find('#detail_open_link').attr('href', fullUrl).show();
            } else {
                modal.find('#detail_filepath').text('Không có file đính kèm');
                modal.find('#detail_open_link').hide();
            }

            if (btn.data('private')) {
                modal.find('#detail_private_section').show();
            } else {
                modal.find('#detail_private_section').hide();
            }

            // Generate QR for detail
            modal.find('#detail_qr').empty();
            new QRCode(document.getElementById("detail_qr"), {
                text: btn.data('code'),
                width: 150,
                height: 150,
                colorDark: "#003a4f",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Set print action
            $('#btn-print-detail').off('click').on('click', function() {
                openLabelPage(btn.data('id'));
            });

            modal.modal('show');
        });

        $(document).on('click', '.btn-edit', function() {
            const button = $(this);
            const modal = $('#updateModal');
            modal.find('#update_id').val(button.data('id'));
            modal.find('#update_name').val(button.data('name'));
            modal.find('#update_filepath').val(button.data('filepath'));
            modal.find('#update_dept_display').val(button.data('dept'));
            modal.find('#update_dept_id').val(button.data('dept'));
            modal.find('#update_expired').val(button.data('expired'));

            window.updateStorageCascade.setLocation({
                id: button.data('location'),
                name: button.attr('data-location-name'),
                warehouse_id: button.data('warehouse'),
                shelf_id: button.data('shelf'),
                tier_id: button.data('tier')
            });

            // Populate multi-select for Document Types
            const types = button.data('types');
            if (types) {
                try {
                    const typesArray = typeof types === 'string' ? JSON.parse(types) : types;
                    modal.find('#update_document_types').val(typesArray).trigger('change');
                } catch (e) {
                    console.error("Error parsing document types:", e);
                    modal.find('#update_document_types').val([]).trigger('change');
                }
            } else {
                modal.find('#update_document_types').val([]).trigger('change');
            }
        });

        // Initialize Select2 for modals
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Reset multi-select when createModal is shown
        $('#createModal').on('show.bs.modal', function() {
            $(this).find('.select2').val([]).trigger('change');
            $('#create_file_attachment').val('');
            $('#file_name_display').text('').removeClass('text-success font-weight-bold').addClass(
                'text-muted');
        });

        // Config Camera Logic (Admin only)
        $('#btn-config-camera').on('click', function() {
            Swal.fire({
                title: 'HƯỚNG DẪN CÀI ĐẶT CAMERA',
                icon: 'info',
                html: `
                    <div class="text-left" style="font-size: 0.9rem;">
                        <p class="text-danger font-weight-bold">Lưu ý: Trình duyệt không cho phép mở trực tiếp trang cấu hình nội bộ. Hãy thực hiện thủ công:</p>
                        <ol>
                            <li>Bấm nút xanh dưới đây để <b>sao chép đường dẫn</b>.</li>
                            <li><b>Dán (Paste)</b> vào thanh địa chỉ của Trình duyệt máy con rồi nhấn Enter.</li>
                            <li>Tại ô <b>Insecure origins treated as secure</b>, nhập đúng địa chỉ: <br>
                                <code class="p-1 bg-light border d-block my-1 text-primary">${window.location.origin}</code>
                            </li>
                            <li>Chọn <b>Enabled</b> và nhấn nút <b>Relaunch</b> bên dưới.</li>
                        </ol>
                        <button id="copy-flag-link" class="btn btn-success btn-sm w-100 mt-2">
                            <i class="fas fa-copy"></i> SAO CHÉP ĐƯỜNG DẪN CẤU HÌNH
                        </button>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true
            });
        });

        $(document).on('click', '#copy-flag-link', function() {
            const link = 'chrome://flags/#unsafely-treat-insecure-origin-as-secure';
            const temp = $('<input>');
            $('body').append(temp);
            temp.val(link).select();
            document.execCommand('copy');
            temp.remove();

            $(this).html('<i class="fas fa-check"></i> ĐÃ SAO CHÉP!').removeClass('btn-success')
                .addClass('btn-secondary');
            setTimeout(() => {
                $('#copy-flag-link').html(
                    '<i class="fas fa-copy"></i> SAO CHÉP ĐƯỜNG DẪN CẤU HÌNH').removeClass(
                    'btn-secondary').addClass('btn-success');
            }, 2000);
        });


        // Generate QR Codes (chỉ cho các dòng của trang hiện tại)
        function generateAllQRs() {
            $('#data_table_document .qr-code-table').each(function() {
                var id = $(this).attr('id');
                var code = $(this).data('code');
                $(this).empty();
                new QRCode(document.getElementById(id), {
                    text: String(code),
                    width: 30,
                    height: 30,
                    colorDark: "#333333",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            });
        }

        table.on('draw', function() {
            generateAllQRs();
        });

        $(document).on('click', '#data_table_document .qr-code-table', function() {
            openLabelPage($(this).data('id'));
        });

        // In nhãn: mở trang in nhãn tài liệu ở tab mới (chọn số lượng, in Zebra / trình duyệt)
        const labelUrl = '{{ route('pages.documentStorage.document.label') }}';
        window.openLabelPage = function(id) {
            if (!id) return;
            window.open(labelUrl + '?id=' + encodeURIComponent(id), '_blank');
        };
    });
</script>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="detailModalLabel"><i class="fas fa-info-circle"></i> Chi Tiết Tài Liệu
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center mb-4">
                        <div id="detail_qr"
                            style="display: inline-block; padding: 10px; background: white; border: 1px solid #eee; border-radius: 8px;">
                        </div>
                        <h4 class="mt-2 font-weight-bold text-primary" id="detail_name"></h4>
                        <span class="badge badge-pill badge-primary" id="detail_code"></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="fas fa-warehouse text-primary mr-2"></i> Kho:</th>
                                <td id="detail_warehouse"></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-archive text-warning mr-2"></i> Kệ:</th>
                                <td id="detail_shelf"></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-bars text-info mr-2"></i> Tầng:</th>
                                <td id="detail_tier"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="fas fa-map-marker-alt text-danger mr-2"></i> Vị trí:</th>
                                <td id="detail_location"></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-calendar-times text-warning mr-2"></i> Hết hạn:</th>
                                <td id="detail_expired"></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-check-circle text-success mr-2"></i> Trạng thái:</th>
                                <td id="detail_status"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="fas fa-building text-info mr-2"></i> Bộ phận:</th>
                                <td id="detail_dept"></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-tags text-primary mr-2"></i> Loại:</th>
                                <td id="detail_types"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><i class="fas fa-user-tie text-secondary mr-2"></i> Sở hữu:</th>
                                <td id="detail_owner"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12" id="detail_private_section">
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-eye-slash mr-2"></i> <strong>Tài liệu nội bộ:</strong> Chỉ người có quyền
                            mới có thể truy cập nội dung này.
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="font-weight-bold">File đính kèm / Đường dẫn:</label>
                        <div id="detail_file_container"
                            class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span id="detail_filepath" class="text-muted truncate mr-2"></span>
                            <a href="#" id="detail_open_link" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt mr-1"></i> Xem File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success px-4" id="btn-print-detail">
                    <i class="fas fa-print mr-1"></i> In nhãn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" role="dialog" aria-labelledby="qrScannerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrScannerModalLabel"><i class="fas fa-qrcode"></i> Quét mã QR bằng Camera
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="qr-reader" style="width: 100%; min-height: 300px; background: #000;"></div>
            </div>
            <div class="modal-footer p-2">
                <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">Hủy bỏ</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let html5QrCode;

        $('#btn-scan-qr').on('click', function() {
            $('#qrScannerModal').modal('show');

            // Wait for modal transition to finish
            setTimeout(() => {
                html5QrCode = new Html5Qrcode("qr-reader");
                const config = {
                    fps: 20, // Tăng fps để quét nhanh hơn
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                        let fontSize = Math.floor(minEdge * 0.6);
                        return {
                            width: fontSize,
                            height: fontSize
                        };
                    },
                    aspectRatio: 1.0
                };

                html5QrCode.start({
                        facingMode: "environment"
                    },
                    config,
                    (decodedText, decodedResult) => {
                        // Thành công
                        $('#quick-search-input').val(decodedText).trigger('keyup');
                        $('#qrScannerModal').modal('hide');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã nhận diện!',
                            text: 'Mã: ' + decodedText,
                            timer: 1500,
                            showConfirmButton: false,
                            position: 'top'
                        });
                        stopScanner();
                    },
                    (errorMessage) => {
                        // ignore failures
                    }
                ).catch((err) => {
                    console.error("Camera error:", err);
                    Swal.fire('Lỗi Camera',
                        'Không thể khởi động camera. Vui lòng kiểm tra quyền truy cập.',
                        'error');
                    $('#qrScannerModal').modal('hide');
                });
            }, 500);
        });

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                }).catch(err => console.error("Scanner stop err:", err));
            }
        }

        $('#qrScannerModal').on('hidden.bs.modal', function() {
            stopScanner();
        });
    });
</script>
