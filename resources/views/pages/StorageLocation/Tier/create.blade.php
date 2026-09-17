<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Tầng Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('pages.storageLocation.tier.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <input type="hidden" name="department_id" class="form-control"
                            value="{{ session('user')['department_id'] }}">
                        <label>Bộ Phận/Phòng Ban <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-control" disabled>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ isset(session('user')['department_id']) && session('user')['department_id'] == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Kho (Nếu có)</label>
                        <select name="warehouse_id" id="create_tier_wh" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Kệ (Shelf) <span class="text-danger">*</span></label>
                        <select name="shelf_id" id="create_tier_shelf" class="form-control" required>
                            <option value="">-- Chọn Kệ --</option>
                            @foreach ($shelves as $sh)
                                <option value="{{ $sh->id }}" data-wh="{{ $sh->warehouse_id }}">{{ $sh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mã Tầng <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="VD: T1-K01" required>
                    </div>
                    <div class="form-group">
                        <label>Tên Tầng <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Tầng 1" required>
                    </div>
                    <div class="form-group">
                        <label>Số Vị Trí Tối Đa</label>
                        <input type="number" name="max_locations" class="form-control" min="1" placeholder="VD: 20">
                        <small class="form-text text-muted">Số ô của tầng này trên sơ đồ kho.</small>
                    </div>
                    <div class="form-group">
                        <label>Thứ Tự Tầng</label>
                        <input type="number" name="position" class="form-control" min="1" placeholder="VD: 1">
                        <small class="form-text text-muted">Tầng 1 nằm dưới cùng trên sơ đồ, số càng lớn càng lên
                            cao.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
