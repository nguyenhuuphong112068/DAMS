<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">Thêm Tài Liệu Mới</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.document.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bộ Phận/Phòng Ban <span class="text-danger">*</span></label>
                        <input type="hidden" name="department_id" value="{{ session('user')['department_id'] }}">
                        <select class="form-control" disabled>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ session('user')['department_id'] == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tên Tài liệu <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Nhập tên tài liệu">
                    </div>

                    <div class="form-group">
                        <label>Loại Tài liệu</label>
                        <select name="document_types_id[]" class="form-control select2" multiple="multiple" data-placeholder="Chọn loại tài liệu" style="width: 100%;">
                            @foreach ($document_types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn File hoặc Đính kèm</label>
                        <div class="input-group">
                            <input type="text" name="filepath" id="create_filepath" class="form-control" placeholder="URL hoặc chọn file đính kèm...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" onclick="document.getElementById('create_file_attachment').click()">
                                    <i class="fas fa-paperclip"></i> Đính kèm
                                </button>
                                <input type="file" name="file_attachment" id="create_file_attachment" style="display: none;">
                            </div>
                        </div>
                        <small class="text-muted" id="file_name_display"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kho <span class="text-danger">*</span></label>
                                <select id="create_warehouse_id" class="form-control" required>
                                    <option value="">-- Chọn Kho --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kệ <span class="text-danger">*</span></label>
                                <select id="create_shelf_id" class="form-control" required>
                                    <option value="">-- Chọn Kệ --</option>
                                    @foreach ($shelves as $shelf)
                                        <option value="{{ $shelf->id }}" data-warehouse="{{ $shelf->warehouse_id }}">{{ $shelf->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tầng <span class="text-danger">*</span></label>
                                <select id="create_tier_id" class="form-control" required>
                                    <option value="">-- Chọn Tầng --</option>
                                    @foreach ($tiers as $tier)
                                        <option value="{{ $tier->id }}" data-shelf="{{ $tier->shelf_id }}" data-warehouse="{{ $tier->warehouse_id }}">{{ $tier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vị trí chi tiết <span class="text-danger">*</span></label>
                                <select name="location_id" id="create_location_id" class="form-control" required>
                                    <option value="">-- Chọn Vị trí --</option>
                                </select>
                                @if($errors->createErrors->has('location_id'))
                                    <span class="text-danger small">{{ $errors->createErrors->first('location_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ngày hết hạn (Nếu có)</label>
                                <input type="date" name="expired_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu tài liệu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('create_file_attachment');
        const filepathInput = document.getElementById('create_filepath');
        const fileNameDisplay = document.getElementById('file_name_display');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    const fileName = this.files[0].name;
                    fileNameDisplay.textContent = 'Đã chọn: ' + fileName;
                    fileNameDisplay.classList.remove('text-muted');
                    fileNameDisplay.classList.add('text-success', 'font-weight-bold');
                    
                    // Auto fill filepath if it's empty or looks like a filename
                    filepathInput.value = fileName;
                } else {
                    fileNameDisplay.textContent = '';
                }
            });
        }
        // Kho > Kệ > Tầng > Vị trí: chọn cấp thấp tự điền cấp cao
        window.createStorageCascade = initStorageCascade('create', $('#createModal'));
    });
</script>
