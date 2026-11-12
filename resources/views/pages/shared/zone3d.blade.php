{{--
|--------------------------------------------------------------------------
| SƠ ĐỒ HỒ SƠ THEO VỊ TRÍ - 3D TỪNG KỆ / MẶT BẰNG CẢ KHO (port từ inventory/shared/zone3d của WMS)
|--------------------------------------------------------------------------
| Thêm nút "Thẻ | 3D | Mặt bằng" vào .mz-tools của khối kết quả. Chế độ 3D: chọn kệ ở danh sách bên trái, kệ dựng
| 3D bên phải (tầng 1 dưới cùng, mỗi ô vị trí là một ô ngang). Vị trí đang có hồ sơ là thùng nổi lên, tô màu theo
| hạn lưu trữ; vị trí trống là ô phẳng. Mặt bằng: cả kho theo mặt bằng đã khai ở Cấu Trúc Kho > Mặt bằng kho.
| Từ khoá đã tra ở máy chủ (data-z3d-keyword trên rootEl) làm các vị trí khớp (node.match) sáng lên + nhấp nháy,
| ô lọc nhanh .mz-search input lọc tiếp trên node.search.
|
|   window.damsZone3d.init(rootEl, { zones, nodes, open(key), modeKey, defaultMode })
|
| zones: { warehouses: [{id, code, name, layout_room}], shelves: [{id, code, name, warehouse_id, layout_*}],
|          tiers: [{id, shelf_id, name, code, position, max}] }   (max = số ô xa nhất của tầng)
| nodes: { 'L<id vị trí>': { t: tier_id, p: ô, s: ok|warn|danger, [match, code, path, search, items] } } - chỉ vị trí
|        có hồ sơ; vị trí khớp tra cứu mới có items (bấm thùng để mở chi tiết qua opts.open).
--}}
@include('pages.shared.shelf3d')

@once
<style>
    .mz-view {
        display: inline-flex;
        border: 1px solid #dbe5f0;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        background: #fff;
    }

    .mz-view button {
        border: none;
        background: none;
        padding: 4px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .mz-view button + button {
        border-left: 1px solid #dbe5f0;
    }

    .mz-view button:hover {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .mz-view button.is-active {
        background: var(--primary);
        color: #fff;
    }

    /* cột danh sách | tay kéo đổi bề ngang | khung 3D; bề ngang danh sách do người dùng kéo (--mz3d-side) */
    .mz-3d {
        display: none;
        grid-template-columns: var(--mz3d-side, 260px) 12px minmax(0, 1fr);
        column-gap: 2px;
        row-gap: 14px;
        margin-top: 4px;
    }

    .mz-3d.is-side-off .mz-3d-list {
        visibility: hidden;
        padding: 0;
        border: 0;
        overflow: hidden;
    }

    /* tay kéo: dọc = đổi bề ngang danh sách, ngang (dưới khung 3D) = đổi chiều cao khung */
    .mz-3d-split,
    .mz-3d-grip {
        position: relative;
        touch-action: none;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .mz-3d-split {
        cursor: ew-resize;
    }

    .mz-3d-grip {
        height: 12px;
        margin-top: -6px;
        cursor: ns-resize;
    }

    .mz-3d-split::before,
    .mz-3d-grip::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        border-radius: 999px;
        background: #CBD5E1;
        transform: translate(-50%, -50%);
        transition: background 0.2s;
    }

    .mz-3d-split::before {
        width: 4px;
        height: 56px;
        top: min(50%, 220px);
    }

    .mz-3d-grip::before {
        width: 56px;
        height: 4px;
    }

    .mz-3d-split:hover,
    .mz-3d-grip:hover,
    .mz-3d-split.is-active,
    .mz-3d-grip.is-active {
        background: var(--primary-soft);
    }

    .mz-3d-split:hover::before,
    .mz-3d-grip:hover::before,
    .mz-3d-split.is-active::before,
    .mz-3d-grip.is-active::before {
        background: var(--primary);
    }

    body.mz-resizing,
    body.mz-resizing * {
        user-select: none !important;
    }

    .mz-mode-3d .mz-3d {
        display: grid;
    }

    .mz-mode-3d .mz-body,
    .mz-mode-3d > .mz-wh,
    .mz-mode-3d > .mz-unzoned,
    .mz-mode-3d .mz-noresult,
    .mz-mode-3d .mz-toggle-all,
    .mz-mode-3d #mzToggleAll {
        display: none !important;
    }

    /* chiều cao do JS tính (fitHeight): khung 3D kéo tới gần đáy cửa sổ, danh sách bên trái cao bằng */
    .mz-3d-list {
        max-height: var(--mz3d-list, 560px);
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: var(--border-radius-lg);
        background: #fff;
        padding: 8px;
    }

    .mz-3d-wh {
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 6px 4px;
    }

    .mz-3d-wh i {
        color: var(--primary-light);
        margin-right: 4px;
    }

    .mz-3d-shelf {
        display: block;
        width: 100%;
        text-align: left;
        border: 1px solid transparent;
        border-radius: var(--border-radius-md);
        background: none;
        padding: 7px 10px;
        margin-bottom: 2px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, opacity 0.2s;
    }

    .mz-3d-shelf:hover {
        background: var(--primary-soft);
    }

    .mz-3d-shelf.is-active {
        background: var(--primary-soft);
        border-color: var(--primary);
    }

    .mz-3d-shelf.is-miss {
        opacity: 0.45;
    }

    .mz-3d-shelf .nm {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .mz-3d-shelf .nm .hit {
        margin-left: auto;
        background: var(--accent);
        color: #fff;
        border-radius: 999px;
        padding: 0 7px;
        font-size: 0.7rem;
    }

    .mz-3d-shelf .sub {
        display: block;
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 1px;
    }

    .mz-3d-shelf .sub .alert {
        color: #B45309;
        font-weight: 700;
    }

    .mz-3d-bar {
        height: 4px;
        border-radius: 999px;
        background: #EEF2F6;
        margin-top: 5px;
        overflow: hidden;
    }

    .mz-3d-bar span {
        display: block;
        height: 100%;
        background: #16A34A;
    }

    .mz-3d-main {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .mz-3d-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
        font-weight: 700;
        color: var(--primary);
    }

    .mz-3d-title .tag {
        font-size: 0.74rem;
        font-weight: 600;
        color: #475569;
        background: var(--primary-soft);
        border-radius: 999px;
        padding: 2px 10px;
    }

    .mz-3d-title .tag.is-alert {
        background: #FEF3C7;
        color: #92400E;
    }

    .mz-3d-title .mz-3d-unzoned {
        border: 1px solid #FCD34D;
        cursor: pointer;
        transition: all 0.2s;
    }

    .mz-3d-title .mz-3d-unzoned:hover {
        background: #FDE68A;
        transform: translateY(-1px);
    }

    .mz-3d-title .mz-3d-unzoned i {
        font-size: 0.68rem;
        margin-left: 3px;
    }

    /* nút Nhãn vị trí chỉ có ở chế độ Mặt bằng */
    .mz-codes-group {
        display: none !important;
    }

    .mz-mode-room .mz-codes-group {
        display: inline-flex !important;
    }

    .mz-3d-main .w3d {
        height: var(--mz3d-h, 560px);
        min-height: 320px;
    }

    .mz-plan-svg {
        position: absolute;
        inset: 0;
        overflow: visible;
        pointer-events: none;
    }

    .mz-plan.is-poly {
        background: none !important;
        border: none !important;
    }

    .mz-3d-none {
        padding: 30px 12px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.84rem;
    }

    /* ---------- chế độ Mặt bằng: danh sách kho/phòng + sơ đồ thu nhỏ ---------- */
    .mz-3d-room {
        display: block;
        width: 100%;
        text-align: left;
        border: 1px solid transparent;
        border-radius: var(--border-radius-md);
        background: none;
        padding: 7px 10px;
        margin-bottom: 2px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, opacity 0.2s;
    }

    .mz-3d-room:hover,
    .mz-3d-room.is-active {
        background: var(--primary-soft);
    }

    .mz-3d-room.is-active {
        border-color: var(--primary);
    }

    .mz-3d-room.is-miss {
        opacity: 0.45;
    }

    .mz-3d-room .nm {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .mz-3d-room .nm .hit {
        margin-left: auto;
        background: var(--accent);
        color: #fff;
        border-radius: 999px;
        padding: 0 7px;
        font-size: 0.7rem;
    }

    .mz-3d-room .sub {
        display: block;
        font-size: 0.72rem;
        color: #64748b;
        margin-top: 1px;
    }

    .mz-plan-box {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }

    .mz-plan-head {
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 6px;
    }

    .mz-plan {
        position: relative;
        margin: 4px auto 6px;
        background-color: #fff;
        background-image:
            linear-gradient(90deg, rgba(var(--primary-rgb), 0.1) 1px, transparent 1px),
            linear-gradient(rgba(var(--primary-rgb), 0.1) 1px, transparent 1px);
        border: 2px solid var(--primary);
        box-sizing: content-box;
    }

    .mz-plan-door {
        position: absolute;
        background: #fff;
        border: 2px dashed var(--accent);
    }

    .mz-plan-shelf {
        position: absolute;
        border: 1px solid var(--primary);
        background: rgba(var(--primary-rgb), 0.1);
        border-bottom-width: 3px;
        cursor: pointer;
        padding: 0;
        transition: background 0.2s, box-shadow 0.2s;
    }

    .mz-plan-shelf:hover {
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.35);
    }

    .mz-plan-shelf.is-stocked {
        background: rgba(22, 163, 74, 0.18);
    }

    .mz-plan-shelf.is-alert {
        background: rgba(245, 158, 11, 0.25);
    }

    .mz-plan-shelf.is-hit {
        background: rgba(23, 184, 212, 0.35);
        border-color: var(--accent);
        box-shadow: 0 0 0 2px rgba(23, 184, 212, 0.35);
    }

    .mz-plan-shelf.is-miss {
        opacity: 0.4;
    }

    /* đang tìm kiếm: kệ có vị trí khớp nhấp nháy trên sơ đồ thu nhỏ */
    @keyframes mz-plan-seek {
        50% {
            background: rgba(23, 184, 212, 0.75);
            box-shadow: 0 0 0 3px #fff, 0 0 10px 4px rgba(23, 184, 212, 0.8);
        }
    }

    .mz-plan.is-seek .mz-plan-shelf.is-hit {
        animation: mz-plan-seek 1s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .mz-plan.is-seek .mz-plan-shelf.is-hit {
            animation: none;
        }
    }

    .mz-plan-shelf span {
        position: absolute;
        left: 50%;
        top: 50%;
        font-size: 9px;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
        pointer-events: none;
    }

    .mz-plan-note {
        font-size: 0.72rem;
        color: #64748b;
    }

    .mz-plan-note.is-warn {
        color: #B45309;
    }

    @media (max-width: 992px) {
        .mz-3d {
            grid-template-columns: minmax(0, 1fr);
        }

        .mz-3d-split {
            display: none;
        }

        .mz-3d.is-side-off .mz-3d-list {
            visibility: visible;
            padding: 8px;
            border: 1px solid #e2e8f0;
            overflow-y: auto;
        }

        .mz-3d-list {
            max-height: 220px;
        }

        .mz-3d-main .w3d {
            min-height: 380px;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        var STATE_COLORS = { ok: '#16A34A', warn: '#F59E0B', danger: '#DC2626' };
        var STATE_LABELS = { ok: 'Đang lưu', warn: 'Sắp hết hạn lưu trữ', danger: 'Quá hạn lưu trữ' };
        var HIT_COLOR = '#17B8D4';
        var MODE_KEY = 'dams.zoneView';
        var CODES_KEY = 'dams.zoneRoomCodes';
        var HEIGHT_KEY = 'dams.zone3dHeight';
        var SIDE_KEY = 'dams.zone3dSide';
        var MIN_HEIGHT = 320;
        // Cả kho vẽ mọi ô (Nhãn vị trí) chỉ khi tổng số ô còn nhẹ cho CSS 3D
        var MAX_ROOM_CELLS = 6000;

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function read(key, fallback) {
            try { return localStorage.getItem(key) || fallback; } catch (err) { return fallback; }
        }

        function write(key, value) {
            try { localStorage.setItem(key, value); } catch (err) { /* bỏ qua */ }
        }

        function byCode(a, b) {
            return String(a.code || a.name || '').localeCompare(String(b.code || b.name || ''), 'vi', { numeric: true });
        }

        function byPosition(a, b) {
            var pa = Number(a.position) || 0, pb = Number(b.position) || 0;
            if (pa && pb && pa !== pb) return pa - pb;
            if (!pa !== !pb) return pa ? -1 : 1;
            return byCode(a, b);
        }

        function asMap(list) {
            var map = {};
            (list || []).forEach(function(x) { if (x && x.id != null) map[x.id] = x; });
            return map;
        }

        function values(map) {
            return Object.keys(map).map(function(k) { return map[k]; });
        }

        /* ---------- dựng cây kệ → tầng → ô (DAMS không có cấp Cột: mỗi kệ là một cột) ---------- */
        function buildShelves(zones, nodes) {
            var warehouses = asMap(zones.warehouses);
            var shelves = asMap(zones.shelves);
            var at = {};
            Object.keys(nodes).forEach(function(key) {
                var n = nodes[key];
                at[n.t + ':' + n.p] = key;
            });

            var out = {};
            (zones.tiers || []).forEach(function(tier) {
                var base = shelves[tier.shelf_id];
                if (!base) return;
                var s = out[tier.shelf_id] = out[tier.shelf_id] || $.extend({ tiers: [] }, base);
                s.tiers.push(tier);
            });

            return values(out).map(function(s) {
                s.warehouseKey = String(s.warehouse_id || 0);
                s.warehouse = warehouses[s.warehouseKey] || { name: 'Chưa gán kho' };
                var rank = 0;
                var tierList = s.tiers.slice().sort(byPosition).map(function(tier) {
                    var t = $.extend({}, tier);
                    rank = Number(t.position) > rank ? Number(t.position) : rank + 1;
                    t.rank = rank;
                    t.max = Math.max(1, Number(t.max) || 1);
                    t.depth = 1;
                    t.cells = [];
                    for (var p = 1; p <= t.max; p++) t.cells.push({ p: p, d: 1, key: at[t.id + ':' + p] || null });
                    return t;
                });
                s.levels = rank;
                s.columnList = [{ name: s.name || s.code || '', code: '', tierList: tierList }];
                return s;
            }).sort(function(a, b) {
                return String(a.warehouse.name).localeCompare(String(b.warehouse.name), 'vi') || byCode(a, b);
            });
        }

        /* ---------- bộ lọc: ô lọc nhanh của trang + từ khoá đã tra ở máy chủ ---------- */
        function filterOf(root) {
            var input = root.querySelector('.mz-search input');
            var f = {
                q: input ? String(input.value || '').trim().toLowerCase() : '',
                // Từ khoá đã lọc ở máy chủ: vị trí khớp mang node.match = true
                k: String(root.getAttribute('data-z3d-keyword') || '')
            };
            f.active = f.q !== '' || f.k !== '';
            return f;
        }

        function matches(f, node) {
            if (!node) return false;
            var okText = f.q === '' || String(node.search || '').indexOf(f.q) >= 0;
            var okKey = f.k === '' || !!node.match;
            return okText && okKey;
        }

        function statOf(ctx, shelf, f) {
            var st = { locations: 0, filled: 0, alerts: 0, hits: 0 };
            shelf.columnList.forEach(function(c) {
                c.tierList.forEach(function(t) {
                    t.cells.forEach(function(cell) {
                        var node = cell.key ? ctx.nodes[cell.key] : null;
                        st.locations++;
                        if (node) {
                            st.filled++;
                            if (node.s === 'warn' || node.s === 'danger') st.alerts++;
                        }
                        if (f.active && matches(f, node)) st.hits++;
                    });
                });
            });
            return st;
        }

        /* ---------- vẽ ---------- */
        function legend() {
            return Object.keys(STATE_LABELS).map(function(state) {
                return '<span><i style="background:' + STATE_COLORS[state] + '"></i>' + STATE_LABELS[state] + '</span>';
            }).join('') + '<span><i style="background:#EEF2F6"></i>Trống</span>';
        }

        function cellOf(ctx, tier, cell, f) {
            var node = cell.key ? ctx.nodes[cell.key] : null;
            var hit = f.active && matches(f, node);
            var title;

            if (!node) {
                title = (tier.name || '') + ' · ô ' + cell.p + ' · Đang trống';
            } else if (node.items) {
                title = (node.code || '') + ' · ' + node.items.map(function(x) { return x.code + (x.name ? ' - ' + x.name : ''); }).join(', ') +
                    (node.s !== 'ok' ? ' · ' + STATE_LABELS[node.s] : '') + ' · bấm để xem chi tiết';
            } else {
                title = (tier.name || '') + ' · ô ' + cell.p + ' · Đang có hồ sơ';
            }

            return {
                p: cell.p, d: cell.d,
                // Chỉ hồ sơ khớp tra cứu mới mở được chi tiết
                key: node && node.items ? cell.key : null,
                title: title,
                flag: !!node && node.s === 'danger',
                label: String(cell.p),
                color: node ? STATE_COLORS[node.s] || STATE_COLORS.ok : null,
                box: !!node, dim: f.active && !hit, hit: hit
            };
        }

        // Mô hình 3D của một kệ. room = đang vẽ trong cả kho: kệ bị thu nhỏ nên chỉ vẽ ô có hồ sơ (trừ khi bật
        // Nhãn vị trí), thùng cao hơn, thùng khớp tìm kiếm tô màu nổi bật để nhìn từ xa vẫn thấy.
        function shelfModel(ctx, shelf, f, room, allCells) {
            return {
                unit: 22, gap: 4, pad: 8, tierHeight: 54, boxHeight: room ? 34 : 17,
                codes: room && allCells,
                levels: shelf.levels,
                columns: shelf.columnList.map(function(c) {
                    return {
                        label: c.name || '',
                        code: c.code || '',
                        tiers: c.tierList.map(function(t) {
                            return {
                                label: t.name || t.code || '',
                                first: t.rank === 1,
                                // Tầng 1 nằm dưới cùng như kệ ngoài đời
                                level: t.rank - 1,
                                max: t.max,
                                depth: t.depth,
                                title: (shelf.code || '') + ' · ' + (t.name || ''),
                                cells: t.cells.map(function(cell) { return cellOf(ctx, t, cell, f); })
                                    .filter(function(cell) { return !room || allCells || cell.box; })
                                    .map(function(cell) {
                                        if (room && cell.hit && cell.box) cell.color = HIT_COLOR;
                                        return cell;
                                    })
                            };
                        })
                    };
                })
            };
        }

        function fitHeight(ctx) {
            if (ctx.mode === 'cards' || !ctx.panel.getClientRects().length) return;
            var title = ctx.panel.querySelector('.mz-3d-title');
            var list = ctx.panel.querySelector('.mz-3d-list');
            var scrollY = window.pageYOffset || 0;
            var stageTop = title.getBoundingClientRect().bottom + 8 + scrollY;
            var height = ctx.userHeight >= MIN_HEIGHT ? Math.round(ctx.userHeight)
                : Math.max(window.innerWidth <= 992 ? 380 : 560, Math.round(window.innerHeight - stageTop - 16));
            var listHeight = Math.max(220, Math.round(stageTop + height - (list.getBoundingClientRect().top + scrollY)));

            if (ctx.panel.style.getPropertyValue('--mz3d-h') !== height + 'px') ctx.panel.style.setProperty('--mz3d-h', height + 'px');
            if (ctx.panel.style.getPropertyValue('--mz3d-list') !== listHeight + 'px') ctx.panel.style.setProperty('--mz3d-list', listHeight + 'px');
        }

        function seekZoom(ctx) {
            return ctx.mode === 'room' ? 3 : 2.5;
        }

        // Có từ khoá: ô khớp nhấp nháy và khung 3D (từng kệ) bay tới chỗ đó; Mặt bằng giữ nguyên toàn cảnh kho.
        function seek(ctx) {
            var f = filterOf(ctx.root);
            var words = f.q + f.k;
            var key = ctx.mode + '|' + (ctx.mode === 'room' ? ctx.room : ctx.current) + '|' + f.q + '|' + f.k;
            ctx.stage.classList.toggle('is-seek', words !== '');
            if (key === ctx.seekKey || !ctx.stage.getClientRects().length) return;
            ctx.seekKey = key;

            if (ctx.mode !== 'room' && words !== '' && DamsShelf3d.focus(ctx.stage, { maxZoom: seekZoom(ctx) })) {
                ctx.zoomed = true;
            } else if (ctx.zoomed) {
                DamsShelf3d.focus(ctx.stage, { reset: true });
                ctx.zoomed = false;
            }
        }

        function renderStage(ctx) {
            var f = filterOf(ctx.root);
            var shelf = ctx.shelves.find(function(s) { return String(s.id) === String(ctx.current); });
            var title = ctx.panel.querySelector('.mz-3d-title');

            if (!shelf) {
                title.innerHTML = '';
                fitHeight(ctx);
                DamsShelf3d.render(ctx.stage, { columns: [], empty: 'Bộ phận này chưa khai kệ / tầng / vị trí ở Vị Trí Lưu Trữ → Cấu Trúc Kho.' });
                return;
            }

            var st = statOf(ctx, shelf, f);
            title.innerHTML = '<i class="fas fa-layer-group"></i> ' + esc(shelf.name || shelf.code || 'Kệ') +
                (shelf.code ? '<span class="tag">' + esc(shelf.code) + '</span>' : '') +
                '<span class="tag">' + esc(shelf.warehouse.name || '') + '</span>' +
                '<span class="tag">' + shelf.levels + ' tầng</span>' +
                '<span class="tag">' + st.filled + '/' + st.locations + ' vị trí có hồ sơ</span>' +
                (st.alerts ? '<span class="tag is-alert">' + st.alerts + ' sắp / quá hạn lưu trữ</span>' : '') +
                (f.active ? '<span class="tag">' + st.hits + ' vị trí khớp</span>' : '');

            fitHeight(ctx);
            DamsShelf3d.render(ctx.stage, shelfModel(ctx, shelf, f, false, true));
            ctx.stage.querySelector('.w3d-legend').innerHTML = legend();
        }

        /* ---------- chế độ Mặt bằng: cả kho theo mặt bằng đã khai ở Cấu Trúc Kho ---------- */

        // Kích thước gợi ý cho kệ chưa đặt trên mặt bằng (giống màn Mặt bằng kho): 0,25 m mỗi ô ngang.
        function defaultSize(shelf) {
            var widest = 1;
            shelf.columnList.forEach(function(c) {
                c.tierList.forEach(function(t) { widest = Math.max(widest, t.max); });
            });
            return { width: Math.max(Math.round((widest * 0.25 + 0.1) * 10) / 10, 0.6), depth: 0.4 };
        }

        // Gom kệ theo kho. Kho đã khai mặt bằng: kệ đặt đúng vị trí, kệ chưa đặt liệt kê riêng. Kho chưa khai:
        // xếp tạm các kệ thành hàng để vẫn xem / tìm được trên 3D.
        function buildRooms(ctx) {
            var rooms = {};
            ctx.shelves.forEach(function(shelf) {
                var key = shelf.warehouseKey || '0';
                var room = rooms[key] = rooms[key] || { key: key, wh: shelf.warehouse, shelves: [] };
                room.shelves.push(shelf);
            });

            return values(rooms).map(function(room) {
                var plan = room.wh.layout_room || null;
                room.items = [];
                room.unplaced = [];
                room.cells = room.shelves.reduce(function(sum, s) {
                    return sum + s.columnList[0].tierList.reduce(function(a, t) { return a + t.max; }, 0);
                }, 0);

                if (plan && Number(plan.width) > 0 && Number(plan.depth) > 0) {
                    room.width = Number(plan.width);
                    room.depth = Number(plan.depth);
                    room.polygon = plan.polygon;
                    room.door = plan.door;
                    room.shaped = !plan.is_rectangle;
                    room.shelves.forEach(function(shelf) {
                        if (shelf.layout_x == null || shelf.layout_x === '') {
                            room.unplaced.push(shelf);
                            return;
                        }
                        room.items.push({
                            shelf: shelf, x: Number(shelf.layout_x), y: Number(shelf.layout_y),
                            width: Number(shelf.layout_width) || defaultSize(shelf).width,
                            depth: Number(shelf.layout_depth) || defaultSize(shelf).depth,
                            rotation: Number(shelf.layout_rotation) || 0
                        });
                    });
                } else {
                    // Kho chưa khai mặt bằng: xếp các kệ thành nhiều hàng (mỗi hàng tối đa ~24 m) cho vừa khung nhìn
                    room.virtual = true;
                    var x = 0.5, y = 0.5, rowDepth = 0.4, maxX = 0;
                    room.shelves.forEach(function(shelf) {
                        var size = defaultSize(shelf);
                        if (x > 0.5 && x + size.width > 24) {
                            x = 0.5;
                            y += rowDepth + 1.2;
                            rowDepth = 0.4;
                        }
                        room.items.push({ shelf: shelf, x: x + size.width / 2, y: y + size.depth / 2, width: size.width, depth: size.depth, rotation: 0 });
                        x += size.width + 0.6;
                        maxX = Math.max(maxX, x);
                        rowDepth = Math.max(rowDepth, size.depth);
                    });
                    room.width = Math.max(Math.ceil(maxX - 0.1), 4);
                    room.depth = Math.max(Math.ceil(y + rowDepth + 1.5), 3);
                    room.polygon = [[0, 0], [room.width, 0], [room.width, room.depth], [0, room.depth]];
                    room.door = null;
                }
                return room;
            }).sort(function(a, b) {
                return String(a.wh.code || a.wh.name || '').localeCompare(String(b.wh.code || b.wh.name || ''), 'vi', { numeric: true });
            });
        }

        function roomStat(ctx, room, f) {
            var acc = { locations: 0, filled: 0, alerts: 0, hits: 0, byShelf: {} };
            room.shelves.forEach(function(shelf) {
                var st = statOf(ctx, shelf, f);
                acc.byShelf[shelf.id] = st;
                ['locations', 'filled', 'alerts', 'hits'].forEach(function(k) { acc[k] += st[k]; });
            });
            return acc;
        }

        function currentRoom(ctx) {
            return ctx.rooms.find(function(room) { return room.key === String(ctx.room); }) || ctx.rooms[0] || null;
        }

        // Hồ sơ khớp tra cứu trên một kệ - hiện thành thẻ chi tiết nổi trên đầu kệ ở Mặt bằng.
        var CARD_LINES = 4;

        function hitCard(ctx, shelf, f) {
            var lines = [];
            shelf.columnList.forEach(function(c) {
                c.tierList.forEach(function(t) {
                    t.cells.forEach(function(cell) {
                        var node = cell.key ? ctx.nodes[cell.key] : null;
                        if (!node || !node.items || !matches(f, node)) return;
                        node.items.forEach(function(x) {
                            lines.push({ name: x.name || 'Hồ sơ hạn chế', code: x.code || '', qty: t.name || '', loc: node.code || '' });
                        });
                    });
                });
            });
            if (!lines.length) return null;
            return {
                title: shelf.code || shelf.name || '',
                count: lines.length + ' khớp',
                lines: lines.slice(0, CARD_LINES),
                more: Math.max(0, lines.length - CARD_LINES)
            };
        }

        function renderRoomStage(ctx) {
            var f = filterOf(ctx.root);
            var room = currentRoom(ctx);
            var title = ctx.panel.querySelector('.mz-3d-title');

            if (!room) {
                title.innerHTML = '';
                fitHeight(ctx);
                DamsShelf3d.render(ctx.stage, { columns: [], empty: 'Bộ phận này chưa khai kệ / tầng / vị trí ở Vị Trí Lưu Trữ → Cấu Trúc Kho.' });
                return;
            }

            var st = roomStat(ctx, room, f);
            var allCells = ctx.codes && room.cells <= MAX_ROOM_CELLS;
            title.innerHTML = '<i class="fas fa-vector-square"></i> ' + esc(room.wh.name || 'Kho') +
                (room.wh.code ? '<span class="tag">' + esc(room.wh.code) + '</span>' : '') +
                '<span class="tag">' + (room.virtual ? 'Chưa khai mặt bằng' : 'Kho ' + room.width + ' × ' + room.depth + ' m' +
                    (room.shaped ? ' · ' + room.polygon.length + ' góc' : '')) + '</span>' +
                '<span class="tag">' + room.shelves.length + ' kệ</span>' +
                '<span class="tag">' + st.filled + '/' + st.locations + ' vị trí có hồ sơ</span>' +
                (st.alerts ? '<span class="tag is-alert">' + st.alerts + ' sắp / quá hạn lưu trữ</span>' : '') +
                (f.active ? '<span class="tag">' + st.hits + ' vị trí khớp</span>' : '') +
                (ctx.codes && !allCells ? '<span class="tag is-alert">Kho quá nhiều ô - chỉ vẽ ô có hồ sơ</span>' : '');

            fitHeight(ctx);
            var seeking = f.q !== '' || f.k !== '';
            DamsShelf3d.renderRoom(ctx.stage, {
                width: room.width, depth: room.depth, polygon: room.polygon, door: room.door, tierMetre: 0.4,
                empty: 'Kho này chưa đặt kệ nào trên mặt bằng.',
                shelves: room.items.map(function(item) {
                    var hits = st.byShelf[item.shelf.id].hits;
                    return {
                        key: String(item.shelf.id),
                        label: (item.shelf.code || item.shelf.name || '') + (f.active && hits ? ' · ' + hits + ' khớp' : ''),
                        x: item.x, y: item.y, width: item.width, depth: item.depth, rotation: item.rotation,
                        selected: f.active && hits > 0,
                        card: seeking && hits ? hitCard(ctx, item.shelf, f) : null,
                        model: shelfModel(ctx, item.shelf, f, true, allCells)
                    };
                })
            });
            ctx.stage.querySelector('.w3d-legend').innerHTML = legend() +
                (f.active ? '<span><i style="background:' + HIT_COLOR + '"></i>Khớp tra cứu</span>' : '');
        }

        // Sơ đồ mặt bằng thu nhỏ: kệ khớp sáng lên, nhấp đúp vào kệ để xem 3D riêng kệ đó.
        function planMarkup(ctx, room, st, f, width) {
            var sc = Math.min(width / room.width, 200 / room.depth);
            var shaped = room.polygon && !room.virtual;
            var html = '<div class="mz-plan' + (shaped ? ' is-poly' : '') + (f.q || f.k ? ' is-seek' : '') + '" style="width:' + (room.width * sc) +
                'px;height:' + (room.depth * sc) + 'px;background-size:' + sc + 'px ' + sc + 'px">';

            if (shaped) {
                var pts = room.polygon.map(function(p) { return (p[0] * sc).toFixed(1) + ',' + (p[1] * sc).toFixed(1); }).join(' ');
                var id = 'mzg' + Math.random().toString(36).slice(2, 8);
                html += '<svg class="mz-plan-svg" width="' + (room.width * sc) + '" height="' + (room.depth * sc) + '">' +
                    '<defs><pattern id="' + id + '" width="' + sc + '" height="' + sc + '" patternUnits="userSpaceOnUse">' +
                    '<path d="M ' + sc + ' 0 L 0 0 0 ' + sc + '" fill="none" stroke="rgba(10,85,112,0.16)" stroke-width="1"/></pattern></defs>' +
                    '<polygon points="' + pts + '" fill="#fff"/><polygon points="' + pts + '" fill="url(#' + id + ')" stroke="#0A5570" stroke-width="2"/>';
                var door = room.door;
                if (door && door.edge != null && room.polygon[door.edge]) {
                    var P = room.polygon[door.edge], Q = room.polygon[(door.edge + 1) % room.polygon.length];
                    var len = Math.hypot(Q[0] - P[0], Q[1] - P[1]) || 1;
                    var a0 = door.offset / len, a1 = (door.offset + door.width) / len;
                    html += '<line x1="' + ((P[0] + (Q[0] - P[0]) * a0) * sc).toFixed(1) + '" y1="' + ((P[1] + (Q[1] - P[1]) * a0) * sc).toFixed(1) +
                        '" x2="' + ((P[0] + (Q[0] - P[0]) * a1) * sc).toFixed(1) + '" y2="' + ((P[1] + (Q[1] - P[1]) * a1) * sc).toFixed(1) +
                        '" stroke="#17B8D4" stroke-width="5" stroke-dasharray="3 2"><title>Cửa ra vào</title></line>';
                }
                html += '</svg>';
            }

            room.items.forEach(function(item) {
                var ss = st.byShelf[item.shelf.id];
                var cls = f.active ? (ss.hits ? ' is-hit' : ' is-miss') : (ss.alerts ? ' is-alert' : (ss.filled ? ' is-stocked' : ''));
                html += '<button type="button" class="mz-plan-shelf' + cls + '" data-plan-shelf="' + esc(item.shelf.id) + '"' +
                    ' title="' + esc((item.shelf.code || '') + ' - ' + (item.shelf.name || '') + ' · ' + ss.filled + '/' + ss.locations +
                    ' vị trí có hồ sơ' + (f.active ? ' · ' + ss.hits + ' khớp' : '') + ' · nhấp đúp để xem 3D kệ này') + '"' +
                    ' style="left:' + ((item.x - item.width / 2) * sc) + 'px;top:' + ((item.y - item.depth / 2) * sc) + 'px;width:' +
                    (item.width * sc) + 'px;height:' + (item.depth * sc) + 'px;transform:rotate(' + item.rotation + 'deg)">' +
                    '<span style="transform:translate(-50%,-50%) rotate(' + (-item.rotation) + 'deg)">' + esc(item.shelf.code || '') + '</span></button>';
            });

            return html + '</div>';
        }

        function renderRoomList(ctx) {
            var f = filterOf(ctx.root);
            var list = ctx.panel.querySelector('.mz-3d-list');

            if (!ctx.rooms.length) {
                list.innerHTML = '<div class="mz-3d-none">Chưa có kho nào có vị trí.</div>';
                return;
            }

            var stats = {};
            ctx.rooms.forEach(function(room) { stats[room.key] = roomStat(ctx, room, f); });

            // Kho đang xem không còn vị trí khớp thì nhảy sang kho đầu tiên có vị trí khớp.
            var room = currentRoom(ctx);
            if (f.active && room && !stats[room.key].hits) {
                var first = ctx.rooms.find(function(r) { return stats[r.key].hits > 0; });
                if (first) ctx.room = first.key;
            }
            room = currentRoom(ctx);
            ctx.room = room.key;

            var html = ctx.rooms.map(function(r) {
                var st = stats[r.key];
                return '<button type="button" class="mz-3d-room' + (r === room ? ' is-active' : '') + (f.active && !st.hits ? ' is-miss' : '') +
                    '" data-z3d-room="' + esc(r.key) + '">' +
                    '<span class="nm"><i class="fas fa-warehouse" style="color: var(--primary-light)"></i>' + esc(r.wh.name || 'Kho') +
                    (f.active && st.hits ? '<span class="hit">' + st.hits + '</span>' : '') + '</span>' +
                    '<span class="sub">' + (r.wh.code ? esc(r.wh.code) + ' · ' : '') + r.shelves.length + ' kệ · ' +
                    st.filled + '/' + st.locations + ' vị trí có hồ sơ</span>' +
                    '</button>';
            }).join('');

            var width = Math.max(list.clientWidth - 24, 180);
            html += '<div class="mz-plan-box"><div class="mz-plan-head"><i class="fas fa-vector-square mr-1"></i>Mặt bằng ' + esc(room.wh.code || room.wh.name || '') + '</div>' +
                planMarkup(ctx, room, stats[room.key], f, width) +
                (room.virtual ? '<div class="mz-plan-note is-warn">Kho chưa khai mặt bằng ở Vị Trí Lưu Trữ → Cấu Trúc Kho: đang xếp tạm các kệ thành hàng.</div>' : '') +
                (room.unplaced.length ? '<div class="mz-plan-note is-warn">Chưa đặt trên mặt bằng: ' +
                    room.unplaced.map(function(shelf) { return esc(shelf.code || shelf.name); }).join(', ') + '</div>' : '') +
                '</div>';
            list.innerHTML = html;
        }

        function renderList(ctx) {
            var f = filterOf(ctx.root);
            var list = ctx.panel.querySelector('.mz-3d-list');
            var lastWh = null;
            var html = '';

            if (!ctx.shelves.length) {
                list.innerHTML = '<div class="mz-3d-none">Chưa có kệ nào có vị trí.</div>';
                return;
            }

            var stats = {};
            ctx.shelves.forEach(function(s) { stats[s.id] = statOf(ctx, s, f); });
            if (f.active && ctx.current != null && stats[ctx.current] && !stats[ctx.current].hits) {
                var first = ctx.shelves.find(function(s) { return stats[s.id].hits > 0; });
                if (first) ctx.current = first.id;
            }

            ctx.shelves.forEach(function(s) {
                var st = stats[s.id];
                if (s.warehouse.name !== lastWh) {
                    lastWh = s.warehouse.name;
                    html += '<div class="mz-3d-wh"><i class="fas fa-warehouse"></i>' + esc(lastWh) + '</div>';
                }
                var pct = st.locations ? Math.round(st.filled / st.locations * 100) : 0;
                html += '<button type="button" class="mz-3d-shelf' + (String(s.id) === String(ctx.current) ? ' is-active' : '') +
                    (f.active && !st.hits ? ' is-miss' : '') + '" data-shelf="' + esc(s.id) + '">' +
                    '<span class="nm"><i class="fas fa-layer-group" style="color: var(--primary-light)"></i>' +
                    esc(s.name || s.code || 'Kệ') + (f.active && st.hits ? '<span class="hit">' + st.hits + '</span>' : '') + '</span>' +
                    '<span class="sub">' + (s.code ? esc(s.code) + ' · ' : '') + st.filled + '/' + st.locations + ' vị trí có hồ sơ' +
                    (st.alerts ? ' · <span class="alert">' + st.alerts + ' sắp / quá hạn</span>' : '') + '</span>' +
                    '<span class="mz-3d-bar"><span style="width:' + pct + '%"></span></span>' +
                    '</button>';
            });
            list.innerHTML = html;
        }

        function renderMain(ctx) {
            if (ctx.mode === 'room') renderRoomStage(ctx);
            else renderStage(ctx);
        }

        function refresh(ctx) {
            if (ctx.mode === '3d') {
                renderList(ctx);
                renderStage(ctx);
                seek(ctx);
            } else if (ctx.mode === 'room') {
                renderRoomList(ctx);
                renderRoomStage(ctx);
                seek(ctx);
            }
        }

        function setMode(ctx, mode) {
            ctx.mode = mode === '3d' || mode === 'room' ? mode : 'cards';
            write(ctx.modeKey, ctx.mode);
            ctx.root.classList.toggle('mz-mode-3d', ctx.mode !== 'cards');
            ctx.root.classList.toggle('mz-mode-room', ctx.mode === 'room');
            ctx.root.querySelectorAll('[data-mz-view]').forEach(function(btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-mz-view') === ctx.mode);
            });
            refresh(ctx);
        }

        function applySide(ctx, value) {
            var width = value === '' || value == null ? null : Number(value);
            ctx.panel.classList.toggle('is-side-off', width === 0);
            if (width == null || isNaN(width)) ctx.panel.style.removeProperty('--mz3d-side');
            else ctx.panel.style.setProperty('--mz3d-side', width + 'px');
        }

        // Tay kéo dưới khung 3D (chiều cao) và giữa danh sách / khung (bề ngang danh sách); nhấp đúp về tự động.
        function bindResize(ctx) {
            var panel = ctx.panel;
            if (panel._z3dResizeBound) {
                panel._z3dResizeBound.ctx = ctx;
                return;
            }
            var bound = panel._z3dResizeBound = { ctx: ctx };
            var drag = null;

            panel.addEventListener('pointerdown', function(event) {
                var grip = event.target.closest('.mz-3d-grip, .mz-3d-split');
                if (!grip || event.button !== 0) return;
                event.preventDefault();
                var stage = panel.querySelector('.mz-3d-main .w3d');
                var list = panel.querySelector('.mz-3d-list');
                drag = {
                    el: grip,
                    height: grip.classList.contains('mz-3d-grip'),
                    x: event.clientX,
                    y: event.clientY,
                    start: grip.classList.contains('mz-3d-grip') ? stage.getBoundingClientRect().height
                        : (panel.classList.contains('is-side-off') ? 0 : list.getBoundingClientRect().width)
                };
                grip.classList.add('is-active');
                document.body.classList.add('mz-resizing');
                try { grip.setPointerCapture(event.pointerId); } catch (err) { /* bỏ qua */ }
            });

            panel.addEventListener('pointermove', function(event) {
                if (!drag) return;
                var c = bound.ctx;
                if (drag.height) {
                    c.userHeight = Math.max(MIN_HEIGHT, Math.min(3000, Math.round(drag.start + event.clientY - drag.y)));
                    fitHeight(c);
                } else {
                    var max = Math.max(160, panel.getBoundingClientRect().width * 0.5);
                    var width = Math.round(drag.start + event.clientX - drag.x);
                    drag.side = width < 120 ? 0 : Math.min(max, Math.max(160, width));
                    applySide(c, drag.side);
                }
            });

            var end = function() {
                if (!drag) return;
                var c = bound.ctx;
                drag.el.classList.remove('is-active');
                document.body.classList.remove('mz-resizing');
                if (drag.height) write(HEIGHT_KEY, String(c.userHeight));
                else if (drag.side != null) write(SIDE_KEY, String(drag.side));
                drag = null;
            };
            panel.addEventListener('pointerup', end);
            panel.addEventListener('pointercancel', end);

            panel.addEventListener('dblclick', function(event) {
                var grip = event.target.closest('.mz-3d-grip, .mz-3d-split');
                if (!grip) return;
                var c = bound.ctx;
                if (grip.classList.contains('mz-3d-grip')) {
                    c.userHeight = 0;
                    write(HEIGHT_KEY, '0');
                    fitHeight(c);
                } else {
                    write(SIDE_KEY, '');
                    applySide(c, '');
                }
            });
        }

        function init(root, opts) {
            if (!root || !window.DamsShelf3d) return;
            var $root = $(root);
            $root.off('.z3d');

            var tools = root.querySelector('.mz-tools');
            if (tools && !tools.querySelector('.mz-view')) {
                tools.insertAdjacentHTML('afterbegin', '<div class="mz-view" role="group" aria-label="Kiểu sơ đồ">' +
                    '<button type="button" data-mz-view="cards"><i class="fas fa-th-large mr-1"></i>Thẻ</button>' +
                    '<button type="button" data-mz-view="3d" title="Xem 3D từng kệ"><i class="fas fa-cube mr-1"></i>3D</button>' +
                    '<button type="button" data-mz-view="room" title="Xem cả kho theo mặt bằng"><i class="fas fa-vector-square mr-1"></i>Mặt bằng</button></div>');
            }
            var panel = root.querySelector('.mz-3d');
            if (!panel) {
                var head = root.querySelector('.mz-head');
                var holder = document.createElement('div');
                holder.className = 'mz-3d';
                holder.innerHTML = '<aside class="mz-3d-list"></aside>' +
                    '<div class="mz-3d-split" title="Kéo để đổi bề ngang danh sách (kéo hết sang trái để ẩn) · nhấp đúp về mặc định"></div>' +
                    '<div class="mz-3d-main"><div class="mz-3d-title"></div>' +
                    '<div class="w3d" title="Kéo chuột để xoay · bấm thùng hồ sơ khớp tra cứu để xem chi tiết">' +
                    DamsShelf3d.markup({
                        // Nhãn vị trí (số ô) trên mọi ô của kệ - chỉ có ở chế độ Mặt bằng
                        tools: '<div class="w3d-group mz-codes-group" role="group" aria-label="Nhãn vị trí">' +
                            '<button type="button" data-z3d-codes title="Bật / tắt vẽ mọi ô (kể cả ô trống) kèm số ô">' +
                            '<i class="fas fa-tags mr-1"></i>Nhãn vị trí</button></div>'
                    }) + '</div>' +
                    '<div class="mz-3d-grip" title="Kéo để đổi chiều cao khung · nhấp đúp về chiều cao tự động"></div></div>';
                if (head) head.insertAdjacentElement('afterend', holder);
                else root.appendChild(holder);
                panel = holder;
            }

            var nodes = opts.nodes || {};
            var ctx = {
                root: root,
                panel: panel,
                stage: panel.querySelector('.w3d'),
                nodes: nodes,
                shelves: buildShelves(opts.zones || {}, nodes),
                codes: read(CODES_KEY, '0') === '1',
                mode: 'cards',
                modeKey: opts.modeKey || MODE_KEY,
                current: null,
                userHeight: Number(read(HEIGHT_KEY, '0')) || 0
            };
            applySide(ctx, read(SIDE_KEY, ''));
            bindResize(ctx);

            // Mặc định mở kệ đầu tiên có vị trí khớp (không có thì kệ đầu tiên có hồ sơ), và kho chứa kệ đó.
            var f0 = filterOf(root);
            var first = (f0.active && ctx.shelves.find(function(s) { return statOf(ctx, s, f0).hits > 0; })) ||
                ctx.shelves.find(function(s) { return statOf(ctx, s, { active: false }).filled > 0; }) || ctx.shelves[0] || null;
            ctx.current = first ? first.id : null;
            ctx.rooms = buildRooms(ctx);
            ctx.room = first ? first.warehouseKey : (ctx.rooms[0] ? ctx.rooms[0].key : null);

            $root.on('click.z3d', '[data-mz-view]', function() {
                setMode(ctx, this.getAttribute('data-mz-view'));
            });
            $root.on('click.z3d', '.mz-3d-shelf', function() {
                ctx.current = this.getAttribute('data-shelf');
                refresh(ctx);
            });
            var syncCodes = function() {
                panel.querySelectorAll('[data-z3d-codes]').forEach(function(btn) { btn.classList.toggle('active', ctx.codes); });
            };
            syncCodes();
            $root.on('click.z3d', '[data-z3d-codes]', function() {
                ctx.codes = !ctx.codes;
                write(CODES_KEY, ctx.codes ? '1' : '0');
                syncCodes();
                renderMain(ctx);
            });
            // Mặt bằng: chọn kho; NHẤP ĐÚP kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hồ sơ) = xem 3D riêng kệ đó.
            $root.on('click.z3d', '[data-z3d-room]', function() {
                ctx.room = this.getAttribute('data-z3d-room');
                refresh(ctx);
            });
            $root.on('dblclick.z3d', '[data-plan-shelf]', function() {
                ctx.current = this.getAttribute('data-plan-shelf');
                setMode(ctx, '3d');
            });
            $root.on('dblclick.z3d', '[data-w3d-shelf]', function(event) {
                if ($(event.target).closest('[data-w3d-key]').length) return;
                ctx.current = this.getAttribute('data-w3d-shelf');
                setMode(ctx, '3d');
            });
            $root.on('input.z3d', '.mz-search input', function() {
                clearTimeout(ctx.timer);
                ctx.timer = setTimeout(function() { refresh(ctx); }, 200);
            });
            $root.on('keydown.z3d', '.mz-search input', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                $(this).trigger('input');
                this.select();
            });
            if (ctx.stage._z3dFull) ctx.stage.removeEventListener('w3d:full', ctx.stage._z3dFull);
            ctx.stage._z3dFull = function() {
                if (ctx.zoomed) DamsShelf3d.focus(ctx.stage, { maxZoom: seekZoom(ctx) });
            };
            ctx.stage.addEventListener('w3d:full', ctx.stage._z3dFull);
            if (root._z3dResize) window.removeEventListener('resize', root._z3dResize);
            root._z3dResize = function() {
                clearTimeout(ctx.resizeTimer);
                ctx.resizeTimer = setTimeout(function() { fitHeight(ctx); }, 150);
            };
            window.addEventListener('resize', root._z3dResize);
            if (window.ResizeObserver) {
                if (root._z3dObserver) root._z3dObserver.disconnect();
                root._z3dObserver = new ResizeObserver(function() {
                    window.requestAnimationFrame(function() { fitHeight(ctx); });
                });
                root._z3dObserver.observe(panel);
            }
            // Bấm thùng hồ sơ (khớp tra cứu) = mở hộp chi tiết của trang
            $root.on('click.z3d', '[data-w3d-key]', function() {
                var key = this.getAttribute('data-w3d-key');
                if (typeof opts.open === 'function') opts.open(key);
            });

            // opts.mode: trang ép kiểu xem (vd. chưa có từ khoá thì chỉ có tổng quan), không thì nhớ theo máy
            setMode(ctx, opts.mode || read(ctx.modeKey, opts.defaultMode || 'cards'));
        }

        window.damsZone3d = { init: init };
    })();
</script>
@endonce
