<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Vị trí Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('pages.storageLocation.location.store') }}" method="POST">
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
                        <label>Thuộc Kho</label>
                        <select name="warehouse_id" id="create_loc_wh" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Kệ (Shelf)</label>
                        <select name="shelf_id" id="create_loc_shelf" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach ($shelves as $sh)
                                <option value="{{ $sh->id }}" data-wh="{{ $sh->warehouse_id }}">{{ $sh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Tầng (Tier)</label>
                        <select name="tier_id" id="create_loc_tier" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach ($tiers as $t)
                                <option value="{{ $t->id }}" data-shelf="{{ $t->shelf_id }}" data-wh="{{ $t->warehouse_id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mã Vị trí <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="VD: VT-01" required>
                    </div>
                    <div class="form-group">
                        <label>Tên Vị trí <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Hộp 01 / Ngăn 01" required>
                    </div>
                    <div class="form-group">
                        <label>Thứ Tự Ô Trong Tầng</label>
                        <input type="number" name="position" class="form-control" min="1" placeholder="VD: 1">
                        <small class="form-text text-muted">Quyết định ô nằm ở cột thứ mấy trên sơ đồ kho.</small>
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
