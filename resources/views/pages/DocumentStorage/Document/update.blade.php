<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="updateModalLabel">Cập nhật Tài Liệu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.document.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bộ Phận/Phòng Ban <span class="text-danger">*</span></label>
                        <input type="hidden" name="department_id" id="update_dept_id">
                        <select class="form-control" id="update_dept_display" disabled>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tên Tài liệu <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="update_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Loại Tài liệu</label>
                        <select name="document_types_id[]" id="update_document_types" class="form-control select2" multiple="multiple" data-placeholder="Chọn loại tài liệu" style="width: 100%;">
                            @foreach ($document_types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Đường dẫn File hoặc Đính kèm mới</label>
                        <div class="input-group">
                            <input type="text" name="filepath" id="update_filepath" class="form-control" placeholder="URL hoặc chọn file đính kèm...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" onclick="document.getElementById('update_file_attachment').click()">
                                    <i class="fas fa-paperclip"></i> Đính kèm
                                </button>
                                <input type="file" name="file_attachment" id="update_file_attachment" style="display: none;">
                            </div>
                        </div>
                        <small class="text-muted" id="update_file_name_display"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kho <span class="text-danger">*</span></label>
                                <select id="update_warehouse_id" class="form-control" required>
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
                                <select id="update_shelf_id" class="form-control" required>
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
                                <select id="update_tier_id" class="form-control" required>
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
                                <select name="location_id" id="update_location_id" class="form-control" required>
                                    <option value="">-- Chọn Vị trí --</option>
                                </select>
                                @if($errors->updateErrors->has('location_id'))
                                    <span class="text-danger small">{{ $errors->updateErrors->first('location_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ngày hết hạn</label>
                                <input type="date" name="expired_date" id="update_expired" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('update_file_attachment');
        const filepathInput = document.getElementById('update_filepath');
        const fileNameDisplay = document.getElementById('update_file_name_display');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    const fileName = this.files[0].name;
                    fileNameDisplay.textContent = 'Đã chọn: ' + fileName;
                    fileNameDisplay.classList.remove('text-muted');
                    fileNameDisplay.classList.add('text-success', 'font-weight-bold');
                    
                    // Auto fill filepath
                    filepathInput.value = fileName;
                } else {
                    fileNameDisplay.textContent = '';
                }
            });
        }
        
        // Reset file display when update modal is opened
        $(document).on('click', '.btn-edit', function() {
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            if (fileInput) fileInput.value = '';
        });
        // Kho > Kệ > Tầng > Vị trí: chọn cấp thấp tự điền cấp cao
        window.updateStorageCascade = initStorageCascade('update', $('#updateModal'));
    });
</script>
