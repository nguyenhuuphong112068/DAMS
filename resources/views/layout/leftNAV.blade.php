<style>
    /* Modern Sidebar Styles */
    .main-sidebar {
        background-color: white !important;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.03) !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
    }

    .sidebar {
        padding-top: 20px;
    }

    .nav-pills .nav-link {
        color: #64748b !important;
        margin: 4px 15px;
        border-radius: var(--border-radius-md);
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        padding: 10px 15px;
    }

    .nav-pills .nav-link i {
        font-size: 1.1rem;
        width: 25px;
        margin-right: 10px;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(0, 58, 79, 0.05) !important;
        color: var(--primary-navy) !important;
        transform: translateX(5px);
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary-navy) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(0, 58, 79, 0.2);
    }

    .nav-pills .nav-link.active i {
        color: white !important;
    }

    .brand-link {
        border-bottom: 0 !important;
        padding: 15px 0;
    }

    .nav-header {
        padding: 15px 25px 5px !important;
        color: #94a3b8 !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('pages.general.home') }}" class="brand-link text-center">
        <img src="{{ asset('img/iconstella.svg') }}" alt="Logo" style="width: 50px; height: auto;">
        {{-- Tên dài hơn "LMS-SYSTEM" cũ nên giảm cỡ chữ để xuống dòng gọn trong sidebar --}}
        <span class="brand-text fw-bold d-block mt-2 library-title"
            style="color: var(--primary-navy); font-size: 0.95rem; line-height: 1.3;">
            Quản Lý<br>Kho Hồ Sơ
        </span>
    </a>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column mt-5" data-widget="treeview" role="menu"
                data-accordion="false">

                @php
                    $currentUserId = session('user')['userId'] ?? null;

                    // 1. Chuyển Bộ Phận
                    $canSwitchDept = user_has_permission($currentUserId, 'department.switch', 'boolean');

                    // 2. Dữ Liệu Gốc
                    $canViewDepartment = user_has_permission($currentUserId, 'department.view', 'boolean');
                    $canViewStatus     = user_has_permission($currentUserId, 'status.view', 'boolean');
                    $canViewDocType    = user_has_permission($currentUserId, 'documentType.view', 'boolean');
                    $hasMasterData     = $canViewDepartment || $canViewStatus || $canViewDocType;

                    // 3. Vị Trí Lưu Trữ
                    $canViewWarehouse  = user_has_permission($currentUserId, 'warehouse.view', 'boolean');
                    $canViewShelf      = user_has_permission($currentUserId, 'shelf.view', 'boolean');
                    $canViewTier       = user_has_permission($currentUserId, 'tier.view', 'boolean');
                    $canViewLocation   = user_has_permission($currentUserId, 'location.view', 'boolean');
                    $hasStorageLocation = $canViewWarehouse || $canViewShelf || $canViewTier || $canViewLocation;

                    // Sơ Đồ Kho cũng nằm dưới /storageLocation nhưng là mục riêng
                    $inStorageTree =
                        request()->routeIs('pages.storageLocation.*') &&
                        !request()->routeIs('pages.storageLocation.map.*');

                    // 4. Sơ Đồ Kho
                    $canViewMap = user_has_permission($currentUserId, 'map.view', 'boolean');

                    // 5. Quản lý Tài liệu
                    $canViewDocument = user_has_permission($currentUserId, 'document.view', 'boolean');

                    // 6. Luân chuyển Hồ sơ
                    $canViewRouting = user_has_permission($currentUserId, 'routing.view', 'boolean');

                    // 7. Quản Trị
                    $canViewAdmin      = user_has_permission($currentUserId, 'admin.view', 'boolean');
                    $canViewUser       = user_has_permission($currentUserId, 'user.view', 'boolean');
                    $canViewRole       = user_has_permission($currentUserId, 'role.view', 'boolean');
                    $canViewPermission = user_has_permission($currentUserId, 'permission.view', 'boolean');
                    $canViewAuditTrail = user_has_permission($currentUserId, 'auditTrail.view', 'boolean');
                    $hasUserPolicy     = $canViewUser || $canViewRole || $canViewPermission;
                @endphp

                <!-- Droplist Menu Chuyển Bộ Phận  -->
                @if ($canSwitchDept)
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="fas fa-building"></i>
                            <p>
                                {{ session('user')['selected_department'] ?? 'Bộ phận' }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @php
                                $departments = DB::table('deparments')->get();
                            @endphp
                            @foreach ($departments as $dept)
                                <li class="nav-item">
                                    <a href="{{ route('switch', ['selected_department' => $dept->shortName, 'redirect' => url()->current()]) }}"
                                        class="nav-link">
                                        <i
                                            class="far fa-circle nav-icon {{ (session('user')['selected_department'] ?? '') == $dept->shortName ? 'text-danger' : '' }}"></i>
                                        <p>{{ $dept->shortName }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif


                <!-- Droplist Menu Dữ Liệu Gốc  -->
                @if ($hasMasterData)
                    <li class="nav-item has-treeview {{ str_contains(url()->current(), 'materData') ? 'menu-open' : '' }}">
                        <a href="#"
                            class="nav-link {{ str_contains(url()->current(), 'materData') ? 'active' : '' }}">
                            <i class="fas fa-database"></i>
                            <p>
                                Dữ Liệu Gốc
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if ($canViewDepartment)
                                <li class="nav-item"><a href="{{ route('pages.materData.department.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.materData.department.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-info"></i>
                                        <p>Phòng Ban</p>
                                    </a></li>
                            @endif
                            @if ($canViewStatus)
                                <li class="nav-item"><a href="{{ route('pages.materData.status.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.materData.status.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-warning"></i>
                                        <p>Trạng Thái</p>
                                    </a></li>
                            @endif
                            @if ($canViewDocType)
                                <li class="nav-item"><a href="{{ route('pages.materData.documentType.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.materData.documentType.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-success"></i>
                                        <p>Loại Tài Liệu</p>
                                    </a></li>
                            @endif
                        </ul>
                    </li>
                @endif



                <!-- Droplist Menu Vị Trí Lưu Trữ -->
                @if ($hasStorageLocation)
                    <li class="nav-item has-treeview {{ $inStorageTree ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $inStorageTree ? 'active' : '' }}">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>
                                Vị Trí Lưu Trữ
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if ($canViewWarehouse)
                                <li class="nav-item"><a href="{{ route('pages.storageLocation.warehouse.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.storageLocation.warehouse.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-primary"></i>
                                        <p>Kho</p>
                                    </a></li>
                            @endif
                            @if ($canViewShelf)
                                <li class="nav-item"><a href="{{ route('pages.storageLocation.shelf.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.storageLocation.shelf.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-info"></i>
                                        <p>Kệ</p>
                                    </a></li>
                            @endif
                            @if ($canViewTier)
                                <li class="nav-item"><a href="{{ route('pages.storageLocation.tier.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.storageLocation.tier.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-warning"></i>
                                        <p>Tầng</p>
                                    </a></li>
                            @endif
                            @if ($canViewLocation)
                                <li class="nav-item"><a href="{{ route('pages.storageLocation.location.list') }}"
                                        class="nav-link {{ request()->routeIs('pages.storageLocation.location.*') ? 'active' : '' }}"><i
                                            class="far fa-circle nav-icon text-danger"></i>
                                        <p>Vị Trí</p>
                                    </a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                <!-- Sơ Đồ Kho -->
                @if ($canViewMap)
                    <li class="nav-item">
                        <a href="{{ route('pages.storageLocation.map.list') }}"
                            class="nav-link {{ request()->routeIs('pages.storageLocation.map.*') ? 'active' : '' }}">
                            <i class="fas fa-th"></i>
                            <p>Sơ Đồ Kho</p>
                        </a>
                    </li>
                @endif

                <!-- Quản lý lưu trữ -->
                @if ($canViewDocument)
                    <li class="nav-item">
                        <a href="{{ route('pages.documentStorage.document.list') }}"
                            class="nav-link {{ str_contains(url()->current(), 'documentStorage/document') ? 'active' : '' }}">
                            <i class="fas fa-file-contract"></i>
                            <p>Quản lý lưu trữ</p>
                        </a>
                    </li>
                @endif

                <!-- Luân chuyển Hồ sơ -->
                {{-- Route::has(): nếu server chưa có route (deploy thiếu file / cache route cũ)
                         thì chỉ ẩn mục menu, không để route() ném lỗi làm sập MỌI trang. --}}
                @if (Route::has('pages.documentStorage.routing.list') && $canViewRouting)
                    {{-- <li class="nav-item">
                            <a href="{{ route('pages.documentStorage.routing.list') }}"
                                class="nav-link {{ str_contains(url()->current(), 'documentStorage/routing') ? 'active' : '' }}">
                                <i class="fas fa-route"></i>
                                <p>Luân chuyển Hồ sơ</p>
                            </a>
                        </li> --}}
                @endif



                <!-- User Policy -->
                @if ($canViewAdmin)
                    <li class="nav-header">QUẢN TRỊ</li>
                    @if ($hasUserPolicy)
                        <li class="nav-item has-treeview {{ str_contains(url()->current(), 'User/') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ str_contains(url()->current(), 'User/') ? 'active' : '' }}">
                                <i class="fas fa-user-shield"></i>
                                <p>Phân Quyền <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if ($canViewUser)
                                    <li class="nav-item"><a href="{{ route('pages.User.user.list') }}" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>User</p>
                                        </a></li>
                                @endif
                                @if ($canViewRole)
                                    <li class="nav-item"><a href="{{ route('pages.User.role.list') }}" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>Nhóm Quyền</p>
                                        </a></li>
                                @endif
                                @if ($canViewPermission)
                                    <li class="nav-item"><a href="{{ route('pages.User.permission.list') }}" class="nav-link"><i
                                                class="far fa-circle nav-icon"></i>
                                            <p>Quyền</p>
                                        </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($canViewAuditTrail)
                        <li class="nav-item mt-3">
                            <a href="{{ route('pages.AuditTrail.list') }}" class="nav-link">
                                <i class="fas fa-history"></i>
                                <p>Audit Trail</p>
                            </a>
                        </li>
                    @endif
                @endif


            </ul>
        </nav>
    </div>
</aside>
