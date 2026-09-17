<div class="modal fade" id="UpdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <form
      action="{{ route('pages.User.permission.update') }}"
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
              Cập Nhật Quyền
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <input type="hidden" class="form-control" name="id" value="">

          {{-- Mã quyền --}}
          <div class="form-group">
            <label for="name">Mã Quyền</label>
            <input type="text" class="form-control" name="name"
              value="{{ old('name') }}" placeholder="vd: document.create">
          </div>
          @error('name','updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- Tên hiển thị --}}
          <div class="form-group">
            <label for="display_name">Tên Hiển Thị</label>
            <input type="text" class="form-control" name="display_name"
              value="{{ old('display_name') }}" placeholder="vd: Tạo Tài Liệu">
          </div>
          @error('display_name','updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- Nhóm chức năng --}}
          <div class="form-group">
            <label for="permission_group">Nhóm Chức Năng</label>
            <select class="form-control" name="permission_group">
                <option value="">-- Chọn nhóm chức năng --</option>
                @foreach ($groups as $groupId => $groupName)
                    <option value="{{ $groupId }}">
                        {{ $groupName }}
                    </option>
                @endforeach
            </select>
          </div>
          @error('permission_group','updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          {{-- Mô tả --}}
          <div class="form-group">
            <label for="description">Mô Tả (Nếu Có)</label>
            <input type="text" class="form-control" name="description"
              value="{{ old('description') }}" placeholder="Không Bắt Buộc">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">
              Lưu
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


{{-- //Show modal nếu có lỗi validation --}}
@if ($errors->updateErrors->any())
<script>
    $(document).ready(function () {
        $('#UpdateModal').modal('show');
    });
</script>
@endif
