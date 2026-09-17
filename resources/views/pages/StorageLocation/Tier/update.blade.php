<div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập Nhật Tầng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('pages.storageLocation.tier.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bộ Phận/Phòng Ban <span class="text-danger">*</span></label>
                        <select name="department_id" id="update_dept" class="form-control" required>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Kho</label>
                        <select name="warehouse_id" id="update_wh" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thuộc Kệ (Shelf) <span class="text-danger">*</span></label>
                        <select name="shelf_id" id="update_shelf" class="form-control" required>
                            <option value="">-- Chọn Kệ --</option>
                            @foreach($shelves as $sh)
                                <option value="{{ $sh->id }}" data-wh="{{ $sh->warehouse_id }}">{{ $sh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mã Tầng <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="update_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tên Tầng <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="update_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Số Vị Trí Tối Đa</label>
                        <input type="number" name="max_locations" id="update_max_locations" class="form-control" min="1">
                    </div>
                    <div class="form-group">
                        <label>Thứ Tự Tầng</label>
                        <input type="number" name="position" id="update_position" class="form-control" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
