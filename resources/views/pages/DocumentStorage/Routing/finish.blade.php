<div class="modal fade" id="finishModal" tabindex="-1" role="dialog" aria-labelledby="finishModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="finishModalLabel">
                    <i class="fas fa-check-circle"></i> Kết Thúc Luân Chuyển &amp; Lưu Vào Kho
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.documentStorage.routing.finish') }}" method="POST">
                @csrf
                <input type="hidden" name="routing_id" id="finish_routing_id">

                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Chọn vị trí lưu trữ hồ sơ để hoàn tất luân chuyển. Hồ sơ sẽ được cập nhật trạng thái "Đã lưu trữ".
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kho <span class="text-danger">*</span></label>
                                <select id="finish_warehouse_id" class="form-control" required>
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
                                <select id="finish_shelf_id" class="form-control" required disabled>
                                    <option value="">-- Chọn Kệ --</option>
                                    @foreach ($shelves as $shelf)
                                        <option value="{{ $shelf->id }}" data-warehouse="{{ $shelf->warehouse_id }}">
                                            {{ $shelf->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tầng <span class="text-danger">*</span></label>
                                <select id="finish_tier_id" class="form-control" required disabled>
                                    <option value="">-- Chọn Tầng --</option>
                                    @foreach ($tiers as $tier)
                                        <option value="{{ $tier->id }}" data-shelf="{{ $tier->shelf_id }}" data-warehouse="{{ $tier->warehouse_id }}">
                                            {{ $tier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vị trí chi tiết <span class="text-danger">*</span></label>
                                <select name="location_id" id="finish_location_id" class="form-control" required
                                    disabled>
                                    <option value="">-- Chọn Vị trí --</option>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc->id }}" data-tier="{{ $loc->tier_id }}" data-shelf="{{ $loc->shelf_id }}">
                                            {{ $loc->name }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->finishErrors->has('location_id'))
                                    <span class="d-block text-danger small">{{ $errors->finishErrors->first('location_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú kết thúc</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Ghi chú khi đưa hồ sơ vào lưu trữ..."></textarea>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Sau khi kết thúc, hồ sơ không thể chuyển tiếp nữa và chặng đang chờ (nếu có) sẽ được
                        đánh dấu đã nhận.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-archive"></i> Kết thúc &amp; Lưu trữ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Lọc phân cấp Kho -> Kệ -> Tầng -> Vị trí
        const warehouseSelect = $('#finish_warehouse_id');
        const shelfSelect = $('#finish_shelf_id');
        const tierSelect = $('#finish_tier_id');
        const locationSelect = $('#finish_location_id');

        warehouseSelect.on('change', function() {
            const whId = $(this).val();
            shelfSelect.val('').prop('disabled', !whId);
            shelfSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr('data-warehouse') == whId || !$(el).attr('data-warehouse')).show();
            tierSelect.val('').prop('disabled', true);
            locationSelect.val('').prop('disabled', true);
        });

        shelfSelect.on('change', function() {
            const shelfId = $(this).val();
            tierSelect.val('').prop('disabled', !shelfId);
            tierSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr('data-shelf') == shelfId).show();
            locationSelect.val('').prop('disabled', true);
        });

        tierSelect.on('change', function() {
            const tierId = $(this).val();
            locationSelect.val('').prop('disabled', !tierId);
            locationSelect.find('option').hide().filter((i, el) => !$(el).val() || $(el).attr('data-tier') == tierId).show();
        });
    });
</script>
