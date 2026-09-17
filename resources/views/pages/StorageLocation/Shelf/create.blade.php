<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Kệ Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('pages.storageLocation.shelf.store') }}" method="POST">
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
                        <select name="warehouse_id" class="form-control">
                            <option value="">-- Không chọn --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mã Kệ <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="VD: KE-01" required>
                    </div>
                    <div class="form-group">
                        <label>Tên Kệ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Kệ hồ sơ 01" required>
                    </div>
                    <div class="form-group">
                        <label>Số Tầng Tối Đa</label>
                        <input type="number" name="max_tiers" class="form-control" min="1" placeholder="VD: 5">
                        <small class="form-text text-muted">Dùng để vẽ sơ đồ kho: kệ sẽ hiện đủ số tầng này, tầng chưa
                            khai báo hiện dạng nét đứt.</small>
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
