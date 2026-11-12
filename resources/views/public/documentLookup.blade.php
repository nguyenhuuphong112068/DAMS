{{--
|--------------------------------------------------------------------------
| TRA CỨU VỊ TRÍ HỒ SƠ - TRANG CÔNG KHAI (không cần đăng nhập), port từ "Tra cứu tồn kho" của WMS
|--------------------------------------------------------------------------
| Standalone, không dùng layout AdminLTE (giống trang đăng nhập). Người dùng chọn 1 bộ phận + gõ (hoặc quét QR
| trên nhãn hồ sơ bằng camera) mã hồ sơ / tên / người sở hữu / mã vị trí -> xem hồ sơ đang nằm ở Kho / Kệ / Tầng /
| Vị trí nào, theo 3 kiểu: Thẻ (dựng ở đây), 3D từng kệ và Mặt bằng cả kho (pages.shared.zone3d).
| Dữ liệu do PublicDocumentLookupController dựng sẵn.
--}}
@php
    $hasZones = $result && count($result['zones']['tiers']) > 0;
    $appName = config('app.name', 'Quản Lý Kho Hồ Sơ');
    $stateLabels = ['ok' => 'Đang lưu', 'warn' => 'Sắp hết hạn lưu trữ', 'danger' => 'Quá hạn lưu trữ'];
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Tra cứu vị trí hồ sơ | {{ $appName }}</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    {{-- icon của sơ đồ 3D dùng chung (pages.shared.shelf3d) --}}
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/fontawesome-free/css/all.min.css') }}">

    <style>
        :root {
            --primary-navy: #003A4F;
            --accent-gold: #CDC717;
            --primary: #0A5570;
            --primary-dark: #003A4F;
            --primary-light: #2B7D99;
            --primary-lighter: #93BFCE;
            --primary-soft: #E6F1F5;
            --primary-rgb: 10, 85, 112;
            --accent: #17B8D4;
            --bg-neutral: #F5F8FA;
            --text-main: #2D3748;
            --text-muted: #6B7A90;
            --border-radius-lg: 12px;
            --border-radius-md: 8px;
            --shadow-sm: 0 1px 3px rgba(var(--primary-rgb), .12);
            --shadow-md: 0 6px 18px rgba(var(--primary-rgb), .16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-main);
            background: linear-gradient(135deg, #EEF4F7 0%, #DCE9EF 45%, #C9DDE6 100%);
            padding: 28px 24px 60px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(var(--primary-rgb), .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--primary-rgb), .05) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .lk-shell { position: relative; width: 100%; margin: 0 auto; }

        /* ---------- Header ---------- */
        .lk-top { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 16px; }
        .lk-brand { display: flex; align-items: center; gap: 12px; }
        .lk-brand .logo-box {
            width: 46px; height: 46px; border-radius: 12px; background: var(--primary-navy);
            display: flex; align-items: center; justify-content: center;
        }
        .lk-brand .logo-box i { color: var(--accent-gold); font-size: 1.35rem; }
        .lk-brand h1 { margin: 0; font-size: 1.15rem; font-weight: 700; letter-spacing: 1px; color: var(--primary-navy); text-transform: uppercase; }
        .lk-brand span { font-size: .74rem; color: var(--text-muted); letter-spacing: .5px; }

        .lk-login-link {
            margin-left: auto; font-size: .84rem; font-weight: 600; color: var(--primary-navy); text-decoration: none;
            border: 1px solid var(--primary-lighter); border-radius: 999px; padding: 7px 16px; transition: all .3s;
        }
        .lk-login-link:hover { background: var(--primary-navy); color: #fff; border-color: var(--primary-navy); }

        /* ---------- Panel ---------- */
        .lk-panel {
            background: #fff; border: 1px solid #DCE6EC; border-radius: var(--border-radius-lg);
            box-shadow: 0 4px 6px rgba(0, 0, 0, .05); padding: 22px 24px; margin-bottom: 18px;
        }
        .lk-form { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
        .lk-field { display: flex; flex-direction: column; gap: 5px; }
        .lk-field.dep { flex: 1 1 260px; }
        .lk-field.kw { flex: 2 1 380px; }
        .lk-field label { font-size: .78rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--text-muted); margin: 0; }
        .lk-field .form-control {
            height: auto;
            border-radius: var(--border-radius-lg); border: 1px solid #DDE6EC; background: #F8FAFB; padding: 11px 14px; font-size: .95rem;
        }
        .lk-field .form-control:focus {
            border-color: var(--primary-light); background: #fff; box-shadow: 0 0 0 4px rgba(var(--primary-rgb), .12); outline: none;
        }
        .lk-kw-row { display: flex; gap: 6px; }
        .lk-kw-row .form-control { flex: 1 1 auto; min-width: 0; }
        .lk-cam {
            flex: none; width: 50px; border-radius: var(--border-radius-lg); border: 1px solid var(--primary-lighter);
            background: #fff; color: var(--primary); font-size: 1.1rem; transition: all .3s;
        }
        .lk-cam:hover:not(:disabled) { background: var(--primary-soft); }
        .lk-cam:disabled { opacity: .45; cursor: not-allowed; }

        .lk-btn {
            background: var(--primary-navy); color: #fff; border: none; border-radius: var(--border-radius-lg);
            padding: 12px 26px; font-weight: 700; letter-spacing: .5px; box-shadow: 0 6px 16px rgba(0, 58, 79, .28);
            transition: all .3s; white-space: nowrap;
        }
        .lk-btn:hover { background: #002D3D; transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0, 58, 79, .34); }

        .lk-hint {
            background: var(--primary-soft); border-left: 3px solid var(--primary-light); border-radius: var(--border-radius-md);
            padding: 12px 16px; font-size: .88rem; color: var(--text-main);
        }
        .lk-warn {
            background: #FFF6E5; border-left: 3px solid #F59E0B; border-radius: var(--border-radius-md);
            padding: 12px 16px; font-size: .88rem; color: #B25E09; margin-bottom: 14px;
        }

        /* ---------- Thanh tổng quan + công cụ (zone3d chèn nút Thẻ | 3D | Mặt bằng vào .mz-tools) ---------- */
        .mz-head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .lk-stats { display: flex; flex-wrap: wrap; gap: 10px; }
        .lk-stat { background: #fff; border: 1px solid #DCE6EC; border-radius: var(--border-radius-md); padding: 8px 16px; min-width: 92px; }
        .lk-stat b { display: block; font-size: 1.3rem; font-weight: 700; color: var(--primary-navy); line-height: 1.15; }
        .lk-stat span { font-size: .66rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748B; }
        .mz-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .mz-search { position: relative; }
        .mz-search input { padding-left: 32px; min-width: 260px; border-radius: var(--border-radius-md); border: 1px solid #DDE6EC; font-size: .9rem; }
        .mz-search > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--primary-lighter); }

        .lk-scope { font-size: .84rem; color: var(--text-muted); margin-bottom: 12px; }
        .lk-scope b { color: var(--primary-dark); }

        /* ---------- Kho ---------- */
        .lk-wh { background: #fff; border: 1px solid #DCE6EC; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 16px; }
        .lk-wh-head {
            display: flex; align-items: center; gap: 12px; padding: 13px 18px; cursor: pointer;
            background: linear-gradient(135deg, var(--primary-navy), var(--primary)); color: #fff;
        }
        .lk-wh-head .ic { width: 34px; height: 34px; flex: none; border-radius: 10px; background: rgba(255, 255, 255, .15); display: flex; align-items: center; justify-content: center; }
        .lk-wh-head .ic i { color: var(--accent-gold); }
        .lk-wh-title { font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        .lk-wh-sub { font-size: .74rem; opacity: .85; }
        .lk-wh-meta { margin-left: auto; }
        .lk-pill { background: rgba(255, 255, 255, .18); border-radius: 999px; padding: 3px 11px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .lk-caret { transition: transform .3s; }
        .lk-wh.is-closed .lk-caret { transform: rotate(-90deg); }
        .lk-wh.is-closed .lk-wh-body { display: none; }
        .lk-wh-body { padding: 16px 18px 4px; }

        /* ---------- Kệ / Tầng ---------- */
        .lk-shelf { border-left: 3px solid var(--accent-gold); padding-left: 14px; margin-bottom: 16px; }
        .lk-shelf-head { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; color: var(--primary-dark); font-weight: 700; }
        .lk-shelf-head .tag { background: var(--primary-soft); color: var(--primary); border-radius: 999px; padding: 2px 10px; font-size: .7rem; font-weight: 700; }
        .lk-tier { background: var(--bg-neutral); border: 1px solid #E5EDF2; border-radius: var(--border-radius-md); padding: 10px 12px; margin-bottom: 10px; }
        .lk-tier-head { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: .76rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .4px; }
        .lk-tier-head .sub { margin-left: auto; font-size: .7rem; font-weight: 600; color: #94A3B8; text-transform: none; letter-spacing: 0; }

        /* ---------- Ô vị trí ---------- */
        .lk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
        .lk-cell {
            display: block; width: 100%; text-align: left; background: #fff; padding: 11px 13px;
            border: 1px solid #DCE6EC; border-top: 3px solid #16A34A; border-radius: var(--border-radius-lg); transition: all .3s; cursor: pointer;
        }
        .lk-cell.is-warn { border-top-color: #F59E0B; }
        .lk-cell.is-danger { border-top-color: #DC2626; }
        .lk-cell:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .lk-cell-top { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .lk-cell-code { font-weight: 700; color: var(--primary-dark); font-size: .86rem; }
        .lk-cell-code i { color: var(--accent-gold); }
        .lk-cell-path { font-size: .68rem; color: #94A3B8; margin-bottom: 7px; }
        .lk-doc { font-size: .78rem; line-height: 1.45; }
        .lk-doc + .lk-doc { margin-top: 5px; padding-top: 5px; border-top: 1px dashed #E2E8F0; }
        .lk-doc b { color: var(--primary-dark); }
        .lk-doc .nm { color: #475569; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .lk-doc .meta { font-size: .7rem; color: #94A3B8; }
        .lk-lock { color: #B45309; font-size: .72rem; font-weight: 600; }

        /* ---------- Hồ sơ chưa xếp vị trí + rỗng ---------- */
        .lk-unzoned { border: 1px dashed #F59E0B; background: #FFFBEB; border-radius: var(--border-radius-lg); padding: 14px 18px; margin-bottom: 16px; }
        .lk-unzoned-head { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #B45309; margin-bottom: 10px; }
        .lk-uz-card { background: #fff; border: 1px solid #FDE8C7; border-radius: 10px; padding: 10px 12px; }

        .lk-empty { text-align: center; padding: 44px 20px; color: #94A3B8; background: #fff; border: 1px solid #DCE6EC; border-radius: var(--border-radius-lg); }
        .lk-empty i { font-size: 2.2rem; color: var(--primary-lighter); margin-bottom: 10px; display: block; }
        .mz-noresult { display: none; }

        .lk-footer { text-align: center; font-size: .74rem; color: #94A3B8; margin-top: 26px; }

        /* ---------- Modal chi tiết ---------- */
        .lk-modal-table th { background: var(--primary-soft); color: var(--primary-dark); font-size: .78rem; }
        .lk-modal-table td { font-size: .84rem; vertical-align: middle; }
        .lk-badge { display: inline-block; border-radius: 999px; padding: 2px 9px; font-size: .7rem; font-weight: 700; }
        .lk-badge.ok { background: #DCFCE7; color: #166534; }
        .lk-badge.warn { background: #FEF3C7; color: #92400E; }
        .lk-badge.danger { background: #FEE2E2; color: #991B1B; }
        #lkDetailModal .modal-content, #cameraScanModal .modal-content {
            border-radius: var(--border-radius-lg); border: 1px solid rgba(255, 255, 255, .6);
            background: rgba(255, 255, 255, .92); backdrop-filter: blur(10px);
        }
        #cameraScanModal .md-sub { display: block; margin-top: 8px; color: var(--text-muted); }

        @media (max-width: 640px) {
            body { padding: 18px 14px 40px; }
            .lk-form { flex-direction: column; align-items: stretch; }
            .lk-btn { width: 100%; }
            .mz-search input { min-width: 0; width: 100%; }
            .mz-tools, .mz-search { width: 100%; }
        }
    </style>
</head>

<body>
    <div class="lk-shell">

        <div class="lk-top">
            <div class="lk-brand">
                <div class="logo-box"><i class="bi bi-archive-fill"></i></div>
                <div>
                    <h1>Tra cứu vị trí hồ sơ</h1>
                    <span>{{ $appName }}</span>
                </div>
            </div>
            <a href="{{ url('/') }}" class="lk-login-link">
                <i class="bi bi-box-arrow-in-right mr-1"></i> Đăng nhập hệ thống
            </a>
        </div>

        {{-- ---------- Bộ lọc tra cứu ---------- --}}
        <div class="lk-panel">
            <form method="GET" action="{{ route('publicDocumentLookup') }}" class="lk-form" id="lkForm">
                <div class="lk-field dep">
                    <label for="department_id">Bộ phận <span style="color:#DC2626">*</span></label>
                    <select name="department_id" id="department_id" class="form-control" required>
                        <option value="">— Chọn bộ phận —</option>
                        @foreach ($departments as $dep)
                            <option value="{{ $dep->id }}" {{ $departmentId === (int) $dep->id ? 'selected' : '' }}>
                                {{ $dep->name }}{{ $dep->shortName ? ' (' . $dep->shortName . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lk-field kw">
                    <label for="q">Mã hồ sơ, tên hồ sơ, người sở hữu hoặc mã vị trí</label>
                    {{-- scan-box + .btn-camera-scan: pages.shared.cameraScan điền mã quét được rồi bấm Enter --}}
                    <div class="lk-kw-row scan-box">
                        <input type="text" name="q" id="q" class="form-control" value="{{ $keyword }}" autocomplete="off"
                            placeholder="Gõ hoặc quét QR trên nhãn hồ sơ (bỏ trống để xem tổng quan kho)" maxlength="120">
                        <button type="button" class="lk-cam btn-camera-scan" title="Quét mã QR trên nhãn hồ sơ bằng camera">
                            <i class="bi bi-qr-code-scan"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="lk-btn">
                    <i class="bi bi-search mr-1"></i> Tra cứu
                </button>
            </form>
        </div>

        {{-- ---------- Kết quả ---------- --}}
        @if (!$submitted)
            <div class="lk-hint">
                <i class="bi bi-info-circle mr-1"></i>
                Chọn <b>bộ phận</b>, nhập (hoặc quét QR trên nhãn bằng nút <i class="bi bi-qr-code-scan"></i>) mã hồ sơ, tên hồ sơ,
                người sở hữu hay mã vị trí rồi bấm <b>Tra cứu</b> để xem hồ sơ đang nằm ở <b>Kho / Kệ / Tầng / Vị trí</b> nào.
                Kết quả xem được dạng <b>Thẻ</b>, <b>3D</b> từng kệ hoặc <b>Mặt bằng</b> cả kho - vị trí khớp sáng lên và nhấp nháy để dễ tìm.
                Hồ sơ hạn chế chỉ tra được bằng đúng mã hồ sơ / mã vị trí và không hiện tên.
            </div>
        @elseif (!$department)
            <div class="lk-warn">
                <i class="bi bi-exclamation-triangle mr-1"></i>
                Vui lòng chọn một bộ phận hợp lệ để tra cứu.
            </div>
        @else
            @php $t = $result['totals']; @endphp

            <div class="lk-scope">
                Kết quả cho bộ phận <b>{{ $department->name }}</b>
                @if ($keyword !== '') · từ khoá "<b>{{ $keyword }}</b>"@else · <b>tổng quan</b> các kho (chưa nhập từ khoá)@endif
            </div>

            @if ($result['truncated'])
                <div class="lk-warn">
                    <i class="bi bi-exclamation-triangle mr-1"></i>
                    Có hơn {{ $maxMatches }} hồ sơ khớp - chỉ hiện {{ $maxMatches }} hồ sơ đầu tiên. Hãy gõ từ khoá cụ thể hơn.
                </div>
            @endif

            <div class="mz-root" id="lkZone" data-z3d-keyword="{{ $keyword !== '' ? mb_strtolower($keyword) : '' }}">
                <div class="mz-head">
                    <div class="lk-stats">
                        @if ($keyword !== '')
                            <div class="lk-stat"><b>{{ $t['documents'] }}</b><span>Hồ sơ khớp</span></div>
                            <div class="lk-stat"><b>{{ $t['locations'] }}</b><span>Vị trí</span></div>
                            <div class="lk-stat"><b>{{ $t['shelves'] }}</b><span>Kệ</span></div>
                            <div class="lk-stat"><b>{{ $t['warehouses'] }}</b><span>Kho</span></div>
                            @if ($t['unzoned'] > 0)
                                <div class="lk-stat"><b>{{ $t['unzoned'] }}</b><span>Chưa xếp vị trí</span></div>
                            @endif
                        @else
                            <div class="lk-stat"><b>{{ count($result['zones']['warehouses']) }}</b><span>Kho</span></div>
                            <div class="lk-stat"><b>{{ count($result['zones']['shelves']) }}</b><span>Kệ</span></div>
                            <div class="lk-stat"><b>{{ number_format($t['occupied']) }}</b><span>Vị trí có hồ sơ</span></div>
                        @endif
                    </div>
                    <div class="mz-tools">
                        <div class="mz-search">
                            <i class="bi bi-search"></i>
                            <input type="text" id="lkFilter" class="form-control form-control-sm" autocomplete="off"
                                placeholder="Lọc nhanh: vị trí, kệ, mã, tên hồ sơ...">
                        </div>
                    </div>
                </div>

                {{-- Hồ sơ khớp nhưng chưa gán vị trí (ẩn ở chế độ 3D / Mặt bằng) --}}
                @if (count($result['unzoned']) > 0)
                    <div class="lk-unzoned mz-unzoned">
                        <div class="lk-unzoned-head">
                            <i class="bi bi-exclamation-triangle"></i> Chưa xếp vị trí ({{ count($result['unzoned']) }} hồ sơ)
                        </div>
                        <div class="lk-grid">
                            @foreach ($result['unzoned'] as $uz)
                                <div class="lk-uz-card lk-doc-card" data-search="{{ mb_strtolower($uz['code'] . ' ' . $uz['name'] . ' ' . $uz['owner']) }}">
                                    <div class="lk-doc">
                                        <b>{{ $uz['code'] }}</b>
                                        @if ($uz['private'])
                                            <div class="lk-lock"><i class="bi bi-lock-fill"></i> Hồ sơ hạn chế</div>
                                        @else
                                            <div class="nm">{{ $uz['name'] ?: '—' }}</div>
                                        @endif
                                        <div class="meta">Hạn lưu trữ: {{ $uz['expiry'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mz-body">
                    @if ($keyword === '')
                        <div class="lk-empty">
                            <i class="bi bi-diagram-3"></i>
                            Nhập mã / tên hồ sơ để xem thẻ vị trí. Chọn <b>3D</b> hoặc <b>Mặt bằng</b> để xem tổng quan các kho của bộ phận.
                        </div>
                    @elseif (count($result['warehouses']) === 0 && count($result['unzoned']) === 0)
                        <div class="lk-empty">
                            <i class="bi bi-inbox"></i>
                            Không tìm thấy hồ sơ nào khớp với điều kiện tra cứu ở bộ phận này.
                        </div>
                    @endif

                    @foreach ($result['warehouses'] as $wh)
                        @php
                            $whLoc = 0;
                            foreach ($wh['shelves'] as $sh) {
                                foreach ($sh['tiers'] as $tr) { $whLoc += count($tr['locations']); }
                            }
                        @endphp
                        <section class="lk-wh">
                            <div class="lk-wh-head">
                                <i class="bi bi-chevron-down lk-caret"></i>
                                <span class="ic"><i class="bi bi-building"></i></span>
                                <div>
                                    <div class="lk-wh-title">{{ $wh['name'] }}</div>
                                    <div class="lk-wh-sub">{{ $wh['code'] ? $wh['code'] . ' · ' : '' }}{{ count($wh['shelves']) }} kệ · {{ $whLoc }} vị trí khớp</div>
                                </div>
                                <div class="lk-wh-meta"><span class="lk-pill">{{ $whLoc }} vị trí</span></div>
                            </div>

                            <div class="lk-wh-body">
                                @foreach ($wh['shelves'] as $shelf)
                                    <div class="lk-shelf">
                                        <div class="lk-shelf-head">
                                            <i class="bi bi-bookshelf"></i> {{ $shelf['name'] }}
                                            @if ($shelf['code'])<span class="tag">{{ $shelf['code'] }}</span>@endif
                                        </div>

                                        @foreach ($shelf['tiers'] as $tier)
                                            <div class="lk-tier">
                                                <div class="lk-tier-head">
                                                    <i class="bi bi-list"></i> {{ $tier['name'] }}
                                                    <span class="sub">{{ count($tier['locations']) }} vị trí khớp</span>
                                                </div>

                                                <div class="lk-grid">
                                                    @foreach ($tier['locations'] as $loc)
                                                        <button type="button"
                                                            class="lk-cell lk-doc-card {{ $loc['state'] !== 'ok' ? 'is-' . $loc['state'] : '' }}"
                                                            data-key="{{ $loc['key'] }}" data-search="{{ $loc['search'] }}">
                                                            <div class="lk-cell-top">
                                                                <span class="lk-cell-code"><i class="bi bi-geo-alt-fill"></i> {{ $loc['code'] }}</span>
                                                            </div>
                                                            <div class="lk-cell-path">{{ $loc['path'] }}</div>
                                                            @foreach ($loc['items'] as $doc)
                                                                <div class="lk-doc">
                                                                    <b>{{ $doc['code'] }}</b>
                                                                    @if ($doc['private'])
                                                                        <div class="lk-lock"><i class="bi bi-lock-fill"></i> Hồ sơ hạn chế</div>
                                                                    @else
                                                                        <div class="nm">{{ $doc['name'] ?: '—' }}</div>
                                                                    @endif
                                                                    <div class="meta">
                                                                        Hạn lưu trữ: {{ $doc['expiry'] }}
                                                                        @if ($doc['state'] !== 'ok') · <span class="lk-badge {{ $doc['state'] }}">{{ $stateLabels[$doc['state']] }}</span>@endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="lk-empty mz-noresult">
                    <i class="bi bi-search"></i>
                    Không có vị trí nào khớp với từ khoá lọc.
                </div>
            </div>
        @endif

        <div class="lk-footer">
            {{ $appName }} © {{ date('Y') }} – Tra cứu vị trí hồ sơ (chỉ đọc)
        </div>
    </div>

    {{-- ---------- Modal chi tiết một vị trí ---------- --}}
    <div class="modal fade" id="lkDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-geo-alt-fill mr-1" style="color: var(--primary)"></i>
                        <span id="lkDName">Vị trí</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Đóng"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <small class="text-muted">Kho / Kệ / Tầng</small>
                    <div class="font-weight-bold mb-3" id="lkDPath">—</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm lk-modal-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:46px">STT</th>
                                    <th>Mã hồ sơ</th>
                                    <th>Tên hồ sơ</th>
                                    <th>Người sở hữu</th>
                                    <th>Loại</th>
                                    <th class="text-center" style="width:120px">Hạn lưu trữ</th>
                                </tr>
                            </thead>
                            <tbody id="lkDBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('dataTable/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @include('pages.shared.cameraScan')
    @include('pages.shared.zone3d')
    <script>
        $(function () {
            var nodes = @json($result ? (object) $result['nodes'] : new \stdClass);
            var stateLabels = @json($stateLabels);

            // Nhớ bộ phận đã chọn lần trước trên máy này
            var depKey = 'dams_lookup_department';
            var $dep = $('#department_id');
            try {
                if (!$dep.val()) {
                    var saved = localStorage.getItem(depKey);
                    if (saved && $dep.find('option[value="' + saved + '"]').length) $dep.val(saved);
                } else {
                    localStorage.setItem(depKey, $dep.val());
                }
            } catch (e) {}
            $dep.on('change', function () {
                try { if (this.value) localStorage.setItem(depKey, this.value); } catch (e) {}
            });

            // Quét QR bằng camera (pages.shared.cameraScan) điền vào ô rồi bắn phím Enter giả -> gửi form tra cứu
            $('#q').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.originalEvent && $('#department_id').val()) $('#lkForm').trigger('submit');
            });

            // Lọc nhanh trong kết quả: ẩn thẻ + ẩn luôn tầng / kệ / kho rỗng (3D / Mặt bằng đọc cùng ô này)
            function lkApply() {
                var q = ($('#lkFilter').val() || '').toString().trim().toLowerCase();

                $('.lk-doc-card').each(function () {
                    var ok = q === '' || ($(this).attr('data-search') || '').indexOf(q) >= 0;
                    this.style.display = ok ? '' : 'none';
                });
                function shown($els) { return $els.filter(function () { return this.style.display !== 'none'; }).length; }
                $('.lk-tier').each(function () { this.style.display = shown($(this).find('.lk-cell')) ? '' : 'none'; });
                $('.lk-shelf').each(function () { this.style.display = shown($(this).find('.lk-tier')) ? '' : 'none'; });
                $('.lk-wh').each(function () { this.style.display = shown($(this).find('.lk-shelf')) ? '' : 'none'; });
                $('.lk-unzoned').each(function () { this.style.display = shown($(this).find('.lk-uz-card')) ? '' : 'none'; });

                var total = $('.lk-wh, .lk-unzoned').length;
                $('.mz-noresult').css('display', total > 0 && shown($('.lk-wh, .lk-unzoned')) === 0 ? 'block' : 'none');
            }
            $('#lkFilter').on('input', lkApply);

            $(document).on('click', '.lk-wh-head', function () {
                $(this).closest('.lk-wh').toggleClass('is-closed');
            });

            // Một vị trí -> hộp chi tiết hồ sơ (bấm thẻ hoặc thùng hồ sơ trên 3D)
            var modal = new bootstrap.Modal(document.getElementById('lkDetailModal'));
            function openLocation(key) {
                var d = nodes[key];
                if (!d || !d.items) return;

                $('#lkDName').text(d.code || '—');
                $('#lkDPath').text(d.path || '—');

                var $tb = $('#lkDBody').empty();
                d.items.forEach(function (x, i) {
                    var $tr = $('<tr></tr>');
                    $tr.append($('<td class="text-center"></td>').text(i + 1));
                    $tr.append($('<td class="font-weight-bold"></td>').text(x.code));
                    if (x.private) {
                        $tr.append($('<td colspan="3" class="text-muted"></td>').html('<i class="bi bi-lock-fill mr-1"></i>Hồ sơ hạn chế - không hiện thông tin'));
                    } else {
                        $tr.append($('<td></td>').text(x.name || '—'));
                        $tr.append($('<td></td>').text(x.owner || '—'));
                        $tr.append($('<td class="small"></td>').text(x.types || '—'));
                    }
                    var $ex = $('<td class="text-center small"></td>').text(x.expiry);
                    if (x.state !== 'ok') $ex.append('<br>').append($('<span class="lk-badge"></span>').addClass(x.state).text(stateLabels[x.state]));
                    $tr.append($ex);
                    $tb.append($tr);
                });
                modal.show();
            }
            $(document).on('click', '.lk-cell[data-key]', function () { openLocation($(this).attr('data-key')); });

            // Thẻ | 3D | Mặt bằng. Từ khoá tra cứu (data-z3d-keyword) làm vị trí khớp sáng lên + nhấp nháy.
            // Bộ phận chưa khai kệ / tầng / vị trí nào thì chỉ có kiểu Thẻ.
            var root = document.getElementById('lkZone');
            if (root && window.damsZone3d && @json($hasZones)) {
                window.damsZone3d.init(root, {
                    zones: @json($result ? $result['zones'] : new \stdClass),
                    nodes: nodes,
                    open: openLocation,
                    modeKey: 'dams.lookup.zoneView',
                    // Chưa nhập từ khoá thì chỉ có tổng quan -> mở thẳng Mặt bằng
                    defaultMode: 'cards',
                    mode: @json($keyword === '' ? 'room' : null)
                });
            }
        });
    </script>
</body>

</html>
