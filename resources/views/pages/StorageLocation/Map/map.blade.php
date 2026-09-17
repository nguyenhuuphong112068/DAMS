<div class="content-wrapper">
    <div class="card mt-5 wm">
        <div class="card-header wm-tabs-head">
            <div class="wm-tabs" role="tablist">
                <button type="button" class="wm-tab is-active" role="tab" data-tab="map" aria-selected="true">
                    <i class="fas fa-th mr-1"></i> Sơ đồ kho
                </button>
                <button type="button" class="wm-tab" role="tab" data-tab="relabel" aria-selected="false">
                    <i class="fas fa-print mr-1"></i> Nhãn cần in lại
                    <span id="wm-relabel-count" class="wm-count" hidden>0</span>
                </button>
            </div>
        </div>

        <div id="wm-pane-map" role="tabpanel">
            <div class="wm-toolbar">
                <nav id="wm-breadcrumb" class="wm-breadcrumb"></nav>
                <div class="wm-search">
                    <input id="wm-search" type="search" class="form-control form-control-sm"
                        placeholder="Tìm mã tài liệu / mã vị trí..." autocomplete="off">
                    <div id="wm-search-results" class="wm-search-results" hidden></div>
                </div>
            </div>

            <div class="card-body">
                <div id="wm-legend" class="wm-legend" aria-label="Chú giải"></div>
                <div id="wm-canvas" class="wm-canvas" aria-live="polite"></div>
            </div>
        </div>

        <div id="wm-pane-relabel" class="card-body" role="tabpanel" hidden>
            <div class="wm-relabel-head">
                <div>
                    <div class="wm-relabel-title">Hồ sơ đã đổi vị trí, nhãn đang in vị trí cũ</div>
                    <div id="wm-relabel-summary" class="wm-empty-note"></div>
                </div>
                <button type="button" id="wm-relabel-refresh" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-sync-alt mr-1"></i> Làm mới
                </button>
            </div>
            <div class="wm-table-wrap">
                <table class="table table-sm table-hover wm-relabel-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Mã hồ sơ</th>
                            <th>Tên hồ sơ</th>
                            <th>Vị trí trên nhãn</th>
                            <th>Vị trí hiện tại</th>
                            <th>Người chuyển</th>
                            <th>Thời gian</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="wm-relabel-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="wm-toast" class="wm-toast" role="status" aria-live="polite" hidden></div>

    <div id="wm-tooltip" class="wm-tooltip" role="tooltip" hidden></div>

    {{-- Cửa sổ nổi không có nền che, để vẫn bấm được các ô khác khi đang mở --}}
    <section id="wm-float" class="wm-float" role="dialog" aria-labelledby="wm-float-title" hidden>
        <header id="wm-float-head" class="wm-float-head">
            <span id="wm-float-title" class="wm-float-title">
                <i class="fas fa-grip-vertical mr-2"></i>Chi tiết vị trí
                <span id="wm-float-code" class="wm-float-code"></span>
            </span>
            <span class="wm-float-actions">
                <button type="button" id="wm-float-min" class="wm-float-btn" aria-label="Thu nhỏ" title="Thu nhỏ">
                    <i class="fas fa-window-minimize"></i>
                </button>
                <button type="button" id="wm-float-close" class="wm-float-btn" aria-label="Đóng" title="Đóng">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        </header>
        <div id="wm-panel" class="wm-panel"></div>
    </section>
</div>

@verbatim
<style>
/* Cửa sổ nổi nằm ngoài .wm nên phải khai báo biến màu cho cả nó. */
.wm, .wm-float {
    --wm-surface: #fcfcfb;
    --wm-ink: #0b0b0b;
    --wm-ink-2: #52514e;
    --wm-ink-muted: #898781;
    --wm-hairline: rgba(11, 11, 11, .12);
    --wm-gridline: #e1e0d9;
    --wm-baseline: #c3c2b7;

    /* Thang một màu: càng đậm càng đầy */
    --wm-f0: #fcfcfb;
    --wm-f1: #cde2fb;
    --wm-f2: #9ec5f4;
    --wm-f3: #5598e7;
    --wm-f4: #2a78d6;
    --wm-f5: #184f95;

    --wm-off: #f0efec;
    --wm-cell: 30px;
    font-variant-numeric: tabular-nums;
}

.wm-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem; font-size: .95rem; }
.wm-crumb { background: none; border: 0; padding: .1rem .35rem; color: #2a78d6; cursor: pointer; border-radius: 4px; }
.wm-crumb:hover { background: #eef4fd; }
.wm-crumb[aria-current="page"] { color: var(--wm-ink); font-weight: 600; cursor: default; }
.wm-crumb[aria-current="page"]:hover { background: none; }
.wm-crumb-sep { color: var(--wm-ink-muted); }

.wm-search { position: relative; width: 240px; }
.wm-search-results {
    position: absolute; z-index: 30; top: 100%; left: 0; right: 0; max-height: 300px; overflow-y: auto;
    background: #fff; border: 1px solid var(--wm-hairline); border-radius: 6px; box-shadow: 0 6px 18px rgba(0,0,0,.12);
}
.wm-hit { display: block; width: 100%; text-align: left; border: 0; background: none; padding: .4rem .6rem; font-size: .8rem; border-bottom: 1px solid var(--wm-gridline); }
.wm-hit:last-child { border-bottom: 0; }
.wm-hit:hover { background: #eef4fd; }
.wm-hit strong { display: block; color: var(--wm-ink); }
.wm-hit span { color: var(--wm-ink-2); }
.wm-hit-empty { padding: .5rem .6rem; font-size: .8rem; color: var(--wm-ink-muted); }

.wm-legend {
    display: flex; flex-wrap: wrap; align-items: center; gap: .85rem; margin-bottom: 1.25rem;
    padding: .65rem 1.1rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;
    font-size: .82rem; color: #475569;
}
.wm-legend-item { display: inline-flex; align-items: center; gap: .4rem; font-weight: 500; }
.wm-swatch { width: 14px; height: 14px; border-radius: 4px; border: 1px solid rgba(0,0,0,.12); display: inline-block; }

.wm-canvas { min-height: 320px; }

/* ---------- Cards Kho / Kệ Tổng quan (Hiện đại, rộng rãi, chuyên nghiệp) ---------- */
.wm-tiles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 1.25rem;
    margin-top: 0.5rem;
}

.wm-tile {
    position: relative;
    text-align: left;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.35rem 1.15rem;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.03);
    transition: all 0.24s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    overflow: hidden;
    user-select: none;
}

.wm-tile::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #94a3b8;
    transition: height 0.2s ease;
}

.wm-tile:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px -6px rgba(0, 58, 93, 0.12), 0 4px 10px -2px rgba(0, 58, 93, 0.06);
    border-color: #3b82f6;
    outline: none;
}

.wm-tile:hover::before {
    height: 5px;
}

.wm-tile:hover .wm-tile-arrow {
    color: #2563eb;
}

.wm-tile:hover .wm-tile-arrow i {
    transform: translateX(3px);
}

/* Header hàng trên */
.wm-tile-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
}

.wm-tile-code-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
}

.wm-tile-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #0284c7;
    font-size: 1.1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.wm-tile:hover .wm-tile-icon {
    background: #e0f2fe;
    color: #0369a1;
    transform: scale(1.05);
}

.wm-tile-code {
    font-weight: 800;
    font-size: 1.35rem;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

/* Badge phần trăm */
.wm-tile-badge {
    font-size: 0.76rem;
    font-weight: 700;
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    letter-spacing: 0.01em;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    line-height: 1;
}

/* Tên Kho */
.wm-tile-name {
    font-size: 0.98rem;
    font-weight: 600;
    color: #334155;
    line-height: 1.45;
    margin-bottom: 0.9rem;
    min-height: 2.85rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}

/* Thanh tiến trình lấp đầy */
.wm-tile-progress-wrap {
    width: 100%;
    height: 7px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 0.85rem;
}

.wm-tile-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: #3b82f6;
    transition: width 0.4s ease;
}

/* Footer hàng dưới */
.wm-tile-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
    font-size: 0.82rem;
    color: #64748b;
}

.wm-tile-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.wm-tile-used strong {
    color: #0f172a;
    font-weight: 700;
}

.wm-tile-avail {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.12rem 0.45rem;
    border-radius: 4px;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.wm-tile-avail.is-full {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
    font-weight: 600;
}

.wm-tile-arrow {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    transition: color 0.2s ease;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    margin-left: auto;
}

.wm-tile-arrow i {
    transition: transform 0.2s ease;
    font-size: 0.7rem;
}

/* Các cấp độ mức lấp đầy (0% -> 100%) */
/* b0: Trống (0%) */
.wm-tile.wm-b0::before { background: #cbd5e1; }
.wm-badge-b0 { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.wm-bar-b0 { background: #cbd5e1; }

/* b1: 1-25% */
.wm-tile.wm-b1::before { background: #0ea5e9; }
.wm-badge-b1 { background: #e0f2fe; color: #0284c7; }
.wm-bar-b1 { background: linear-gradient(90deg, #38bdf8, #0ea5e9); }

/* b2: 26-50% */
.wm-tile.wm-b2::before { background: #2563eb; }
.wm-badge-b2 { background: #dbeafe; color: #1d4ed8; }
.wm-bar-b2 { background: linear-gradient(90deg, #60a5fa, #2563eb); }

/* b3: 51-75% */
.wm-tile.wm-b3::before { background: #4f46e5; }
.wm-badge-b3 { background: #ede9fe; color: #4338ca; }
.wm-bar-b3 { background: linear-gradient(90deg, #818cf8, #4f46e5); }

/* b4: 76-99% */
.wm-tile.wm-b4::before { background: #d97706; }
.wm-badge-b4 { background: #fef3c7; color: #b45309; }
.wm-bar-b4 { background: linear-gradient(90deg, #fbbf24, #d97706); }

/* b5: Đầy 100% */
.wm-tile.wm-b5::before { background: #dc2626; }
.wm-badge-b5 { background: #fee2e2; color: #b91c1c; }
.wm-bar-b5 { background: linear-gradient(90deg, #f87171, #dc2626); }

/* Swatches trong Legend */
.wm-swatch.wm-b0 { background: #f1f5f9; border-color: #cbd5e1; }
.wm-swatch.wm-b1 { background: #e0f2fe; border-color: #38bdf8; }
.wm-swatch.wm-b2 { background: #dbeafe; border-color: #3b82f6; }
.wm-swatch.wm-b3 { background: #ede9fe; border-color: #6366f1; }
.wm-swatch.wm-b4 { background: #fef3c7; border-color: #f59e0b; }
.wm-swatch.wm-b5 { background: #fee2e2; border-color: #ef4444; }

.wm-shelf { margin-bottom: 1.25rem; }
.wm-shelf-head { display: flex; align-items: baseline; gap: .5rem; margin-bottom: .35rem; }
.wm-shelf-title { font-weight: 700; font-size: .9rem; color: var(--wm-ink); background: none; border: 0; padding: 0; cursor: pointer; }
.wm-shelf-title:hover { text-decoration: underline; }
.wm-shelf-stat { font-size: .75rem; color: var(--wm-ink-2); }

/* Kệ rộng tới 50+ cột nên phải cuộn ngang, nhãn tầng dính trái để không mất ngữ cảnh. */
.wm-scroll { overflow-x: auto; overflow-y: hidden; padding-bottom: 2px; }
.wm-grid { display: inline-block; min-width: max-content; }
.wm-row { display: flex; align-items: center; gap: .4rem; margin-bottom: 2px; }
.wm-row-label {
    width: 72px; flex: none; font-size: .78rem; color: var(--wm-ink-2); text-align: right;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    position: sticky; left: 0; z-index: 1; background: #fff;
}
.wm-cols { display: grid; grid-template-columns: repeat(var(--cols), var(--wm-cell)); gap: 2px; }
.wm-colhead { font-size: .68rem; color: var(--wm-ink-muted); text-align: center; overflow: hidden; }

.wm-cell {
    width: var(--wm-cell); height: var(--wm-cell); border-radius: 4px; border: 1px solid var(--wm-hairline);
    padding: 0; line-height: 1; cursor: pointer; overflow: hidden; font-weight: 600;
    font-size: clamp(9px, calc(var(--wm-cell) * .3), 15px);
}
/* Ô trống viền rõ, ô đã lưu tô đặc: nhìn lướt là thấy ngay chỗ nào còn chỗ. */
.wm-cell--empty { background: #fff; color: var(--wm-ink-muted); border-color: var(--wm-baseline); font-weight: 400; }
.wm-cell--busy { background: var(--wm-f4); color: #fff; border-color: var(--wm-f5); }
.wm-cell--off {
    background: repeating-linear-gradient(45deg, var(--wm-off), var(--wm-off) 3px, #dcdbd6 3px, #dcdbd6 6px);
    color: var(--wm-ink-muted);
}
.wm-cell--none { background: transparent; border-style: dashed; border-color: var(--wm-gridline); cursor: default; }
.wm-cell:not(.wm-cell--none):hover { outline: 2px solid #0b0b0b; outline-offset: 1px; }
.wm-cell--selected { outline: 3px solid #eb6834 !important; outline-offset: 1px; }

/* Kệ hẹp mà cao thì xếp cạnh nhau, nếu không sẽ phí phần lớn chiều ngang màn hình. */
.wm-dense { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0 1.4rem; }
.wm-dense .wm-cell { border-radius: 3px; }
.wm-dense .wm-row-label { width: 60px; font-size: .74rem; }
.wm-dense .wm-shelf { margin-bottom: 1.25rem; max-width: 100%; min-width: 0; }
.wm-dense .wm-shelf-head { gap: .4rem; }
.wm-dense .wm-shelf-title { font-size: .88rem; }
.wm-dense .wm-shelf-stat { font-size: .74rem; }

.wm-float {
    position: fixed; z-index: 1070; top: 120px; right: 24px; width: 380px; max-width: calc(100vw - 32px);
    background: #fff; border: 1px solid var(--wm-hairline); border-radius: 10px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, .18); display: flex; flex-direction: column;
    max-height: calc(100vh - 40px);
}
.wm-float-head {
    display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    padding: .5rem .6rem .5rem .85rem; background: var(--wm-f5); color: #fff;
    border-radius: 10px 10px 0 0; cursor: move; user-select: none; touch-action: none; font-weight: 600; font-size: .85rem;
}
.wm-float-head.is-dragging { cursor: grabbing; }
.wm-float-title { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wm-float-code { font-weight: 400; opacity: .85; }
.wm-float-actions { display: inline-flex; flex: none; gap: 2px; }
.wm-float-btn {
    width: 28px; height: 26px; background: none; border: 0; color: #fff; font-size: .85rem; line-height: 1;
    border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
}
.wm-float-btn:hover { background: rgba(255, 255, 255, .2); }
#wm-float-min i { transform: translateY(-3px); }
.wm-panel { padding: .85rem; overflow-y: auto; }

/* Thu nhỏ: chỉ còn thanh tiêu đề, đủ để kéo đi hoặc mở lại. */
.wm-float.is-min { width: 280px; }
.wm-float.is-min .wm-float-head { border-radius: 10px; }
.wm-float.is-min .wm-panel { display: none; }
.wm-float.is-min #wm-float-min i { transform: none; }
.wm-panel h6 { font-weight: 700; margin-bottom: .1rem; }
.wm-panel-path { font-size: .75rem; color: var(--wm-ink-2); margin-bottom: .6rem; }
.wm-doc { border-top: 1px solid var(--wm-gridline); padding-top: .55rem; margin-top: .55rem; font-size: .8rem; }
.wm-doc dt { font-weight: 600; color: var(--wm-ink-2); font-size: .72rem; margin-top: .35rem; }
.wm-doc dd { margin: 0; }
.wm-empty-note { font-size: .82rem; color: var(--wm-ink-muted); }
.wm-note { flex: 0 0 100%; }

.wm-tooltip {
    position: fixed; z-index: 1080; pointer-events: none; background: #0b0b0b; color: #fff;
    padding: .35rem .5rem; border-radius: 5px; font-size: .74rem; line-height: 1.35; max-width: 240px;
    box-shadow: 0 4px 14px rgba(0,0,0,.25);
}
.wm-tooltip b { color: #fff; }

.wm-alert { border: 1px solid #f5c98a; background: #fff8ec; border-radius: 8px; padding: .75rem .9rem; font-size: .85rem; }

/* ---------- Tab ---------- */
.wm-tabs-head { padding-bottom: 0; border-bottom: 1px solid var(--wm-gridline); }
.wm-tabs { display: flex; gap: .25rem; flex-wrap: wrap; }
.wm-tab {
    background: none; border: 0; border-bottom: 3px solid transparent; padding: .55rem .9rem;
    font-weight: 600; font-size: .9rem; color: var(--wm-ink-2); cursor: pointer;
}
.wm-tab:hover { color: var(--wm-ink); }
.wm-tab.is-active { color: var(--wm-f5); border-bottom-color: var(--wm-f5); }
.wm-count {
    display: inline-block; min-width: 20px; padding: 1px 6px; margin-left: .3rem; border-radius: 10px;
    background: #eb6834; color: #fff; font-size: .72rem; line-height: 16px; text-align: center;
}
.wm-toolbar {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .75rem 1.25rem 0;
}

/* ---------- Kéo thả đổi vị trí ---------- */
.wm-cell { position: relative; }
.wm-cell[data-doc] { cursor: grab; }
.wm-is-dragging, .wm-is-dragging * { cursor: grabbing !important; user-select: none !important; }
.wm-ghost {
    position: fixed; left: 0; top: 0; z-index: 1100; pointer-events: none;
    background: #184f95; color: #fff; border-radius: 6px; padding: .3rem .55rem; font-size: .8rem; font-weight: 600;
    box-shadow: 0 6px 18px rgba(0,0,0,.28); white-space: nowrap; display: flex; align-items: center; gap: .1rem;
}
.wm-ghost[hidden] { display: none; }
.wm-ghost small { font-weight: 400; opacity: .8; margin-left: .45rem; }
.wm-ghost.is-ok { background: #0c7a0c; }
.wm-cell--dragging { opacity: .35; }
.wm-cell--drop { outline: 3px solid #0ca30c !important; outline-offset: 1px; background: #e7f6e7 !important; }
/* Góc cam: hồ sơ đã đổi vị trí, nhãn đang in vị trí cũ. */
.wm-cell--stale::after {
    content: ""; position: absolute; top: 0; right: 0; width: 0; height: 0;
    border-style: solid; border-width: 0 9px 9px 0; border-color: transparent #eb6834 transparent transparent;
}
.wm-is-dragging .wm-float { opacity: .35; pointer-events: none; }
.wm-is-dragging .wm-tooltip { display: none; }
.wm-drag-hint { color: var(--wm-ink-muted); margin-left: auto; }

.wm-doc[data-doc] { cursor: grab; border-radius: 6px; user-select: none; }
.wm-doc[data-doc]:hover { background: #f4f8fe; }
.wm-doc-hint { font-size: .7rem; color: var(--wm-ink-muted); margin-top: .35rem; }
.wm-stale-note {
    margin-top: .45rem; padding: .35rem .5rem; border-radius: 5px; background: #fff3ec;
    border: 1px solid #f6c3a8; font-size: .74rem; color: #8a3d17;
}
.wm-stale-note a { color: #8a3d17; font-weight: 600; text-decoration: underline; }

.wm-toast {
    position: fixed; z-index: 1095; left: 50%; bottom: 24px; transform: translateX(-50%);
    max-width: calc(100vw - 32px); background: #0b0b0b; color: #fff; border-radius: 8px;
    padding: .6rem .9rem; font-size: .85rem; box-shadow: 0 8px 24px rgba(0,0,0,.3);
    display: flex; align-items: center; gap: .75rem;
}
.wm-toast[hidden] { display: none; }
.wm-toast--error { background: #b42b2b; }
.wm-toast button { background: none; border: 1px solid rgba(255,255,255,.5); color: #fff; border-radius: 5px; padding: .15rem .5rem; font-size: .78rem; white-space: nowrap; }
.wm-toast button:hover { background: rgba(255,255,255,.15); }

/* ---------- Tab nhãn cần in lại ---------- */
.wm-relabel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: .75rem; flex-wrap: wrap; }
.wm-relabel-title { font-weight: 700; }
.wm-table-wrap { overflow-x: auto; }
.wm-relabel-table { font-size: .84rem; }
.wm-relabel-table th { white-space: nowrap; color: var(--wm-ink-2); font-weight: 600; border-top: 0; }
.wm-relabel-table td { vertical-align: middle; }
.wm-relabel-table .wm-old { color: var(--wm-ink-muted); text-decoration: line-through; white-space: nowrap; }
.wm-relabel-table .wm-new { color: #006300; font-weight: 600; white-space: nowrap; }
.wm-relabel-table .wm-actions { white-space: nowrap; text-align: right; }

@media (max-width: 991px) {
    .wm-search { width: 100%; }
}
</style>
@endverbatim

<script type="application/json" id="wm-config">
{
    "summary": "{{ route('pages.storageLocation.map.summary') }}",
    "grid": "{{ route('pages.storageLocation.map.grid') }}",
    "cell": "{{ route('pages.storageLocation.map.cell') }}",
    "locate": "{{ route('pages.storageLocation.map.locate') }}",
    "move": "{{ route('pages.storageLocation.map.move') }}",
    "relabel": "{{ route('pages.storageLocation.map.relabel') }}",
    "label": "{{ route('pages.documentStorage.document.label') }}",
    "csrf": "{{ csrf_token() }}"
}
</script>

@verbatim
<script>
(function () {
    'use strict';

    var ROUTES = JSON.parse(document.getElementById('wm-config').textContent);

    var canvas = document.getElementById('wm-canvas');
    var panel = document.getElementById('wm-panel');
    var floatBox = document.getElementById('wm-float');
    var floatHead = document.getElementById('wm-float-head');
    var floatClose = document.getElementById('wm-float-close');
    var floatMin = document.getElementById('wm-float-min');
    var floatCode = document.getElementById('wm-float-code');
    var legend = document.getElementById('wm-legend');
    var crumbs = document.getElementById('wm-breadcrumb');
    var tooltip = document.getElementById('wm-tooltip');
    var searchInput = document.getElementById('wm-search');
    var searchResults = document.getElementById('wm-search-results');
    var toast = document.getElementById('wm-toast');
    var relabelCount = document.getElementById('wm-relabel-count');
    var relabelBody = document.getElementById('wm-relabel-body');
    var relabelSummary = document.getElementById('wm-relabel-summary');
    var paneMap = document.getElementById('wm-pane-map');
    var paneRelabel = document.getElementById('wm-pane-relabel');

    var state = {
        warehouseId: null,
        warehouseName: '',
        shelfId: null,
        shelfName: '',
        selectedCell: null,
        pendingCell: null
    };

    var BUCKETS = [
        { cls: 'wm-b0', label: 'Trống' },
        { cls: 'wm-b1', label: '1-25%' },
        { cls: 'wm-b2', label: '26-50%' },
        { cls: 'wm-b3', label: '51-75%' },
        { cls: 'wm-b4', label: '76-99%' },
        { cls: 'wm-b5', label: 'Đầy' }
    ];

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function bucketOf(used, total) {
        if (!total) return 0;
        var pct = used / total * 100;
        if (pct <= 0) return 0;
        if (pct <= 25) return 1;
        if (pct <= 50) return 2;
        if (pct <= 75) return 3;
        if (pct < 100) return 4;
        return 5;
    }

    function pctOf(used, total) {
        return total ? Math.round(used / total * 100) : 0;
    }

    function groupBy(list, keyFn) {
        var map = new Map();
        list.forEach(function (item) {
            var key = keyFn(item);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(item);
        });
        return map;
    }

    function shortLabel(code) {
        var parts = String(code || '').split(/[-_.\/]/);
        return parts[parts.length - 1].slice(-3);
    }

    function fetchJson(url, params) {
        var query = new URLSearchParams();
        Object.keys(params || {}).forEach(function (key) {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                query.append(key, params[key]);
            }
        });
        var full = query.toString() ? url + '?' + query.toString() : url;

        return fetch(full, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            });
    }

    /* ---------- Chú giải ---------- */

    function renderLegend(mode) {
        var items;

        if (mode === 'grid') {
            items = [
                { style: 'background:#fff;border-color:var(--wm-baseline)', label: 'Trống' },
                { style: 'background:var(--wm-f4);border-color:var(--wm-f5)', label: 'Đã lưu' },
                { style: 'background:repeating-linear-gradient(45deg,#f0efec,#f0efec 3px,#dcdbd6 3px,#dcdbd6 6px)', label: 'Ngưng sử dụng' },
                { style: 'background:transparent;border-style:dashed', label: 'Chưa tạo vị trí' },
                { style: 'background:linear-gradient(225deg,#eb6834 0 5px,var(--wm-f4) 5px);border-color:var(--wm-f5)', label: 'Cần in lại nhãn' }
            ];
        } else {
            items = BUCKETS.map(function (bucket) {
                return { cls: bucket.cls, label: bucket.label };
            });
        }

        legend.innerHTML = '<span class="text-muted">Mức lấp đầy:</span>' + items.map(function (item) {
            var swatch = item.cls
                ? '<span class="wm-swatch ' + item.cls + '"></span>'
                : '<span class="wm-swatch" style="' + item.style + '"></span>';
            return '<span class="wm-legend-item">' + swatch + esc(item.label) + '</span>';
        }).join('') + (mode === 'grid'
            ? '<span class="wm-drag-hint"><i class="fas fa-hand-paper mr-1"></i>Kéo ô đã lưu thả vào ô trống để đổi vị trí</span>'
            : '');
    }

    /* ---------- Breadcrumb ---------- */

    function renderCrumbs() {
        var parts = [{ label: 'Tất cả kho', level: 'root' }];
        if (state.warehouseId) parts.push({ label: state.warehouseName || 'Kho', level: 'warehouse' });
        if (state.shelfId) parts.push({ label: state.shelfName || 'Kệ', level: 'shelf' });

        crumbs.innerHTML = parts.map(function (part, index) {
            var last = index === parts.length - 1;
            var button = '<button type="button" class="wm-crumb" data-level="' + part.level + '"'
                + (last ? ' aria-current="page"' : '') + '>' + esc(part.label) + '</button>';
            return index ? '<span class="wm-crumb-sep">/</span>' + button : button;
        }).join('');
    }

    /* ---------- Vẽ ô tổng quan ---------- */

    function renderTiles(level, nodes) {
        canvas.className = 'wm-canvas';

        if (!nodes.length) {
            canvas.innerHTML = '<p class="wm-empty-note">Chưa có vị trí nào được khai báo ở cấp này.</p>';
            return;
        }

        var isWarehouse = level === 'warehouse';
        var iconClass = isWarehouse ? 'fa-warehouse' : 'fa-layer-group';

        var html = '<div class="wm-tiles">' + nodes.map(function (node) {
            var used = Number(node.used) || 0;
            var total = Number(node.total) || 0;
            var pct = pctOf(used, total);
            var bIdx = bucketOf(used, total);
            var bucket = BUCKETS[bIdx];
            var avail = Math.max(0, total - used);
            var statusText = bucket.label;

            return '<button type="button" class="wm-tile ' + bucket.cls + '"'
                + ' data-tile="' + esc(node.id) + '" data-level="' + level + '"'
                + ' data-name="' + esc(node.name) + '"'
                + ' data-tip="' + esc(node.name || node.code) + ' — ' + used.toLocaleString() + '/' + total.toLocaleString()
                + ' ô đã lưu (' + pct + '%)">'
                + '<div class="wm-tile-top">'
                +   '<div class="wm-tile-code-wrap">'
                +     '<span class="wm-tile-icon"><i class="fas ' + iconClass + '"></i></span>'
                +     '<span class="wm-tile-code">' + esc(node.code) + '</span>'
                +   '</div>'
                +   '<span class="wm-tile-badge wm-badge-b' + bIdx + '">' + pct + '% ' + esc(statusText) + '</span>'
                + '</div>'
                + '<div class="wm-tile-name" title="' + esc(node.name) + '">' + esc(node.name) + '</div>'
                + '<div class="wm-tile-progress-wrap">'
                +   '<div class="wm-tile-progress-bar wm-bar-b' + bIdx + '" style="width:' + Math.min(100, Math.max(pct, pct > 0 ? 3 : 0)) + '%"></div>'
                + '</div>'
                + '<div class="wm-tile-footer">'
                +   '<div class="wm-tile-stat">'
                +     '<span class="wm-tile-used"><strong>' + used.toLocaleString() + '</strong>/' + total.toLocaleString() + ' ô</span>'
                +     (avail > 0 ? '<span class="wm-tile-avail">Trống ' + avail.toLocaleString() + '</span>' : '<span class="wm-tile-avail is-full">Đầy</span>')
                +   '</div>'
                +   '<span class="wm-tile-arrow">Xem sơ đồ <i class="fas fa-chevron-right ml-1"></i></span>'
                + '</div>'
                + '</button>';
        }).join('') + '</div>';

        canvas.innerHTML = html;
        renderLegend('tiles');
    }

    /* ---------- Vẽ lưới chi tiết ---------- */

    function orderCells(cells) {
        var ordered = [];
        var loose = [];

        cells.forEach(function (cell) {
            if (cell.pos) ordered[cell.pos - 1] = cell;
            else loose.push(cell);
        });

        var cursor = 0;
        loose.forEach(function (cell) {
            while (ordered[cursor] !== undefined) cursor++;
            ordered[cursor] = cell;
        });

        return ordered;
    }

    function cellMarkup(cell, column, showText) {
        if (!cell) {
            return '<button type="button" class="wm-cell wm-cell--none" disabled'
                + ' aria-label="Cột ' + column + ': chưa tạo vị trí"></button>';
        }

        var status = cell.off ? 'Ngưng sử dụng' : (cell.busy ? 'Đã lưu' : 'Trống');
        var cls = cell.off ? 'wm-cell--off' : (cell.busy ? 'wm-cell--busy' : 'wm-cell--empty');
        if (cell.stale) cls += ' wm-cell--stale';
        var tip = '<b>' + esc(cell.code) + '</b><br>' + status;
        if (cell.doc) tip += '<br>Tài liệu: ' + esc(cell.doc);
        if (cell.stale) tip += '<br>⚠ Cần in lại nhãn';

        // Ô có hồ sơ kéo được kể cả khi ô đang ngưng sử dụng, để còn dọn hồ sơ ra.
        var dragAttrs = cell.busy && cell.did
            ? ' data-doc="' + cell.did + '" data-from="' + cell.id + '"'
              + ' data-from-code="' + esc(cell.code) + '" data-doc-code="' + esc(cell.doc || '') + '"'
            : '';

        return '<button type="button" class="wm-cell ' + cls + '" data-cell="' + cell.id + '"'
            + ' data-code="' + esc(cell.code) + '"' + dragAttrs
            + ' data-tip="' + esc(tip) + '" aria-label="' + esc(cell.code) + ' — ' + status + '">'
            + (showText ? esc(shortLabel(cell.code)) : '') + '</button>';
    }

    function renderShelfBlock(shelf, tiers, cellsByTier, dense, available) {
        var columns = 1;
        var used = 0;
        var total = 0;
        var maxPosition = 0;

        tiers.forEach(function (tier) {
            var cells = cellsByTier.get(Number(tier.id)) || [];
            columns = Math.max(columns, Number(tier.max_locations) || 0, cells.length);
            maxPosition = Math.max(maxPosition, Number(tier.position) || 0);
            cells.forEach(function (cell) {
                total++;
                if (cell.busy) used++;
            });
        });

        var rowCount = Math.max(Number(shelf.max_tiers) || 0, tiers.length, maxPosition);
        var rows = [];

        for (var position = 1; position <= rowCount; position++) {
            var match = null;
            for (var i = 0; i < tiers.length; i++) {
                if (Number(tiers[i].position) === position) { match = tiers[i]; break; }
            }
            rows.push({ position: position, tier: match });
        }

        tiers.forEach(function (tier) {
            if (!Number(tier.position)) rows.push({ position: null, tier: tier });
        });

        rows.reverse(); // tầng cao nhất nằm trên, giống kệ ngoài đời

        // Kệ có thể rộng 50+ cột, nên ô tự co để cả kệ lọt khung thay vì phải cuộn ngang.
        var labelWidth = dense ? 70 : 84;
        var size;

        if (dense) {
            // Cỡ ô cố định để nhiều kệ xếp cạnh nhau được; kệ quá rộng thì co nhẹ,
            // dưới 24px thì giữ nguyên và cho cuộn ngang để ô vẫn dễ nhìn.
            size = 34;
            if (labelWidth + columns * (size + 2) > available) {
                size = Math.max(24, Math.floor((available - labelWidth) / columns) - 2);
            }
        } else {
            size = Math.max(16, Math.min(Math.floor((available - labelWidth) / columns) - 2, 56));
        }

        // Dưới 20px, số vị trí bị chật, nên chỉ hiện chữ khi ô đủ rộng.
        var showText = size >= 20;

        var body = rows.map(function (row) {
            var label = row.tier ? (row.tier.name || row.tier.code) : ('Tầng ' + row.position + ' (chưa tạo)');
            var cells = row.tier ? orderCells(cellsByTier.get(Number(row.tier.id)) || []) : [];
            var markup = '';

            for (var column = 1; column <= columns; column++) {
                markup += cellMarkup(cells[column - 1], column, showText);
            }

            return '<div class="wm-row">'
                + '<span class="wm-row-label" title="' + esc(label) + '">' + esc(label) + '</span>'
                + '<div class="wm-cols" style="--cols:' + columns + '">' + markup + '</div>'
                + '</div>';
        }).join('');

        var header = '<div class="wm-shelf-head">'
            + '<button type="button" class="wm-shelf-title" data-shelf="' + esc(shelf.id) + '"'
            + ' data-name="' + esc(shelf.name) + '">' + esc(shelf.code) + ' — ' + esc(shelf.name) + '</button>'
            + '<span class="wm-shelf-stat">' + used + '/' + total + ' ô đã lưu · ' + pctOf(used, total) + '%</span>'
            + '</div>';

        var colHead = '';
        if (!dense) {
            // Ô hẹp thì số cột chồng lên nhau, nên chỉ ghi mốc mỗi 5 cột.
            var every = size >= 18 ? 1 : 5;
            var heads = '';
            for (var c = 1; c <= columns; c++) {
                heads += '<span class="wm-colhead">' + (c % every === 0 || every === 1 ? c : '') + '</span>';
            }
            colHead = '<div class="wm-row"><span class="wm-row-label"></span>'
                + '<div class="wm-cols" style="--cols:' + columns + '">' + heads + '</div></div>';
        }

        return '<section class="wm-shelf" style="--wm-cell:' + size + 'px">' + header
            + '<div class="wm-scroll"><div class="wm-grid">' + colHead + body + '</div></div>'
            + '</section>';
    }

    function renderGrid(data) {
        if (data.too_many) {
            // Quá ngưỡng vẽ: lùi về danh sách kệ để người dùng chọn một kệ.
            var alertHtml = '<div class="wm-alert mb-3">Kho này có <b>' + data.total
                + '</b> vị trí, vượt mức vẽ cùng lúc (' + data.limit + '). Hãy chọn một kệ bên dưới.</div>';
            fetchJson(ROUTES.summary, { warehouse_id: state.warehouseId })
                .then(function (summary) {
                    renderTiles(summary.level, summary.nodes);
                    canvas.insertAdjacentHTML('afterbegin', alertHtml);
                })
                .catch(failed);
            return;
        }

        var dense = data.shelves.length > 1;
        canvas.className = 'wm-canvas' + (dense ? ' wm-dense' : '');

        if (!data.shelves.length) {
            canvas.innerHTML = '<p class="wm-empty-note">Chưa có vị trí nào trong phạm vi này.</p>';
            renderLegend('grid');
            return;
        }

        var tiersByShelf = groupBy(data.tiers, function (tier) { return Number(tier.shelf_id); });
        var cellsByTier = groupBy(data.cells, function (cell) { return Number(cell.tier); });

        var available = Math.max(canvas.clientWidth, 320);

        canvas.innerHTML = data.shelves.map(function (shelf) {
            var tiers = (tiersByShelf.get(Number(shelf.id)) || []).slice().sort(function (a, b) {
                return (Number(a.position) || 0) - (Number(b.position) || 0);
            });
            return renderShelfBlock(shelf, tiers, cellsByTier, dense, available);
        }).join('');

        renderLegend('grid');

        if (data.lightweight) {
            canvas.insertAdjacentHTML('afterbegin',
                '<p class="wm-empty-note wm-note mb-2">Đang xem ở mức thu nhỏ — di chuột vào ô để xem mã, bấm vào ô để xem tài liệu.</p>');
        }

        if (state.pendingCell) {
            highlightCell(state.pendingCell, !state.pendingQuiet);
            loadCell(state.pendingCell);
            state.pendingCell = null;
            state.pendingQuiet = false;
        }
    }

    /* ---------- Cửa sổ chi tiết nổi, kéo được ---------- */

    var POSITION_KEY = 'wm-float-position';

    function keepInView() {
        var rect = floatBox.getBoundingClientRect();
        var left = Math.min(Math.max(rect.left, 8), Math.max(8, window.innerWidth - rect.width - 8));
        // Chỉ cần giữ thanh tiêu đề trong màn hình để còn kéo lại được.
        var top = Math.min(Math.max(rect.top, 8), window.innerHeight - 48);
        floatBox.style.left = left + 'px';
        floatBox.style.top = top + 'px';
        floatBox.style.right = 'auto';
    }

    function openFloat() {
        if (floatBox.hidden) {
            floatBox.hidden = false;
            try {
                var saved = JSON.parse(localStorage.getItem(POSITION_KEY) || 'null');
                if (saved) {
                    floatBox.style.left = saved.left + 'px';
                    floatBox.style.top = saved.top + 'px';
                    floatBox.style.right = 'auto';
                }
            } catch (e) { /* trình duyệt chặn storage thì dùng vị trí mặc định */ }
            keepInView();
        }
    }

    function setMinimized(minimized) {
        floatBox.classList.toggle('is-min', minimized);
        var icon = floatMin.querySelector('i');
        icon.className = minimized ? 'fas fa-window-restore' : 'fas fa-window-minimize';
        floatMin.title = minimized ? 'Mở rộng' : 'Thu nhỏ';
        floatMin.setAttribute('aria-label', floatMin.title);
        keepInView();
    }

    function resetPanel() {
        floatBox.hidden = true;
        floatCode.textContent = '';
        panel.innerHTML = '';
        var selected = canvas.querySelector('.wm-cell--selected');
        if (selected) selected.classList.remove('wm-cell--selected');
        state.selectedCell = null;
    }

    var drag = null;

    floatHead.addEventListener('pointerdown', function (event) {
        if (event.target.closest('.wm-float-btn')) return;
        var rect = floatBox.getBoundingClientRect();
        drag = { dx: event.clientX - rect.left, dy: event.clientY - rect.top };
        floatHead.setPointerCapture(event.pointerId);
        floatHead.classList.add('is-dragging');
    });

    floatHead.addEventListener('pointermove', function (event) {
        if (!drag) return;
        floatBox.style.left = (event.clientX - drag.dx) + 'px';
        floatBox.style.top = (event.clientY - drag.dy) + 'px';
        floatBox.style.right = 'auto';
        keepInView();
    });

    function endDrag() {
        if (!drag) return;
        drag = null;
        floatHead.classList.remove('is-dragging');
        try {
            var rect = floatBox.getBoundingClientRect();
            localStorage.setItem(POSITION_KEY, JSON.stringify({ left: Math.round(rect.left), top: Math.round(rect.top) }));
        } catch (e) { /* không lưu được vị trí cũng không sao */ }
    }

    floatHead.addEventListener('pointerup', endDrag);
    floatHead.addEventListener('pointercancel', endDrag);
    floatClose.addEventListener('click', resetPanel);
    floatMin.addEventListener('click', function () {
        setMinimized(!floatBox.classList.contains('is-min'));
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !floatBox.hidden) resetPanel();
    });

    window.addEventListener('resize', function () {
        if (!floatBox.hidden) keepInView();
    });

    function renderPanel(data) {
        var location = data.location;
        floatCode.textContent = '· ' + location.code;
        var path =[location.warehouse_name, location.shelf_name, location.tier_name]
            .filter(Boolean).map(esc).join(' › ');

        var head = '<h6>' + esc(location.code) + '</h6>'
            + '<div class="wm-panel-path">' + path + '</div>';

        if (Number(location.status_id) !== 1) {
            head += '<span class="badge badge-secondary mb-2">Ngưng sử dụng</span> ';
        }

        if (!data.documents.length) {
            panel.innerHTML = head
                + '<span class="badge badge-light border mb-2">Trống</span>'
                + '<p class="wm-empty-note mt-2">Vị trí này chưa lưu tài liệu nào.</p>';
            return;
        }

        var docs = data.documents.map(function (doc) {
            var rows = '';
            if (doc.owner) rows += '<dt>Người giữ</dt><dd>' + esc(doc.owner) + '</dd>';
            if (doc.expired_date) rows += '<dt>Hết hạn</dt><dd>' + esc(doc.expired_date) + '</dd>';
            if (doc.status_name) rows += '<dt>Trạng thái</dt><dd>' + esc(doc.status_name) + '</dd>';
            if (doc.created_by) rows += '<dt>Người lưu</dt><dd>' + esc(doc.created_by) + '</dd>';

            var staleNote = Number(doc.stale)
                ? '<div class="wm-stale-note"><i class="fas fa-exclamation-triangle mr-1"></i>Nhãn đang in vị trí '
                  + '<b>' + esc(doc.labeled_location_code || '-') + '</b>. '
                  + '<a href="' + labelUrl(doc.id) + '" target="_blank" rel="noopener">In lại nhãn</a></div>'
                : '';

            return '<div class="wm-doc" data-doc="' + esc(doc.id) + '"'
                + ' data-from="' + esc(location.id) + '" data-from-code="' + esc(location.code) + '"'
                + ' data-doc-code="' + esc(doc.code) + '">'
                + '<div><b>' + esc(doc.code) + '</b></div>'
                + '<div>' + esc(doc.name) + (doc.restricted ? ' <span class="badge badge-warning">Riêng tư</span>' : '') + '</div>'
                + '<dl class="mb-0">' + rows + '</dl>'
                + staleNote
                + '<div class="wm-doc-hint"><i class="fas fa-arrows-alt mr-1"></i>Kéo thẻ này thả vào ô trống để đổi vị trí</div>'
                + '</div>';
        }).join('');

        panel.innerHTML = head + '<span class="badge badge-primary mb-1">Đã lưu</span>' + docs;
    }

    function loadCell(locationId) {
        openFloat();
        // Bấm một ô mới nghĩa là muốn xem nội dung, nên mở rộng lại nếu đang thu nhỏ.
        if (floatBox.classList.contains('is-min')) setMinimized(false);
        panel.innerHTML = '<p class="wm-empty-note">Đang tải...</p>';
        fetchJson(ROUTES.cell, { location_id: locationId })
            .then(renderPanel)
            .catch(function () {
                panel.innerHTML = '<p class="wm-empty-note text-danger">Không tải được thông tin vị trí.</p>';
            });
    }

    function highlightCell(locationId, scroll) {
        var previous = canvas.querySelector('.wm-cell--selected');
        if (previous) previous.classList.remove('wm-cell--selected');

        var target = canvas.querySelector('.wm-cell[data-cell="' + locationId + '"]');
        if (target) {
            target.classList.add('wm-cell--selected');
            // Chỉ cuộn khi nhảy tới từ ô tìm kiếm; bấm trực tiếp thì ô đã nằm trong tầm mắt.
            if (scroll) target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        state.selectedCell = locationId;
    }

    /* ---------- Điều hướng ---------- */

    // refresh: vẽ lại tại chỗ sau khi chuyển hồ sơ, không xoá trắng khung để trang không nhảy cuộn.
    function load(refresh) {
        renderCrumbs();
        if (!refresh) {
            canvas.innerHTML = '<p class="wm-empty-note">Đang tải sơ đồ...</p>';
        }

        var wantsGrid = state.shelfId || state.warehouseId;

        if (wantsGrid) {
            fetchJson(ROUTES.grid, {
                shelf_id: state.shelfId,
                warehouse_id: state.shelfId ? null : state.warehouseId
            }).then(renderGrid).catch(failed);
            return;
        }

        fetchJson(ROUTES.summary, { warehouse_id: state.warehouseId })
            .then(function (data) { renderTiles(data.level, data.nodes); })
            .catch(failed);
    }

    function failed() {
        canvas.className = 'wm-canvas';
        canvas.innerHTML = '<p class="wm-empty-note text-danger">Không tải được dữ liệu sơ đồ.</p>';
    }

    function goRoot() {
        state.warehouseId = null;
        state.warehouseName = '';
        state.shelfId = null;
        state.shelfName = '';
        resetPanel();
        load();
    }

    function goWarehouse(id, name) {
        state.warehouseId = id;
        state.warehouseName = name;
        state.shelfId = null;
        state.shelfName = '';
        resetPanel();
        load();
    }

    function goShelf(id, name) {
        state.shelfId = id;
        state.shelfName = name;
        resetPanel();
        load();
    }

    /* ---------- Sự kiện ---------- */

    canvas.addEventListener('click', function (event) {
        var tile = event.target.closest('.wm-tile');
        if (tile) {
            if (tile.dataset.level === 'warehouse') goWarehouse(tile.dataset.tile, tile.dataset.name);
            else goShelf(tile.dataset.tile, tile.dataset.name);
            return;
        }

        var title = event.target.closest('.wm-shelf-title');
        if (title) {
            goShelf(title.dataset.shelf, title.dataset.name);
            return;
        }

        var cell = event.target.closest('.wm-cell[data-cell]');
        if (cell) {
            highlightCell(cell.dataset.cell);
            loadCell(cell.dataset.cell);
        }
    });

    crumbs.addEventListener('click', function (event) {
        var crumb = event.target.closest('.wm-crumb');
        if (!crumb || crumb.hasAttribute('aria-current')) return;

        if (crumb.dataset.level === 'root') goRoot();
        else if (crumb.dataset.level === 'warehouse') goWarehouse(state.warehouseId, state.warehouseName);
    });

    canvas.addEventListener('mouseover', function (event) {
        var target = event.target.closest('[data-tip]');
        if (!target) return;
        tooltip.innerHTML = target.dataset.tip;
        tooltip.hidden = false;
    });

    canvas.addEventListener('mousemove', function (event) {
        if (tooltip.hidden) return;
        var x = event.clientX + 14;
        var y = event.clientY + 16;
        var box = tooltip.getBoundingClientRect();
        if (x + box.width > window.innerWidth - 8) x = event.clientX - box.width - 14;
        if (y + box.height > window.innerHeight - 8) y = event.clientY - box.height - 16;
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    });

    canvas.addEventListener('mouseout', function (event) {
        if (!event.target.closest('[data-tip]')) return;
        tooltip.hidden = true;
    });

    var searchTimer = null;
    var searchSeq = 0;

    // Đi thẳng tới ô đã tìm thấy, dùng chung cho bấm chọn và tự nhảy khi chỉ có 1 kết quả.
    function goToHit(hit) {
        searchResults.hidden = true;
        searchInput.value = '';

        state.warehouseId = hit.warehouse_id;
        state.warehouseName = hit.warehouse_name;
        state.pendingCell = hit.location_id;
        goShelf(hit.shelf_id, hit.shelf_name);
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        var keyword = searchInput.value.trim();
        var seq = ++searchSeq;

        if (keyword.length < 2) {
            searchResults.hidden = true;
            return;
        }

        // Gõ xong là thấy phản hồi ngay, khỏi băn khoăn app có đang chạy hay không.
        searchResults.innerHTML = '<div class="wm-hit-empty"><i class="fas fa-spinner fa-spin mr-1"></i>Đang tìm...</div>';
        searchResults.hidden = false;

        searchTimer = setTimeout(function () {
            fetchJson(ROUTES.locate, { q: keyword }).then(function (data) {
                if (seq !== searchSeq) return; // đã có tìm kiếm mới hơn, bỏ kết quả trễ này

                if (!data.matches.length) {
                    searchResults.innerHTML = '<div class="wm-hit-empty">Không tìm thấy.</div>';
                    searchResults.hidden = false;
                    return;
                }

                // Khớp đúng 1 hồ sơ thì nhảy thẳng vào sơ đồ luôn, khỏi phải bấm chọn lại.
                if (data.matches.length === 1) {
                    goToHit(data.matches[0]);
                    return;
                }

                searchResults.innerHTML = data.matches.map(function (hit) {
                    return '<button type="button" class="wm-hit"'
                        + ' data-location="' + esc(hit.location_id) + '"'
                        + ' data-warehouse="' + esc(hit.warehouse_id) + '"'
                        + ' data-warehouse-name="' + esc(hit.warehouse_name) + '"'
                        + ' data-shelf="' + esc(hit.shelf_id) + '"'
                        + ' data-shelf-name="' + esc(hit.shelf_name) + '">'
                        + '<strong>' + esc(hit.doc_code) + '</strong>'
                        + '<span>' + esc(hit.doc_name) + ' · ' + esc(hit.location_code) + '</span>'
                        + '</button>';
                }).join('');
                searchResults.hidden = false;
            }).catch(function () {
                if (seq !== searchSeq) return;
                searchResults.innerHTML = '<div class="wm-hit-empty text-danger">Không tìm được, thử lại.</div>';
                searchResults.hidden = false;
            });
        }, 250);
    });

    searchResults.addEventListener('click', function (event) {
        var hit = event.target.closest('.wm-hit');
        if (!hit) return;

        goToHit({
            location_id: hit.dataset.location,
            warehouse_id: hit.dataset.warehouse,
            warehouse_name: hit.dataset.warehouseName,
            shelf_id: hit.dataset.shelf,
            shelf_name: hit.dataset.shelfName
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.wm-search')) searchResults.hidden = true;
    });

    /* ---------- Gọi API ghi dữ liệu ---------- */

    function labelUrl(documentId) {
        return ROUTES.label + '?id=' + encodeURIComponent(documentId);
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': ROUTES.csrf
            },
            body: JSON.stringify(body)
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) {
                    var message = data.message
                        || (response.status === 419 ? 'Phiên làm việc hết hạn, vui lòng tải lại trang.' : 'Lỗi máy chủ (' + response.status + ').');
                    throw new Error(message);
                }
                return data;
            });
        });
    }

    /* ---------- Thông báo ---------- */

    var toastTimer = null;

    // actions: mảng { label, run } — cho phép vừa "In lại nhãn" vừa "Hoàn tác" cùng lúc.
    function showToast(html, options) {
        options = options || {};
        var actions = options.actions || (options.action ? [options.action] : []);
        clearTimeout(toastTimer);
        toast.className = 'wm-toast' + (options.error ? ' wm-toast--error' : '');
        toast.innerHTML = '<span>' + html + '</span>' + actions.map(function (_, i) {
            return '<button type="button" data-toast-action="' + i + '">' + esc(actions[i].label) + '</button>';
        }).join('');
        toast.hidden = false;

        toast.querySelectorAll('[data-toast-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                toast.hidden = true;
                actions[Number(button.dataset.toastAction)].run();
            });
        });

        toastTimer = setTimeout(function () { toast.hidden = true; }, options.error ? 6000 : 9000);
    }

    /* ---------- Kéo thả hồ sơ sang ô trống ---------- */

    // Tự xử lý bằng pointer events thay vì HTML5 drag-and-drop: trên Chrome/Windows,
    // vòng kéo thả của hệ điều hành làm treo trang khi lưới có hàng nghìn ô.
    var DRAG_THRESHOLD = 6;
    var EDGE = 70;

    var press = null;      // nhấn chuột trên hồ sơ nhưng chưa kéo đủ xa
    var dragging = null;   // đang kéo thật
    var dropTarget = null;
    var suppressClick = false;
    var ghost = document.createElement('div');
    ghost.className = 'wm-ghost';
    ghost.hidden = true;
    document.body.appendChild(ghost);

    function setDropTarget(cell) {
        if (dropTarget === cell) return;
        if (dropTarget) dropTarget.classList.remove('wm-cell--drop');
        dropTarget = cell;
        if (dropTarget) dropTarget.classList.add('wm-cell--drop');
        ghost.classList.toggle('is-ok', !!cell);
    }

    function clearDrag() {
        setDropTarget(null);
        document.body.classList.remove('wm-is-dragging');
        if (dragging && dragging.source) dragging.source.classList.remove('wm-cell--dragging');
        ghost.hidden = true;
        dragging = null;
        press = null;
    }

    function emptyCellAt(x, y) {
        var element = document.elementFromPoint(x, y);
        // Chỉ ô trống đang sử dụng mới nhận hồ sơ; ô ngưng sử dụng mang class --off nên bị loại.
        return element ? element.closest('#wm-canvas .wm-cell--empty[data-cell]') : null;
    }

    function startDrag(event) {
        var source = press.source;
        dragging = {
            source: source,
            documentId: source.dataset.doc,
            fromId: source.dataset.from,
            fromCode: source.dataset.fromCode,
            docCode: source.dataset.docCode,
            x: event.clientX,
            y: event.clientY
        };
        press = null;

        tooltip.hidden = true;
        ghost.innerHTML = '<i class="fas fa-file-alt mr-1"></i>'
            + esc(dragging.docCode || dragging.fromCode) + '<small>Thả vào ô trống</small>';
        ghost.hidden = false;
        document.body.classList.add('wm-is-dragging');
        if (source.classList.contains('wm-cell')) source.classList.add('wm-cell--dragging');
        requestAnimationFrame(autoScroll);
    }

    function trackDrag() {
        ghost.style.transform = 'translate(' + (dragging.x + 14) + 'px,' + (dragging.y + 14) + 'px)';
        setDropTarget(emptyCellAt(dragging.x, dragging.y));
    }

    // Kho cao hơn màn hình nên kéo sát mép trên/dưới thì trang tự cuộn theo.
    function autoScroll() {
        if (!dragging) return;
        var speed = 0;
        if (dragging.y < EDGE) speed = -Math.ceil((EDGE - dragging.y) / 4);
        else if (dragging.y > window.innerHeight - EDGE) speed = Math.ceil((dragging.y - (window.innerHeight - EDGE)) / 4);
        if (speed) {
            window.scrollBy(0, speed);
            trackDrag();
        }
        requestAnimationFrame(autoScroll);
    }

    document.addEventListener('pointerdown', function (event) {
        if (event.button !== 0 || !event.isPrimary) return;
        var source = event.target.closest('[data-doc]');
        if (!source) return;
        press = { source: source, x: event.clientX, y: event.clientY };
    });

    window.addEventListener('pointermove', function (event) {
        if (press) {
            if (Math.abs(event.clientX - press.x) + Math.abs(event.clientY - press.y) < DRAG_THRESHOLD) return;
            startDrag(event);
        }
        if (!dragging) return;

        // Nhả chuột ở ngoài cửa sổ trình duyệt thì không có pointerup, nên huỷ khi thấy nút đã nhả.
        if (event.buttons === 0) {
            clearDrag();
            return;
        }

        event.preventDefault();
        dragging.x = event.clientX;
        dragging.y = event.clientY;
        trackDrag();
    }, { passive: false });

    window.addEventListener('pointerup', function (event) {
        press = null;
        if (!dragging) return;

        var move = dragging;
        var cell = emptyCellAt(event.clientX, event.clientY);
        clearDrag();

        // Thả xong trình duyệt vẫn bắn click lên phần tử chung, không được để nó mở chi tiết ô khác.
        suppressClick = true;
        setTimeout(function () { suppressClick = false; }, 0);

        if (cell) submitMove(move, cell.dataset.cell, cell.dataset.code);
    });

    window.addEventListener('pointercancel', clearDrag);
    window.addEventListener('blur', function () { if (dragging) clearDrag(); });

    document.addEventListener('click', function (event) {
        if (!suppressClick) return;
        suppressClick = false;
        event.stopPropagation();
        event.preventDefault();
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && dragging) {
            event.stopImmediatePropagation();
            clearDrag();
        }
    }, true);

    // Không hỏi xác nhận để thao tác nhanh — bù lại bằng nút "Hoàn tác" ngay trên thông báo.
    function submitMove(move, toId, toCode, isUndo) {
        showToast('<i class="fas fa-spinner fa-spin mr-1"></i>Đang chuyển ' + esc(move.docCode || move.fromCode) + '...');

        postJson(ROUTES.move, { document_id: move.documentId, to_location_id: toId })
            .then(function (result) {
                setRelabelCount(result.relabel_count);

                var message = (isUndo ? 'Đã hoàn tác — ' : 'Đã chuyển ') + '<b>' + esc(result.document.code) + '</b>: '
                    + esc(result.from.code) + ' → ' + esc(result.to.code);
                if (result.stale) message += ' · cần in lại nhãn';

                var actions = [];
                if (!isUndo) {
                    // Hoàn tác lại chính chiều vừa đi: đưa hồ sơ về đúng ô cũ.
                    actions.push({
                        label: 'Hoàn tác',
                        run: function () {
                            submitMove({ documentId: result.document.id, docCode: result.document.code, fromCode: result.to.code }, result.from.id, result.from.code, true);
                        }
                    });
                }
                if (result.stale) {
                    actions.push({ label: 'In lại nhãn', run: function () { window.open(labelUrl(result.document.id), '_blank', 'noopener'); } });
                }
                showToast(message, { actions: actions });

                // Vẽ lại tại chỗ để cập nhật số liệu kệ, rồi mở chi tiết ô vừa nhận hồ sơ.
                state.pendingCell = String(result.to.id);
                state.pendingQuiet = true;
                load(true);
            })
            .catch(function (error) {
                showToast(esc(error.message), { error: true });
                load(true);
            });
    }

    /* ---------- Tab ---------- */

    function showTab(name) {
        document.querySelectorAll('.wm-tab').forEach(function (tab) {
            var active = tab.dataset.tab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        paneMap.hidden = name !== 'map';
        paneRelabel.hidden = name !== 'relabel';

        if (name === 'relabel') {
            resetPanel();
            loadRelabel();
        }
    }

    document.querySelector('.wm-tabs').addEventListener('click', function (event) {
        var tab = event.target.closest('.wm-tab');
        if (tab) showTab(tab.dataset.tab);
    });

    /* ---------- Nhãn cần in lại ---------- */

    function setRelabelCount(count) {
        count = Number(count) || 0;
        relabelCount.textContent = count > 999 ? '999+' : count;
        relabelCount.hidden = count === 0;
    }

    function formatTime(value) {
        if (!value) return '-';
        var match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return match ? match[3] + '/' + match[2] + '/' + match[1] + ' ' + match[4] + ':' + match[5] : esc(value);
    }

    function loadRelabel() {
        if (!paneRelabel.hidden) {
            relabelSummary.textContent = 'Đang tải...';
        }

        return fetchJson(ROUTES.relabel).then(function (data) {
            setRelabelCount(data.total);
            if (paneRelabel.hidden) return;

            relabelSummary.textContent = data.total
                ? data.total + ' hồ sơ cần in lại nhãn' + (data.total > data.rows.length ? ' (hiển thị ' + data.rows.length + ' gần nhất)' : '') + '.'
                : '';

            if (!data.rows.length) {
                relabelBody.innerHTML = '<tr><td colspan="8" class="text-center wm-empty-note py-4">'
                    + '<i class="fas fa-check-circle mr-1"></i>Không có nhãn nào cần in lại.</td></tr>';
                return;
            }

            relabelBody.innerHTML = data.rows.map(function (row, index) {
                return '<tr>'
                    + '<td>' + (index + 1) + '</td>'
                    + '<td><b>' + esc(row.code) + '</b></td>'
                    + '<td>' + esc(row.name) + '</td>'
                    + '<td class="wm-old">' + esc(row.old_location || '-') + '</td>'
                    + '<td class="wm-new">' + esc(row.new_location || '-') + '</td>'
                    + '<td>' + esc(row.moved_by || '-') + '</td>'
                    + '<td class="text-nowrap">' + formatTime(row.moved_at) + '</td>'
                    + '<td class="wm-actions">'
                    + '<button type="button" class="btn btn-sm btn-outline-secondary mr-1" data-goto="' + esc(row.new_location_id) + '"'
                    + ' data-warehouse="' + esc(row.warehouse_id) + '" data-warehouse-name="' + esc(row.warehouse_name) + '"'
                    + ' data-shelf="' + esc(row.shelf_id) + '" data-shelf-name="' + esc(row.shelf_name) + '">'
                    + '<i class="fas fa-map-marker-alt mr-1"></i>Xem</button>'
                    + '<a class="btn btn-sm btn-primary" href="' + labelUrl(row.id) + '" target="_blank" rel="noopener">'
                    + '<i class="fas fa-print mr-1"></i>In nhãn</a>'
                    + '</td>'
                    + '</tr>';
            }).join('');
        }).catch(function () {
            if (!paneRelabel.hidden) relabelSummary.textContent = 'Không tải được danh sách nhãn cần in lại.';
        });
    }

    document.getElementById('wm-relabel-refresh').addEventListener('click', loadRelabel);

    relabelBody.addEventListener('click', function (event) {
        var button = event.target.closest('[data-goto]');
        if (!button) return;

        showTab('map');
        state.warehouseId = button.dataset.warehouse;
        state.warehouseName = button.dataset.warehouseName;
        state.pendingCell = button.dataset.goto;
        goShelf(button.dataset.shelf, button.dataset.shelfName);
    });

    // In nhãn ở tab khác xong quay lại thì danh sách tự cập nhật.
    window.addEventListener('focus', loadRelabel);

    resetPanel();
    load();
    loadRelabel();
})();
</script>
@endverbatim
