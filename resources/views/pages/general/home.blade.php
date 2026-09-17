@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection


@section('mainContent')
    <div class="content-wrapper" style="background-color: #f7f9fb;">
        <div class="container-fluid py-4">

            <!-- Hero Banner -->
            <div class="home-hero mb-4">
                <div class="home-hero-text">
                    <p class="home-hero-eyebrow">{{ now()->format('d/m/Y') }}</p>
                    <h2>Chào mừng, {{ session('user')['fullName'] ?? 'bạn' }} 👋</h2>
                    <p class="home-hero-sub">Hệ thống <strong>{{ config('app.name', 'Quản Lý Kho Hồ Sơ') }}</strong>
                        giúp bạn lưu trữ, tra cứu và theo dõi luân chuyển hồ sơ một cách tập trung, an toàn.</p>
                </div>
                <div class="home-hero-icon">
                    <i class="fas fa-archive"></i>
                </div>
            </div>

            <!-- Quick Access -->
            <h6 class="home-section-title">Truy cập nhanh</h6>
            <div class="row g-3 mb-2">

                @if (user_has_any_role(session('user')['userId'], ['Admin']))
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.materData.department.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(0,58,79,0.08); color:var(--primary-navy);">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="home-card-title">Dữ Liệu Gốc</div>
                            <div class="home-card-desc">Phòng ban, trạng thái, loại tài liệu</div>
                        </a>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.storageLocation.warehouse.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(205,199,23,0.15); color:#8a8410;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="home-card-title">Vị Trí Lưu Trữ</div>
                            <div class="home-card-desc">Kho, phòng, kệ và vị trí lưu trữ</div>
                        </a>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.documentStorage.document.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(23,162,184,0.12); color:#17a2b8;">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="home-card-title">Quản lý Tài liệu</div>
                            <div class="home-card-desc">Danh mục hồ sơ đang lưu trữ</div>
                        </a>
                    </div>

                    @if (Route::has('pages.documentStorage.routing.list'))
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('pages.documentStorage.routing.list') }}" class="home-card">
                                <div class="home-card-icon" style="background:rgba(40,167,69,0.12); color:#28a745;">
                                    <i class="fas fa-route"></i>
                                </div>
                                <div class="home-card-title">Luân chuyển Hồ sơ</div>
                                <div class="home-card-desc">Theo dõi quá trình luân chuyển</div>
                            </a>
                        </div>
                    @endif
                @endif

                @if (Route::has('pages.documentStorage.reissue.list'))
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.documentStorage.reissue.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(220,53,69,0.1); color:#dc3545;">
                                <i class="fas fa-redo-alt"></i>
                            </div>
                            <div class="home-card-title">Xin cấp lại Hồ sơ</div>
                            <div class="home-card-desc">Gửi và theo dõi yêu cầu cấp lại</div>
                        </a>
                    </div>
                @endif

                @if (user_has_any_role(session('user')['userId'], ['Admin']))
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.User.user.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(0,58,79,0.08); color:var(--primary-navy);">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="home-card-title">Phân Quyền</div>
                            <div class="home-card-desc">Người dùng, nhóm quyền, quyền</div>
                        </a>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pages.AuditTrail.list') }}" class="home-card">
                            <div class="home-card-icon" style="background:rgba(108,117,125,0.12); color:#6c757d;">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="home-card-title">Audit Trail</div>
                            <div class="home-card-desc">Lịch sử thao tác trên hệ thống</div>
                        </a>
                    </div>
                @endif

            </div>

        </div>
    </div>

    <style>
        .home-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(120deg, #ffffff 0%, #fbfaf2 100%);
            border: 1px solid rgba(0, 58, 79, 0.08);
            border-radius: 18px;
            padding: 32px 36px;
            box-shadow: 0 8px 24px rgba(0, 58, 79, 0.05);
        }

        .home-hero-eyebrow {
            color: var(--accent-gold, #CDC717);
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .home-hero-text h2 {
            color: var(--primary-navy, #003A4F);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .home-hero-sub {
            color: #64748b;
            max-width: 560px;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .home-hero-icon {
            width: 84px;
            height: 84px;
            min-width: 84px;
            border-radius: 20px;
            background: var(--primary-navy, #003A4F);
            color: var(--accent-gold, #CDC717);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            box-shadow: 0 10px 20px rgba(0, 58, 79, 0.25);
        }

        .home-section-title {
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }

        .home-card {
            display: block;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 16px;
            padding: 22px 18px;
            height: 100%;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .home-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 58, 79, 0.1);
            border-color: rgba(0, 58, 79, 0.15);
            text-decoration: none;
        }

        .home-card-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 14px;
        }

        .home-card-title {
            color: var(--primary-navy, #003A4F);
            font-weight: 700;
            font-size: 0.98rem;
            margin-bottom: 4px;
        }

        .home-card-desc {
            color: #94a3b8;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        @media (max-width: 576px) {
            .home-hero {
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .home-hero-icon {
                align-self: flex-end;
            }
        }
    </style>
@endsection
