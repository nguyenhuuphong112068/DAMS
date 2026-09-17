@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        $homeUserId = session('user')['userId'] ?? null;
        $canViewDocument = user_has_permission($homeUserId, 'document.view', 'boolean');
        $canViewMap = user_has_permission($homeUserId, 'map.view', 'boolean');
        $canViewWarehouse =
            user_has_permission($homeUserId, 'warehouse.view', 'boolean') ||
            user_has_permission($homeUserId, 'location.view', 'boolean');
        $canViewMaster =
            user_has_permission($homeUserId, 'department.view', 'boolean') ||
            user_has_permission($homeUserId, 'status.view', 'boolean') ||
            user_has_permission($homeUserId, 'documentType.view', 'boolean');
        $canViewPolicy =
            user_has_permission($homeUserId, 'user.view', 'boolean') ||
            user_has_permission($homeUserId, 'role.view', 'boolean') ||
            user_has_permission($homeUserId, 'permission.view', 'boolean');
        $canViewAudit = user_has_permission($homeUserId, 'auditTrail.view', 'boolean');
        $canViewRouting =
            user_has_permission($homeUserId, 'routing.view', 'boolean') &&
            Route::has('pages.documentStorage.routing.list');
    @endphp

    <div class="content-wrapper home-wrapper">
        <!-- Ambient Decorative Glowing Orbs -->
        <div class="home-bg-orb-1"></div>
        <div class="home-bg-orb-2"></div>

        <div class="container-fluid py-4 px-md-4 position-relative" style="z-index: 1;">

            <!-- Hero Banner -->
            <div class="home-hero mb-4">
                <div class="home-hero-content">
                    <div class="home-hero-badge">
                        <span class="pulse-dot"></span>
                        <span class="badge-text">STELLAPHARMA &bull; HỆ THỐNG QUẢN LÝ KHO HỒ SƠ</span>
                    </div>

                    <h1 class="home-hero-title">
                        Chào mừng trở lại, <span class="text-highlight">{{ session('user')['fullName'] ?? 'bạn' }}</span> 👋
                    </h1>

                    <div class="home-hero-actions mt-3">
                        @if ($canViewDocument)
                            <a href="{{ route('pages.documentStorage.document.list') }}"
                                class="btn btn-hero-primary me-2 mb-2">
                                <i class="fas fa-search me-2"></i> Tra Cứu Tài Liệu
                            </a>
                        @endif
                        @if ($canViewMap)
                            <a href="{{ route('pages.storageLocation.map.list') }}" class="btn btn-hero-outline mb-2">
                                <i class="fas fa-th me-2"></i> Sơ Đồ Kho Trực Quan
                            </a>
                        @endif
                    </div>
                </div>

                <div class="home-hero-graphic d-none d-lg-flex">
                    <div class="hero-card-floating">
                        <div class="hero-icon-bubble">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="hero-floating-stat">
                            <span class="stat-number">{{ number_format($stats['total_documents'] ?? 5128) }}</span>
                            <span class="stat-label">Hồ sơ đã lưu kho</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: rgba(0, 58, 79, 0.08); color: var(--primary-navy);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">{{ number_format($stats['total_documents'] ?? 0) }}</div>
                            <div class="stat-card-title">Hồ Sơ Đang Quản Lý</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: rgba(205, 199, 23, 0.16); color: #8a8410;">
                            <i class="fas fa-th"></i>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">{{ number_format($stats['total_locations'] ?? 0) }}</div>
                            <div class="stat-card-title">Vị Trí Ô Chứa</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">{{ number_format($stats['total_warehouses'] ?? 0) }}</div>
                            <div class="stat-card-title">Kho Lưu Trữ</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">{{ number_format($stats['total_shelves'] ?? 0) }}</div>
                            <div class="stat-card-title">Kệ Chứa Hồ Sơ</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access Section -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="home-section-title mb-0">
                    <i class="fas fa-bolt text-warning me-2"></i>Truy cập nhanh
                </h6>
                <span class="section-hint">Chọn chức năng làm việc nhanh</span>
            </div>

            <div class="row g-3 mb-4">
                @if ($canViewDocument)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.documentStorage.document.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(2, 132, 199, 0.1); color: #0284c7;">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Quản lý Tài liệu</div>
                            <div class="home-card-desc">Danh mục hồ sơ tài liệu, in tem nhãn mã QR và thao tác xuất nhập lưu
                                trữ.</div>
                        </a>
                    </div>
                @endif

                @if ($canViewMap)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.storageLocation.map.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(5, 150, 105, 0.1); color: #059669;">
                                    <i class="fas fa-th"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Sơ Đồ Kho Trực Quan</div>
                            <div class="home-card-desc">Bản đồ ô vị trí không gian 2D/lưới, định vị tức thời vị trí tài liệu
                                trên kệ.</div>
                        </a>
                    </div>
                @endif

                @if ($canViewWarehouse)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.storageLocation.warehouse.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Vị Trí Lưu Trữ</div>
                            <div class="home-card-desc">Quản lý phân cấp linh hoạt: Kho &rarr; Kệ (Shelf) &rarr; Tầng (Tier)
                                &rarr; Vị trí (Location).</div>
                        </a>
                    </div>
                @endif

                @if ($canViewMaster)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.materData.department.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon"
                                    style="background: rgba(0, 58, 79, 0.08); color: var(--primary-navy);">
                                    <i class="fas fa-database"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Dữ Liệu Gốc</div>
                            <div class="home-card-desc">Quản lý danh mục phòng ban, trạng thái tài liệu và danh mục loại hồ
                                sơ.</div>
                        </a>
                    </div>
                @endif

                @if ($canViewRouting)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.documentStorage.routing.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                    <i class="fas fa-route"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Luân Chuyển Hồ Sơ</div>
                            <div class="home-card-desc">Theo dõi quy trình tiếp nhận, chuyển giao và hoàn trả hồ sơ giữa
                                các phòng ban.</div>
                        </a>
                    </div>
                @endif

                @if ($canViewPolicy)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.User.user.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Phân Quyền & Người Dùng</div>
                            <div class="home-card-desc">Quản lý tài khoản, vai trò và phân quyền ma trận truy cập chi tiết.
                            </div>
                        </a>
                    </div>
                @endif

                @if ($canViewAudit)
                    <div class="col-12 col-sm-6 col-md-4 col-xl-4 mb-3">
                        <a href="{{ route('pages.AuditTrail.list') }}" class="home-card">
                            <div class="home-card-header">
                                <div class="home-card-icon" style="background: rgba(100, 116, 139, 0.1); color: #64748b;">
                                    <i class="fas fa-history"></i>
                                </div>
                                <span class="home-card-arrow"><i class="fas fa-arrow-right"></i></span>
                            </div>
                            <div class="home-card-title">Audit Trail</div>
                            <div class="home-card-desc">Nhật ký lịch sử kiểm tra vết thao tác, bảo đảm tính toàn vẹn dữ
                                liệu hệ thống.</div>
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <style>
        /* Modern Architectural Canvas Background */
        .home-wrapper {
            position: relative;
            min-height: calc(100vh - 60px);
            background-color: #f6f8fa;
            background-image:
                radial-gradient(at 0% 0%, rgba(0, 58, 79, 0.04) 0px, transparent 45%),
                radial-gradient(at 100% 0%, rgba(205, 199, 23, 0.08) 0px, transparent 40%),
                radial-gradient(at 50% 100%, rgba(0, 58, 79, 0.03) 0px, transparent 50%),
                radial-gradient(rgba(0, 58, 79, 0.055) 1.2px, transparent 1.2px);
            background-size: 100% 100%, 100% 100%, 100% 100%, 28px 28px;
            background-position: 0 0, 0 0, 0 0, 0 0;
            overflow-x: hidden;
        }

        /* Ambient Glowing Orbs */
        .home-bg-orb-1 {
            position: absolute;
            top: -80px;
            right: -60px;
            width: 440px;
            height: 440px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(205, 199, 23, 0.13) 0%, rgba(205, 199, 23, 0) 70%);
            pointer-events: none;
            z-index: 0;
            filter: blur(40px);
        }

        .home-bg-orb-2 {
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 58, 79, 0.07) 0%, rgba(0, 58, 79, 0) 70%);
            pointer-events: none;
            z-index: 0;
            filter: blur(50px);
        }

        /* Hero Banner */
        .home-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.94) 0%, rgba(255, 255, 255, 0.86) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 22px;
            padding: 34px 40px;
            box-shadow: 0 16px 36px rgba(0, 58, 79, 0.06), 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .home-hero::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 320px;
            height: 100%;
            background: radial-gradient(circle at 100% 20%, rgba(205, 199, 23, 0.12), transparent 70%);
            pointer-events: none;
        }

        .home-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 58, 79, 0.08);
            border-radius: 30px;
            box-shadow: 0 2px 6px rgba(0, 58, 79, 0.04);
        }

        .home-hero-badge .badge-text {
            color: var(--primary-navy, #003A4F);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.8px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .home-hero-title {
            color: var(--primary-navy, #003A4F);
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .home-hero-title .text-highlight {
            background: linear-gradient(120deg, var(--primary-navy, #003A4F) 0%, #0369a1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .home-hero-sub {
            color: #526071;
            max-width: 620px;
            font-size: 0.94rem;
            line-height: 1.55;
            margin-bottom: 0;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--primary-navy, #003A4F) 0%, #054863 100%);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 9px 20px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 6px 16px rgba(0, 58, 79, 0.22);
            transition: all 0.2s ease;
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(0, 58, 79, 0.3);
            color: #ffffff;
        }

        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-navy, #003A4F) !important;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 9px 20px;
            border-radius: 12px;
            border: 1px solid rgba(0, 58, 79, 0.15);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }

        .btn-hero-outline:hover {
            background: #ffffff;
            border-color: var(--primary-navy, #003A4F);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 58, 79, 0.1);
        }

        /* Floating Graphic in Hero */
        .hero-card-floating {
            background: #ffffff;
            border: 1px solid rgba(0, 58, 79, 0.08);
            border-radius: 20px;
            padding: 22px 26px;
            box-shadow: 0 14px 28px rgba(0, 58, 79, 0.08);
            display: flex;
            align-items: center;
            gap: 18px;
            animation: floatSlow 4s ease-in-out infinite;
        }

        @keyframes floatSlow {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .hero-icon-bubble {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-navy, #003A4F) 0%, #034b66 100%);
            color: var(--accent-gold, #CDC717);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 8px 18px rgba(0, 58, 79, 0.25);
        }

        .hero-floating-stat .stat-number {
            display: block;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--primary-navy, #003A4F);
            line-height: 1.2;
        }

        .hero-floating-stat .stat-label {
            font-size: 0.78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Stats Cards */
        .stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 6px 18px rgba(0, 58, 79, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 58, 79, 0.08);
            border-color: rgba(0, 58, 79, 0.12);
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card-value {
            font-size: 1.28rem;
            font-weight: 800;
            color: var(--primary-navy, #003A4F);
            line-height: 1.2;
        }

        .stat-card-title {
            font-size: 0.76rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }

        /* Section Titles */
        .home-section-title {
            color: var(--primary-navy, #003A4F);
            font-size: 0.92rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .section-hint {
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Quick Access Cards */
        .home-card {
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 18px;
            padding: 24px 22px;
            height: 100%;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(0, 58, 79, 0.04);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .home-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: var(--primary-navy, #003A4F);
            transition: height 0.25s ease;
        }

        .home-card:hover {
            transform: translateY(-5px);
            background: #ffffff;
            box-shadow: 0 16px 32px rgba(0, 58, 79, 0.1);
            border-color: rgba(0, 58, 79, 0.12);
            text-decoration: none;
        }

        .home-card:hover::before {
            height: 100%;
        }

        .home-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .home-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }

        .home-card:hover .home-card-icon {
            transform: scale(1.08);
        }

        .home-card-arrow {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .home-card:hover .home-card-arrow {
            background: var(--primary-navy, #003A4F);
            color: #ffffff;
            transform: translateX(3px);
        }

        .home-card-title {
            color: var(--primary-navy, #003A4F);
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 6px;
            letter-spacing: -0.2px;
        }

        .home-card-desc {
            color: #64748b;
            font-size: 0.84rem;
            line-height: 1.5;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .home-hero {
                padding: 24px 20px;
            }

            .home-hero-title {
                font-size: 1.35rem;
            }
        }
    </style>
@endsection
