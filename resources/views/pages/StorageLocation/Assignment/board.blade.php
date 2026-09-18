<div class="content-wrapper">
    <div class="sa mt-3">
        @if ($managers->isEmpty())
            <div class="card sa-empty">
                <div class="card-body">
                    <i class="fas fa-user-tag"></i>
                    <p>Chưa có người dùng nào mang vai trò <b>{{ \App\StorageLocation\ShelfManager::ROLE }}</b>.</p>
                    <p class="text-muted mb-0">Hãy gán vai trò này cho người dùng trong mục Phân Quyền &rsaquo; User, rồi quay lại đây để phân công.</p>
                </div>
            </div>
        @else
            <aside class="card sa-people">
                <div class="sa-people-head">
                    <div class="sa-side-title">Người quản lý kệ</div>
                    <input type="search" id="sa-people-filter" class="form-control form-control-sm" placeholder="Tìm người..."
                        {{ $managers->count() > 6 ? '' : 'hidden' }}>
                </div>
                <div id="sa-people-list" class="sa-people-list"></div>

                <div class="sa-scope">
                    <div class="sa-side-title">
                        Phạm vi đang giao <span id="sa-scope-count" class="sa-count"></span>
                    </div>
                    <ul id="sa-scope-list" class="sa-scope-list"></ul>
                </div>
            </aside>

            <section class="card sa-main" id="sa-main">
                <div class="card-header sa-head">
                    <nav id="sa-crumbs" class="sa-crumbs"></nav>
                    <div id="sa-actions" class="sa-actions"></div>
                </div>
                <div class="card-body">
                    <div class="sa-hintbar">
                        <p id="sa-hint" class="sa-hint"></p>
                        <div id="sa-legend" class="sa-legend">
                            <span><i class="sa-swatch is-on"></i> Người đang chọn phụ trách</span>
                            <span><i class="sa-swatch is-other"></i> Người khác phụ trách</span>
                            <span><i class="sa-swatch"></i> Chưa giao</span>
                            <span data-grid-only><i class="sa-swatch is-off"></i> Đang khoá</span>
                        </div>
                    </div>
                    <div id="sa-view" class="sa-view"></div>
                </div>
                <div class="sa-busy"><i class="fas fa-circle-notch fa-spin"></i></div>
            </section>
        @endif
    </div>
</div>

<style>
    .sa {
        display: grid;
        grid-template-columns: 290px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
        padding: 0 8px;
    }

    @media (max-width: 991px) {
        .sa { grid-template-columns: minmax(0, 1fr); }
    }

    .sa .card { margin-bottom: 16px; }

    .sa-empty { grid-column: 1 / -1; text-align: center; }
    .sa-empty .card-body { padding: 48px 16px; }
    .sa-empty i { font-size: 2.4rem; color: #94a3b8; margin-bottom: 12px; }

    /* ---------- Cột trái: người và phạm vi ---------- */
    .sa-people { position: sticky; top: 70px; overflow: hidden; }

    .sa-people-head { padding: 14px 14px 8px; display: flex; flex-direction: column; gap: 8px; }

    .sa-side-title {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sa-people-list { max-height: 38vh; overflow-y: auto; padding: 0 8px 8px; }

    .sa-person {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        border: 1px solid transparent;
        background: none;
        border-radius: 10px;
        padding: 8px;
        text-align: left;
        cursor: pointer;
        transition: background .15s ease;
    }

    .sa-person:hover { background: #f1f5f9; }

    .sa-person.is-active { background: #ecfdf3; border-color: #86efac; }

    .sa-avatar {
        flex: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #334155;
        font-weight: 700;
        font-size: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sa-person.is-active .sa-avatar { background: #16a34a; color: #fff; }

    .sa-person-text { min-width: 0; flex: 1; }

    .sa-person-name {
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sa-person-meta { font-size: .75rem; color: #64748b; }
    .sa-person-meta b { color: #15803d; }

    .sa-scope { border-top: 1px solid #e2e8f0; padding: 12px 14px 14px; }

    .sa-count {
        background: #e2e8f0;
        color: #334155;
        border-radius: 999px;
        padding: 0 7px;
        font-size: .7rem;
        letter-spacing: 0;
    }

    .sa-scope-list { list-style: none; margin: 8px 0 0; padding: 0; max-height: 36vh; overflow-y: auto; }

    .sa-scope-list li { display: flex; align-items: center; gap: 4px; border-bottom: 1px solid #f1f5f9; }

    .sa-scope-go {
        flex: 1;
        min-width: 0;
        border: none;
        background: none;
        text-align: left;
        padding: 6px 2px;
        cursor: pointer;
        color: #0f172a;
    }

    .sa-scope-go:hover .sa-scope-label { color: #2563eb; text-decoration: underline; }
    .sa-scope-label { display: block; font-size: .85rem; font-weight: 600; }
    .sa-scope-detail { display: block; font-size: .72rem; color: #64748b; }

    .sa-scope-remove {
        border: none;
        background: none;
        color: #94a3b8;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        cursor: pointer;
    }

    .sa-scope-remove:hover { background: #fee2e2; color: #dc2626; }

    .sa-scope-empty { color: #94a3b8; font-size: .85rem; padding: 8px 0; }

    /* ---------- Khung chính ---------- */
    .sa-main { position: relative; min-height: 420px; }

    .sa-head { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }

    .sa-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 14px; }
    .sa-crumb { border: none; background: none; padding: 0; color: #2563eb; cursor: pointer; }
    .sa-crumb:hover { text-decoration: underline; }
    .sa-crumb.is-current { color: #0f172a; font-weight: 700; cursor: default; text-decoration: none; }
    .sa-crumb-sep { color: #cbd5e1; }

    .sa-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

    .sa-hintbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 20px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .sa-hint { margin: 0; color: #475569; font-size: .88rem; }
    .sa-hint b { color: #0f172a; }

    .sa-legend { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: #475569; }

    .sa-swatch {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 3px;
        vertical-align: -1px;
        margin-right: 3px;
        background: #eef1f4;
        border: 1px solid #d6dce2;
    }

    .sa-busy {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, .55);
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #16a34a;
        border-radius: inherit;
        z-index: 5;
    }

    .sa-main.is-busy .sa-busy { display: flex; }

    /* ---------- Nút tick 3 trạng thái: cả / một phần / chưa ---------- */
    .sa-check {
        flex: none;
        width: 26px;
        height: 26px;
        border-radius: 7px;
        border: 2px solid #cbd5e1;
        background: #fff;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        cursor: pointer;
        padding: 0;
        transition: all .15s ease;
    }

    .sa-check:hover { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, .15); }
    .sa-check.is-on { background: #16a34a; border-color: #16a34a; }
    .sa-check.is-part { background: #bbf7d0; border-color: #16a34a; color: #15803d; }

    .sa-whole {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 14px 4px 4px;
        background: #fff;
        font-size: .85rem;
        font-weight: 600;
        color: #0f172a;
        cursor: pointer;
    }

    .sa-whole:hover { border-color: #16a34a; }
    .sa-whole .sa-check { pointer-events: none; }

    /* ---------- Thẻ Kho / Kệ ---------- */
    .sa-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
    .sa-cards.is-shelves { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }

    .sa-tile {
        position: relative;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        cursor: pointer;
        user-select: none;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .sa-tile:hover {
        border-color: #3b82f6;
        box-shadow: 0 10px 22px -8px rgba(15, 23, 42, .18);
        transform: translateY(-2px);
    }

    .sa-tile.is-on { border-color: #86efac; background: #f0fdf4; }
    .sa-tile.is-part { border-color: #bbf7d0; }

    /* Thẻ đang nằm trong vùng kéo chọn */
    .sa-tile.is-pick { outline: 2px solid #16a34a; outline-offset: 1px; background: #dcfce7; }
    .sa-tile.is-pick.is-on { outline-color: #dc2626; background: #fee2e2; }

    .sa-tile-top { display: flex; align-items: center; gap: 10px; }

    .sa-tile-code {
        font-weight: 800;
        font-size: 1.15rem;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .sa-tile-pct { font-size: .75rem; font-weight: 700; color: #64748b; }
    .sa-tile.is-on .sa-tile-pct,
    .sa-tile.is-part .sa-tile-pct { color: #15803d; }

    .sa-tile-name {
        font-size: .85rem;
        color: #475569;
        margin: 4px 0 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sa-bar { height: 6px; border-radius: 999px; background: #eef2f6; overflow: hidden; }
    .sa-bar > div { height: 100%; background: #16a34a; border-radius: inherit; }

    .sa-tile-foot {
        display: flex;
        justify-content: space-between;
        gap: 6px;
        margin-top: 8px;
        font-size: .75rem;
        color: #64748b;
    }

    .sa-tile-foot b { color: #0f172a; }

    .sa-others {
        margin-top: 8px;
        font-size: .72rem;
        color: #b45309;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sa-others i { margin-right: 3px; }

    .sa-none { color: #94a3b8; padding: 30px 0; text-align: center; }

    /* ---------- Sơ đồ kệ: hàng là tầng, cột là vị trí ---------- */
    .sa-grid-wrap { overflow-x: auto; padding-bottom: 8px; }
    .sa-grid { --cell: 18px; display: inline-block; min-width: 100%; touch-action: none; user-select: none; }

    .sa-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }

    .sa-row-label {
        position: sticky;
        left: 0;
        z-index: 2;
        flex: none;
        width: 150px;
        background: #fff;
    }

    .sa-tier {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        padding: 3px 8px 3px 4px;
        cursor: pointer;
        text-align: left;
        font-size: .8rem;
        font-weight: 600;
        color: #0f172a;
    }

    .sa-tier:hover { border-color: #16a34a; }
    .sa-tier .sa-check { width: 20px; height: 20px; border-radius: 5px; font-size: .6rem; pointer-events: none; }
    .sa-tier small { margin-left: auto; font-weight: 500; color: #64748b; }

    .sa-cells { display: grid; grid-template-columns: repeat(var(--cols), var(--cell)); gap: 2px; flex: none; }

    .sa-row-head .sa-cells span { font-size: 10px; color: #94a3b8; text-align: center; overflow: visible; white-space: nowrap; }

    .sa-cell {
        height: var(--cell);
        border-radius: 3px;
        background: #eef1f4;
        border: 1px solid #d6dce2;
        cursor: pointer;
        position: relative;
    }

    .sa-cell:hover { border-color: #16a34a; }
    .sa-cell.is-none { background: transparent; border-color: transparent; cursor: default; }

    .sa-cell.is-other,
    .sa-swatch.is-other { background: #fde68a; border-color: #f59e0b; }

    .sa-cell.is-on,
    .sa-swatch.is-on { background: #22c55e; border-color: #16a34a; }

    /* Cả mình và người khác cùng phụ trách: nền xanh, góc vàng */
    .sa-cell.is-on.is-other::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        border-style: solid;
        border-width: 0 7px 7px 0;
        border-color: transparent #f59e0b transparent transparent;
    }

    .sa-cell.is-off,
    .sa-swatch.is-off {
        background: repeating-linear-gradient(45deg, #cfd4d9, #cfd4d9 3px, #b9bfc5 3px, #b9bfc5 6px);
        border-color: #aeb4ba;
        cursor: not-allowed;
    }

    .sa-cell.is-pick { outline: 2px solid #16a34a; outline-offset: 0; background: #86efac; }
    .sa-grid.is-removing .sa-cell.is-pick { outline-color: #dc2626; background: #fecaca; }

    .sa-pickinfo {
        font-size: .82rem;
        font-weight: 600;
        color: #15803d;
        background: #dcfce7;
        border-radius: 999px;
        padding: 3px 12px;
    }

    .sa-pickinfo.is-removing { color: #b91c1c; background: #fee2e2; }
</style>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if ($managers->isNotEmpty())
    <script type="application/json" id="sa-config">
        {!! json_encode([
            'managers' => $managers->map(fn ($m) => [
                'id' => (int) $m->id,
                'name' => $m->fullName,
                'userName' => $m->userName,
                'department' => $m->deparment,
            ])->values(),
            'urls' => [
                'summary' => route('pages.storageLocation.assignment.summary'),
                'warehouses' => route('pages.storageLocation.assignment.warehouses'),
                'shelves' => route('pages.storageLocation.assignment.shelves'),
                'grid' => route('pages.storageLocation.assignment.grid'),
                'toggle' => route('pages.storageLocation.assignment.toggle'),
            ],
            'csrf' => csrf_token(),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>

    <script>
        (function () {
            const cfg = JSON.parse(document.getElementById('sa-config').textContent);
            const $ = (id) => document.getElementById(id);
            const main = $('sa-main');
            const view = $('sa-view');
            const LEVELS = ['warehouse', 'shelf', 'tier', 'location'];

            const state = {
                userId: null,
                view: 'warehouses', // warehouses | shelves | grid
                warehouseId: null,
                shelfId: null,
                data: null,
                siblings: [], // danh sách kệ cùng kho để chuyển kệ trước/sau
                covered: {},
                busy: false,
            };
            let direct = {};

            const fmt = (n) => Number(n || 0).toLocaleString('vi-VN');
            const esc = (value) => String(value == null ? '' : value).replace(/[&<>"']/g,
                (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
            const pct = (part, total) => total ? Math.round(part / total * 100) : 0;

            const toast = Swal.mixin({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false });

            async function api(url, params, post) {
                const options = { headers: { Accept: 'application/json' } };
                if (post) {
                    options.method = 'POST';
                    options.headers['Content-Type'] = 'application/json';
                    options.headers['X-CSRF-TOKEN'] = cfg.csrf;
                    options.body = JSON.stringify(params);
                } else {
                    url += '?' + new URLSearchParams(params);
                }

                const response = await fetch(url, options);
                // Hết phiên đăng nhập thì server chuyển hướng sang trang HTML.
                if (response.redirected || response.status === 401 || response.status === 419) {
                    window.location.reload();
                    throw new Error('Phiên làm việc đã hết hạn.');
                }

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Lỗi máy chủ (' + response.status + ').');
                }
                return data;
            }

            function setBusy(value) {
                state.busy = value;
                main.classList.toggle('is-busy', value);
            }

            function fail(error) {
                Swal.fire({ icon: 'error', title: 'Không thực hiện được', text: error.message });
            }

            // ---------- Trạng thái giao, suy ra từ phân công trực tiếp ----------
            function setDirect(map) {
                direct = {};
                LEVELS.forEach((level) => { direct[level] = new Set(map[level] || []); });
            }

            const warehouseWhole = (id) => direct.warehouse.has(id);
            const shelfWhole = (id) => direct.shelf.has(id) || warehouseWhole(state.warehouseId);
            const tierWhole = (id) => direct.tier.has(id) || shelfWhole(state.shelfId);

            function checkState(whole, covered) {
                return whole ? 'on' : (covered > 0 ? 'part' : 'off');
            }

            function checkIcon(status) {
                return '<span class="sa-check is-' + status + '">'
                    + (status === 'on' ? '<i class="fas fa-check"></i>' : status === 'part' ? '<i class="fas fa-minus"></i>' : '')
                    + '</span>';
            }

            function othersLine(others) {
                if (!others || !others.length) return '';
                const text = others.map((o) => o.name + ' · ' + fmt(o.covered)).join(', ');
                return '<div class="sa-others" title="Người khác phụ trách: ' + esc(text) + '">'
                    + '<i class="fas fa-user-friends"></i>' + esc(text) + '</div>';
            }

            // ---------- Địa chỉ trên URL để tải lại trang vẫn giữ chỗ đang xem ----------
            function writeHash() {
                const params = new URLSearchParams();
                if (state.userId) params.set('u', state.userId);
                if (state.view !== 'warehouses') params.set('w', state.warehouseId);
                if (state.view === 'grid') params.set('s', state.shelfId);
                history.replaceState(null, '', '#' + params);
            }

            function readHash() {
                const params = new URLSearchParams(location.hash.slice(1));
                const userId = Number(params.get('u'));
                state.userId = cfg.managers.some((m) => m.id === userId) ? userId : cfg.managers[0].id;
                state.warehouseId = Number(params.get('w')) || null;
                state.shelfId = Number(params.get('s')) || null;
                state.view = state.shelfId ? 'grid' : (state.warehouseId ? 'shelves' : 'warehouses');
            }

            // ---------- Cột trái ----------
            function renderPeople() {
                const filter = ($('sa-people-filter').value || '').toLowerCase().trim();

                $('sa-people-list').innerHTML = cfg.managers
                    .filter((m) => !filter || (m.name + ' ' + m.userName).toLowerCase().includes(filter))
                    .map((m) => {
                        const initials = m.name.split(/\s+/).filter(Boolean).slice(-2).map((w) => w[0]).join('').toUpperCase();
                        const covered = state.covered[m.id] || 0;
                        return '<button type="button" class="sa-person' + (m.id === state.userId ? ' is-active' : '') + '" data-user="' + m.id + '">'
                            + '<span class="sa-avatar">' + esc(initials) + '</span>'
                            + '<span class="sa-person-text">'
                            + '<span class="sa-person-name d-block">' + esc(m.name) + '</span>'
                            + '<span class="sa-person-meta">' + esc(m.userName) + (m.department ? ' · ' + esc(m.department) : '')
                            + ' · ' + (covered ? '<b>' + fmt(covered) + '</b> vị trí' : 'chưa giao') + '</span>'
                            + '</span></button>';
                    }).join('') || '<div class="sa-scope-empty px-2">Không tìm thấy.</div>';
            }

            function renderScope(groups) {
                $('sa-scope-count').textContent = groups.length;
                $('sa-scope-list').innerHTML = groups.length ? groups.map((g) =>
                    '<li>'
                    + '<button type="button" class="sa-scope-go" data-go-warehouse="' + g.warehouse_id + '" data-go-shelf="' + (g.shelf_id || '') + '">'
                    + '<span class="sa-scope-label">' + esc(g.label) + '</span>'
                    + '<span class="sa-scope-detail">' + esc(g.detail) + '</span></button>'
                    + '<button type="button" class="sa-scope-remove" title="Bỏ giao phạm vi này"'
                    + ' data-remove-type="' + (g.shelf_id ? 'shelf' : 'warehouse') + '" data-remove-id="' + (g.shelf_id || g.warehouse_id) + '"'
                    + ' data-remove-label="' + esc(g.label) + '"><i class="fas fa-times"></i></button>'
                    + '</li>'
                ).join('') : '<li class="sa-scope-empty">Chưa giao kho, kệ hay vị trí nào.</li>';
            }

            async function loadSummary() {
                const data = await api(cfg.urls.summary, { user_id: state.userId });
                state.covered = data.covered || {};
                renderPeople();
                renderScope(data.groups || []);
            }

            // ---------- Khung chính ----------
            function currentPerson() {
                return cfg.managers.find((m) => m.id === state.userId);
            }

            function renderCrumbs() {
                const parts = [['warehouses', 'Tất cả kho']];
                const data = state.data || {};
                if (state.view !== 'warehouses' && data.warehouse) parts.push(['shelves', 'Kho ' + data.warehouse.code]);
                if (state.view === 'grid' && data.shelf) parts.push(['grid', 'Kệ ' + data.shelf.code]);

                $('sa-crumbs').innerHTML = parts.map(([target, label], index) => {
                    const current = index === parts.length - 1;
                    return (index ? '<span class="sa-crumb-sep">›</span>' : '')
                        + '<button type="button" class="sa-crumb' + (current ? ' is-current' : '') + '" data-crumb="' + target + '">'
                        + esc(label) + '</button>';
                }).join('');
            }

            function renderHint() {
                const person = '<b>' + esc(currentPerson().name) + '</b>';
                const hints = {
                    warehouses: 'Đang phân công cho ' + person + '. Bấm ô tick để giao cả kho, '
                        + '<b>kéo chuột qua nhiều thẻ</b> để giao hàng loạt, bấm vào thẻ để giao từng kệ.',
                    shelves: 'Đang phân công cho ' + person + '. Bấm ô tick để giao cả kệ, '
                        + '<b>kéo chuột qua nhiều thẻ</b> (hoặc Shift + bấm) để giao một dãy kệ, bấm vào thẻ để mở sơ đồ tầng.',
                    grid: 'Đang phân công cho ' + person + '. Bấm tên tầng để giao cả tầng. Bấm hoặc <b>kéo chuột quét vùng</b> để giao; '
                        + 'bắt đầu kéo từ ô đã giao thì sẽ bỏ giao cả vùng.',
                };
                $('sa-hint').innerHTML = hints[state.view];
                document.querySelectorAll('[data-grid-only]').forEach((el) => { el.hidden = state.view !== 'grid'; });
            }

            function renderWarehouses() {
                $('sa-actions').innerHTML = '';
                const list = state.data.warehouses;
                if (!list.length) {
                    view.innerHTML = '<div class="sa-none">Bộ phận này chưa có kho nào đang sử dụng.</div>';
                    return;
                }

                view.innerHTML = '<div class="sa-cards">' + list.map((w) => {
                    const status = checkState(warehouseWhole(w.id), w.covered);
                    return '<div class="sa-tile is-' + status + '" data-open-warehouse="' + w.id + '">'
                        + '<div class="sa-tile-top">'
                        + '<button type="button" class="sa-check is-' + status + '" title="' + (status === 'on' ? 'Bỏ giao cả kho' : 'Giao cả kho') + '"'
                        + ' data-toggle-type="warehouse" data-toggle-id="' + w.id + '" data-on="' + (status === 'on' ? 1 : 0) + '">'
                        + (status === 'on' ? '<i class="fas fa-check"></i>' : status === 'part' ? '<i class="fas fa-minus"></i>' : '') + '</button>'
                        + '<span class="sa-tile-code">' + esc(w.code) + '</span>'
                        + '<span class="sa-tile-pct">' + (status === 'on' ? 'Cả kho' : pct(w.covered, w.total) + '%') + '</span>'
                        + '</div>'
                        + '<div class="sa-tile-name" title="' + esc(w.name) + '">' + esc(w.name) + '</div>'
                        + '<div class="sa-bar"><div style="width:' + pct(w.covered, w.total) + '%"></div></div>'
                        + '<div class="sa-tile-foot"><span><b>' + fmt(w.covered) + '</b>/' + fmt(w.total) + ' vị trí</span>'
                        + '<span>' + fmt(w.shelves) + ' kệ ›</span></div>'
                        + othersLine(w.others)
                        + '</div>';
                }).join('') + '</div>';
            }

            function wholeButton(type, id, on, covered, label) {
                const status = checkState(on, covered);
                return '<button type="button" class="sa-whole" data-toggle-type="' + type + '" data-toggle-id="' + id + '" data-on="' + (on ? 1 : 0) + '">'
                    + checkIcon(status) + (on ? 'Đã giao ' : 'Giao ') + esc(label) + '</button>';
            }

            function renderShelves() {
                const { warehouse, shelves } = state.data;
                const covered = shelves.reduce((sum, s) => sum + s.covered, 0);
                $('sa-actions').innerHTML = wholeButton('warehouse', warehouse.id, warehouseWhole(warehouse.id), covered, 'cả kho ' + warehouse.code);

                if (!shelves.length) {
                    view.innerHTML = '<div class="sa-none">Kho này chưa có kệ nào.</div>';
                    return;
                }

                view.innerHTML = '<div class="sa-cards is-shelves">' + shelves.map((s) => {
                    const status = checkState(shelfWhole(s.id), s.covered);
                    return '<div class="sa-tile is-' + status + '" data-open-shelf="' + s.id + '">'
                        + '<div class="sa-tile-top">'
                        + '<button type="button" class="sa-check is-' + status + '" title="' + (status === 'on' ? 'Bỏ giao cả kệ' : 'Giao cả kệ') + '"'
                        + ' data-toggle-type="shelf" data-toggle-id="' + s.id + '" data-on="' + (status === 'on' ? 1 : 0) + '">'
                        + (status === 'on' ? '<i class="fas fa-check"></i>' : status === 'part' ? '<i class="fas fa-minus"></i>' : '') + '</button>'
                        + '<span class="sa-tile-code">' + esc(s.code) + '</span>'
                        + '<span class="sa-tile-pct">' + (status === 'on' ? 'Cả kệ' : pct(s.covered, s.total) + '%') + '</span>'
                        + '</div>'
                        + '<div class="sa-bar mt-2"><div style="width:' + pct(s.covered, s.total) + '%"></div></div>'
                        + '<div class="sa-tile-foot"><span><b>' + fmt(s.covered) + '</b>/' + fmt(s.total) + ' vị trí</span>'
                        + '<span>' + s.tiers + ' tầng ›</span></div>'
                        + othersLine(s.others)
                        + '</div>';
                }).join('') + '</div>';
            }

            function renderGrid() {
                const { shelf, tiers, others } = state.data;
                const index = state.siblings.findIndex((s) => s.id === shelf.id);
                const prev = state.siblings[index - 1];
                const next = state.siblings[index + 1];

                let shelfCovered = 0;
                tiers.forEach((tier) => {
                    const whole = tierWhole(tier.id);
                    tier.on = 0;
                    tier.active = 0;
                    tier.cells.forEach((cell) => {
                        if (!cell[3]) return;
                        tier.active++;
                        if (whole || direct.location.has(cell[0])) tier.on++;
                    });
                    shelfCovered += tier.on;
                });

                $('sa-actions').innerHTML = '<span id="sa-pickinfo" class="sa-pickinfo" hidden></span>'
                    + wholeButton('shelf', shelf.id, shelfWhole(shelf.id), shelfCovered, 'cả kệ ' + shelf.code)
                    + '<div class="btn-group btn-group-sm">'
                    + '<button type="button" class="btn btn-outline-secondary" data-open-shelf="' + (prev ? prev.id : '') + '"' + (prev ? '' : ' disabled') + ' title="Kệ trước">'
                    + '<i class="fas fa-chevron-left"></i> ' + (prev ? esc(prev.code) : '') + '</button>'
                    + '<button type="button" class="btn btn-outline-secondary" data-open-shelf="' + (next ? next.id : '') + '"' + (next ? '' : ' disabled') + ' title="Kệ sau">'
                    + (next ? esc(next.code) : '') + ' <i class="fas fa-chevron-right"></i></button>'
                    + '</div>';

                if (!tiers.length) {
                    view.innerHTML = '<div class="sa-none">Kệ này chưa có tầng nào đang sử dụng.</div>';
                    return;
                }

                const cols = Math.max(1, ...tiers.flatMap((t) => t.cells.map((c) => c[1])));
                const head = '<div class="sa-row sa-row-head"><div class="sa-row-label"></div><div class="sa-cells" style="--cols:' + cols + '">'
                    + Array.from({ length: cols }, (_, i) => '<span>' + ((i + 1) === 1 || (i + 1) % 5 === 0 ? i + 1 : '') + '</span>').join('')
                    + '</div></div>';

                const rows = tiers.map((tier, t) => {
                    const whole = tierWhole(tier.id);
                    const byPos = {};
                    tier.cells.forEach((cell) => { byPos[cell[1]] = cell; });

                    let cells = '';
                    for (let p = 1; p <= cols; p++) {
                        const cell = byPos[p];
                        if (!cell) {
                            cells += '<span class="sa-cell is-none" data-t="' + t + '" data-p="' + p + '"></span>';
                            continue;
                        }

                        const [id, , code, active] = cell;
                        const names = others[id] || [];
                        let cls = 'sa-cell';
                        if (!active) cls += ' is-off';
                        else {
                            if (whole || direct.location.has(id)) cls += ' is-on';
                            if (names.length) cls += ' is-other';
                        }
                        const title = code + (active ? '' : ' (đang khoá)') + (names.length ? '\nNgười khác: ' + names.join(', ') : '');
                        cells += '<span class="' + cls + '" data-t="' + t + '" data-p="' + p + '" data-id="' + id + '" title="' + esc(title) + '"></span>';
                    }

                    const status = checkState(whole, tier.on);
                    return '<div class="sa-row"><div class="sa-row-label">'
                        + '<button type="button" class="sa-tier" data-toggle-type="tier" data-toggle-id="' + tier.id + '" data-on="' + (whole ? 1 : 0) + '"'
                        + ' title="' + (whole ? 'Bỏ giao cả tầng' : 'Giao cả tầng') + ' ' + esc(tier.code) + '">'
                        + checkIcon(status) + esc(tier.name || tier.code) + '<small>' + tier.on + '/' + tier.active + '</small></button>'
                        + '</div><div class="sa-cells" style="--cols:' + cols + '">' + cells + '</div></div>';
                }).join('');

                view.innerHTML = '<div class="sa-grid-wrap"><div class="sa-grid" id="sa-grid">' + head + rows + '</div></div>';

                // Tầng ngắn thì ô to cho dễ bấm, tầng dài (tới 150 ô) thì co lại để đỡ phải cuộn ngang.
                const room = view.clientWidth - 158;
                const size = Math.max(12, Math.min(24, Math.floor(room / cols) - 2));
                $('sa-grid').style.setProperty('--cell', size + 'px');
            }

            function render() {
                renderCrumbs();
                renderHint();
                if (state.view === 'warehouses') renderWarehouses();
                if (state.view === 'shelves') renderShelves();
                if (state.view === 'grid') renderGrid();
            }

            async function loadView() {
                let data;
                if (state.view === 'warehouses') {
                    data = await api(cfg.urls.warehouses, { user_id: state.userId });
                } else if (state.view === 'shelves') {
                    data = await api(cfg.urls.shelves, { user_id: state.userId, warehouse_id: state.warehouseId });
                    state.siblings = data.shelves.map((s) => ({ id: s.id, code: s.code }));
                } else {
                    data = await api(cfg.urls.grid, { user_id: state.userId, shelf_id: state.shelfId });
                    state.warehouseId = data.shelf.warehouse_id;
                    // Vào thẳng sơ đồ (từ danh sách phạm vi hay tải lại trang) thì chưa có danh sách kệ cùng kho.
                    if (!state.siblings.some((s) => s.id === data.shelf.id)) {
                        const list = await api(cfg.urls.shelves, { user_id: state.userId, warehouse_id: state.warehouseId });
                        state.siblings = list.shelves.map((s) => ({ id: s.id, code: s.code }));
                    }
                }

                state.data = data;
                setDirect(data.direct);
                writeHash();
                render();
            }

            async function go(changes) {
                if (state.busy) return;
                Object.assign(state, changes);
                setBusy(true);
                try {
                    await Promise.all([loadView(), changes.userId ? loadSummary() : null]);
                } catch (error) {
                    fail(error);
                    // Kho/kệ trên URL không còn thì quay về danh sách kho.
                    if (state.view !== 'warehouses') {
                        Object.assign(state, { view: 'warehouses', warehouseId: null, shelfId: null });
                        await loadView().catch(fail);
                    }
                } finally {
                    setBusy(false);
                }
            }

            async function toggle(action, type, ids) {
                if (state.busy || !ids.length) return;
                setBusy(true);
                try {
                    const result = await api(cfg.urls.toggle, { user_id: state.userId, action, type, ids }, true);
                    await Promise.all([loadView(), loadSummary()]);
                    toast.fire({ icon: result.changed ? 'success' : 'info', title: result.message });
                } catch (error) {
                    fail(error);
                } finally {
                    setBusy(false);
                }
            }

            // ---------- Kéo quét vùng trên sơ đồ kệ ----------
            let drag = null;

            function pickedCells() {
                if (!drag) return [];
                const [t0, t1] = [Math.min(drag.t0, drag.t1), Math.max(drag.t0, drag.t1)];
                const [p0, p1] = [Math.min(drag.p0, drag.p1), Math.max(drag.p0, drag.p1)];
                return drag.cells.filter((cell) => {
                    const t = +cell.dataset.t;
                    const p = +cell.dataset.p;
                    return t >= t0 && t <= t1 && p >= p0 && p <= p1
                        && !cell.classList.contains('is-off')
                        && cell.classList.contains('is-on') === drag.removing;
                });
            }

            function paintPick() {
                const picked = new Set(pickedCells());
                drag.cells.forEach((cell) => cell.classList.toggle('is-pick', picked.has(cell)));

                const info = $('sa-pickinfo');
                if (info) {
                    info.hidden = false;
                    info.classList.toggle('is-removing', drag.removing);
                    info.textContent = (drag.removing ? 'Bỏ giao ' : 'Giao ') + picked.size + ' vị trí';
                }
            }

            view.addEventListener('pointerdown', (event) => {
                const cell = event.target.closest('.sa-cell[data-id]');
                if (!cell || state.busy || event.button !== 0 || cell.classList.contains('is-off')) return;

                event.preventDefault();
                const grid = $('sa-grid');
                drag = {
                    t0: +cell.dataset.t, p0: +cell.dataset.p,
                    t1: +cell.dataset.t, p1: +cell.dataset.p,
                    removing: cell.classList.contains('is-on'),
                    cells: Array.from(grid.querySelectorAll('.sa-cell[data-id]')),
                };
                grid.classList.toggle('is-removing', drag.removing);
                paintPick();
            });

            document.addEventListener('pointermove', (event) => {
                if (tileDrag) {
                    paintTiles(event);
                    return;
                }
                if (!drag) return;
                const node = document.elementFromPoint(event.clientX, event.clientY);
                const cell = node && node.closest('#sa-grid .sa-cell[data-t]');
                if (!cell || (+cell.dataset.t === drag.t1 && +cell.dataset.p === drag.p1)) return;
                drag.t1 = +cell.dataset.t;
                drag.p1 = +cell.dataset.p;
                paintPick();
            });

            function endDrag(cancel) {
                if (!drag) return;
                const ids = cancel ? [] : pickedCells().map((cell) => +cell.dataset.id);
                const action = drag.removing ? 'unassign' : 'assign';
                drag.cells.forEach((cell) => cell.classList.remove('is-pick'));
                drag = null;
                const info = $('sa-pickinfo');
                if (info) info.hidden = true;
                toggle(action, 'location', ids);
            }

            // ---------- Kéo rê qua nhiều thẻ kho / kệ ----------
            // Cùng ý nghĩa với quét vùng trong sơ đồ: thẻ đầu tiên quyết định là giao hay bỏ giao.
            let tileDrag = null;
            let skipClick = false;

            function tilesInRange() {
                const [from, to] = [Math.min(tileDrag.from, tileDrag.to), Math.max(tileDrag.from, tileDrag.to)];
                return tileDrag.tiles.slice(from, to + 1)
                    .filter((tile) => tile.classList.contains('is-on') === tileDrag.removing);
            }

            function paintTiles(event) {
                const node = document.elementFromPoint(event.clientX, event.clientY);
                const tile = node && node.closest('#sa-view .sa-tile');
                const index = tile ? tileDrag.tiles.indexOf(tile) : -1;
                if (index < 0 || index === tileDrag.to) return;

                tileDrag.to = index;
                tileDrag.moved = true;
                const picked = new Set(tilesInRange());
                tileDrag.tiles.forEach((item) => item.classList.toggle('is-pick', picked.has(item)));
            }

            function endTileDrag(cancel) {
                if (!tileDrag) return;
                const picked = cancel ? [] : tilesInRange();
                const moved = tileDrag.moved;
                const type = tileDrag.type;
                const action = tileDrag.removing ? 'unassign' : 'assign';
                tileDrag.tiles.forEach((tile) => tile.classList.remove('is-pick'));
                tileDrag = null;

                // Bấm không kéo thì giữ nguyên hành vi cũ: mở kho/kệ đó ra.
                if (!moved || cancel) return;
                skipClick = true;
                toggle(action, type, picked.map((tile) => +tile.querySelector('[data-toggle-id]').dataset.toggleId));
            }

            view.addEventListener('pointerdown', (event) => {
                if (state.view === 'grid' || state.busy || event.button !== 0) return;
                const tile = event.target.closest('.sa-tile');
                if (!tile || event.target.closest('[data-toggle-type]')) return;

                event.preventDefault();
                const tiles = Array.from(view.querySelectorAll('.sa-tile'));
                tileDrag = {
                    tiles,
                    type: state.view === 'warehouses' ? 'warehouse' : 'shelf',
                    from: tiles.indexOf(tile),
                    to: tiles.indexOf(tile),
                    removing: tile.classList.contains('is-on'),
                    moved: false,
                };
            });

            document.addEventListener('pointerup', () => { endDrag(false); endTileDrag(false); });
            document.addEventListener('pointercancel', () => { endDrag(true); endTileDrag(true); });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') { endDrag(true); endTileDrag(true); }
            });

            // ---------- Bấm ----------
            let lastCheck = null;

            view.addEventListener('click', (event) => {
                const toggler = event.target.closest('[data-toggle-type]');
                if (toggler) {
                    event.stopPropagation();
                    const action = toggler.dataset.on === '1' ? 'unassign' : 'assign';
                    const type = toggler.dataset.toggleType;
                    let ids = [+toggler.dataset.toggleId];

                    // Shift + bấm: áp cùng thao tác cho cả dãy từ ô tick bấm trước tới ô này.
                    const checks = Array.from(view.querySelectorAll('[data-toggle-type="' + type + '"]'));
                    const index = checks.indexOf(toggler);
                    if (event.shiftKey && lastCheck && lastCheck.type === type && lastCheck.index !== index) {
                        const [from, to] = [Math.min(lastCheck.index, index), Math.max(lastCheck.index, index)];
                        ids = checks.slice(from, to + 1)
                            .filter((el) => (el.dataset.on === '1') === (action === 'unassign'))
                            .map((el) => +el.dataset.toggleId);
                    }
                    lastCheck = { type, index };

                    toggle(action, type, ids);
                    return;
                }

                // Vừa kéo qua nhiều thẻ thì không mở thẻ cuối ra nữa.
                if (skipClick) {
                    skipClick = false;
                    return;
                }

                const warehouse = event.target.closest('[data-open-warehouse]');
                if (warehouse) {
                    go({ view: 'shelves', warehouseId: +warehouse.dataset.openWarehouse, shelfId: null });
                    return;
                }

                const shelf = event.target.closest('[data-open-shelf]');
                if (shelf) go({ view: 'grid', shelfId: +shelf.dataset.openShelf });
            });

            $('sa-actions').addEventListener('click', (event) => {
                const toggler = event.target.closest('[data-toggle-type]');
                if (toggler) {
                    toggle(toggler.dataset.on === '1' ? 'unassign' : 'assign', toggler.dataset.toggleType, [+toggler.dataset.toggleId]);
                    return;
                }

                const shelf = event.target.closest('[data-open-shelf]');
                if (shelf && shelf.dataset.openShelf) go({ view: 'grid', shelfId: +shelf.dataset.openShelf });
            });

            $('sa-crumbs').addEventListener('click', (event) => {
                const crumb = event.target.closest('[data-crumb]:not(.is-current)');
                if (!crumb) return;
                if (crumb.dataset.crumb === 'warehouses') go({ view: 'warehouses', warehouseId: null, shelfId: null });
                if (crumb.dataset.crumb === 'shelves') go({ view: 'shelves', shelfId: null });
            });

            $('sa-people-list').addEventListener('click', (event) => {
                const person = event.target.closest('[data-user]');
                if (person && +person.dataset.user !== state.userId) go({ userId: +person.dataset.user });
            });

            $('sa-people-filter').addEventListener('input', renderPeople);

            $('sa-scope-list').addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-type]');
                if (remove) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Bỏ giao phạm vi?',
                        text: currentPerson().name + ' sẽ không còn phụ trách ' + remove.dataset.removeLabel + '.',
                        showCancelButton: true,
                        confirmButtonText: 'Bỏ giao',
                        cancelButtonText: 'Huỷ',
                        confirmButtonColor: '#dc3545',
                    }).then((result) => {
                        if (result.isConfirmed || result.value) {
                            toggle('unassign', remove.dataset.removeType, [+remove.dataset.removeId]);
                        }
                    });
                    return;
                }

                const target = event.target.closest('[data-go-warehouse]');
                if (!target) return;
                if (target.dataset.goShelf) {
                    go({ view: 'grid', warehouseId: +target.dataset.goWarehouse, shelfId: +target.dataset.goShelf });
                } else {
                    go({ view: 'shelves', warehouseId: +target.dataset.goWarehouse, shelfId: null });
                }
            });

            readHash();
            go({ userId: state.userId });
        })();
    </script>
@endif
