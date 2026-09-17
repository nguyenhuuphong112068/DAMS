<div class="modal fade" id="UpdateRoleModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">

    <form
      action="{{ route('pages.User.role.update') }}"
      method="POST">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <a href="{{ route ('pages.general.home') }}">
              <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.8 ; max-width:45px;">
          </a>

          <h4 class="modal-title w-100 text-center" id="ModalLabel" style="color: #CDC717">
              Đổi Tên Nhóm Quyền
          </h4>

          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <input type="hidden" class="form-control" name="id" value="">

          <div class="form-group">
            <label for="name">Tên Nhóm Quyền</label>
            <input type="text" class="form-control" name="name"
              value="{{ old('name') }}" placeholder="vd: Nhân Viên Kho">
          </div>
          @error('name','updateErrors')
              <div class="alert alert-danger">{{ $message }}</div>
          @enderror

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
        $('#UpdateRoleModal').modal('show');
    });
</script>
@endif
