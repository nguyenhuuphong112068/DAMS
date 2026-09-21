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
                <div class="wm-toolbar-right">
                <div class="wm-history" role="group" aria-label="Lịch sử thao tác">
                    <button type="button" id="wm-undo" class="btn btn-sm btn-outline-secondary" disabled title="Chưa có thao tác để hoàn tác (Ctrl+Z)">
                        <i class="fas fa-undo mr-1"></i>Hoàn tác <span id="wm-undo-count" class="wm-history-count"></span><kbd class="wm-kbd">Ctrl+Z</kbd>
                    </button>
                    <button type="button" id="wm-redo" class="btn btn-sm btn-outline-secondary" disabled title="Chưa có thao tác để làm lại (Ctrl+Y)">
                        <i class="fas fa-redo mr-1"></i>Làm lại <span id="wm-redo-count" class="wm-history-count"></span><kbd class="wm-kbd">Ctrl+Y</kbd>
                    </button>
                </div>
                <div class="wm-search">
                    <input id="wm-search" type="search" class="form-control form-control-sm"
                        placeholder="Tìm mã tài liệu / mã vị trí..." autocomplete="off">
                    <div id="wm-search-results" class="wm-search-results" hidden></div>
                </div>
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
                <div class="wm-relabel-tools">
                    <button type="button" id="wm-binder-print" class="btn btn-sm btn-secondary" disabled
                        title="Tick chọn hồ sơ ở cột đầu, rồi bấm để in nhãn gáy binder cùng lúc">
                        <i class="fas fa-tags mr-1"></i> In nhãn binder (<span id="wm-binder-count">0</span>)
                    </button>
                    <button type="button" id="wm-binder-clear" class="btn btn-sm btn-outline-secondary" title="Bỏ chọn tất cả" hidden>
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" id="wm-relabel-refresh" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-sync-alt mr-1"></i> Làm mới
                    </button>
                </div>
            </div>
            <div class="wm-table-wrap">
                <table class="table table-sm table-hover wm-relabel-table mb-0">
                    <thead>
                        <tr>
                            <th class="wm-check">
                                <input type="checkbox" id="wm-binder-all" title="Chọn / bỏ chọn tất cả để in nhãn binder">
                            </th>
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

    {{-- Thanh thao tác cho các ô đang chọn (Shift + kéo / Shift + bấm) --}}
    <div id="wm-selbar" class="wm-selbar" role="toolbar" aria-label="Thao tác trên các ô đã chọn" hidden>
        <span id="wm-selbar-text" class="wm-selbar-text"></span>
        <button type="button" class="btn btn-sm btn-light" data-sel="edit"><i class="fas fa-pen mr-1"></i>Sửa</button>
        <button type="button" class="btn btn-sm btn-danger" data-sel="dispose"><i class="fas fa-trash-alt mr-1"></i>Huỷ <span></span></button>
        <button type="button" class="wm-selbar-close" data-sel="clear" title="Bỏ chọn (Esc)" aria-label="Bỏ chọn"><i class="fas fa-times"></i></button>
    </div>

    {{-- Hộp thoại tạo / sửa / huỷ hồ sơ --}}
    <div id="wm-modal" class="wm-modal" hidden>
        <form id="wm-form" class="wm-modal-box" role="dialog" aria-modal="true" aria-labelledby="wm-modal-title" novalidate>
            <header class="wm-modal-head">
                <span id="wm-modal-title"></span>
                <button type="button" class="wm-float-btn" data-modal-close aria-label="Đóng"><i class="fas fa-times"></i></button>
            </header>
            <div class="wm-modal-body">
                <div id="wm-modal-targets" class="wm-modal-targets"></div>

                <div data-part="fields">
                    <div class="form-group" data-field="name">
                        <label class="wm-label">Tên hồ sơ <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" maxlength="255" autocomplete="off">
                    </div>
                    <div class="form-group" data-field="types">
                        <label class="wm-label">Loại tài liệu</label>
                        <div id="wm-types" class="wm-types"></div>
                    </div>
                    <div class="form-group" data-field="expired">
                        <label class="wm-label">Ngày hết hạn</label>
                        <input type="date" name="expired_date" class="form-control form-control-sm">
                        <small class="form-text text-muted">Để trống nếu hồ sơ không có hạn.</small>
                    </div>
                    <div class="form-group mb-0" data-field="file">
                        <label class="wm-label">Tệp đính kèm</label>
                        <input type="file" name="file_attachment" class="form-control-file form-control-sm">
                        <small id="wm-file-current" class="form-text"></small>
                    </div>
                </div>

                <div data-part="dispose">
                    <div class="wm-dispose-warn"><i class="fas fa-exclamation-triangle mr-1"></i>
                        Hồ sơ sẽ được chuyển vào lịch sử huỷ và vị trí trả về trống. Thao tác này không hoàn tác được.</div>
                    <div class="form-group mb-0">
                        <label class="wm-label">Lý do huỷ <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control form-control-sm" maxlength="500"></textarea>
                    </div>
                </div>

                <div id="wm-modal-error" class="wm-modal-error" hidden></div>
            </div>
            <footer class="wm-modal-foot">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-modal-close>Đóng</button>
                <button type="submit" id="wm-modal-submit" class="btn btn-sm btn-primary"></button>
            </footer>
        </form>
    </div>

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

/* Người quản lý kệ được phân công */
.wm-managers { display: flex; align-items: center; gap: .3rem; flex-wrap: wrap; font-size: .74rem; color: #7c4a03; }
.wm-managers i { color: #b45309; }
.wm-manager-chip { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 999px; padding: 0 .45rem; white-space: nowrap; }
.wm-tile .wm-managers { margin-top: .55rem; }
.wm-panel-managers { margin: -.3rem 0 .6rem; }
.wm-lock-note { font-size: .72rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 5px; padding: .3rem .5rem; margin-bottom: .5rem; }
/* Góc xám đậm dưới trái: kệ do người khác phụ trách, không kéo thả được. */
.wm-cell--locked { cursor: not-allowed; }
.wm-cell--locked::before {
    content: ""; position: absolute; left: 0; bottom: 0; width: 0; height: 0;
    border-style: solid; border-width: 9px 0 0 9px; border-color: transparent transparent transparent #475569;
}
.wm-is-dragging .wm-cell--locked { opacity: .45; }
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
.wm-cell--drop-bad { outline: 3px solid #d92d20 !important; outline-offset: 1px; background: #fde8e6 !important; }
.wm-ghost.is-bad { background: #b42b2b; }

/* Shift + kéo: khung chọn nhiều ô, ô đã chọn viền vàng để không lẫn với ô đang xem chi tiết (viền cam). */
.wm-marquee {
    position: fixed; z-index: 1090; pointer-events: none; border: 1px solid #2a78d6; background: rgba(42, 120, 214, .14);
}
.wm-marquee[hidden] { display: none; }
.wm-is-selecting, .wm-is-selecting * { user-select: none !important; cursor: crosshair !important; }
.wm-cell--picked { outline: 3px solid #f5a300 !important; outline-offset: 1px; box-shadow: inset 0 0 0 2px #fff; }
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
.wm-relabel-table .wm-check { width: 1%; text-align: center; }
.wm-relabel-table .wm-check input { cursor: pointer; vertical-align: middle; }
.wm-relabel-tools { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }

/* ---------- Tạo / sửa / huỷ hồ sơ ---------- */
.wm-crud { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
.wm-selbar {
    position: fixed; z-index: 1085; left: 50%; bottom: 76px; transform: translateX(-50%);
    max-width: calc(100vw - 32px); display: flex; align-items: center; flex-wrap: wrap; gap: .4rem;
    background: #fff; border: 1px solid var(--wm-hairline, rgba(0,0,0,.12)); border-radius: 10px;
    padding: .45rem .5rem .45rem .85rem; box-shadow: 0 10px 28px rgba(0,0,0,.2); font-size: .85rem;
}
.wm-selbar[hidden] { display: none; }
.wm-selbar-text { margin-right: .35rem; color: #334155; }
.wm-selbar .btn[hidden] { display: none; }
.wm-selbar-close { background: none; border: 0; width: 28px; height: 28px; border-radius: 5px; color: #64748b; }
.wm-selbar-close:hover { background: #f1f5f9; }
.wm-is-dragging .wm-selbar, .wm-is-selecting .wm-selbar { opacity: .35; pointer-events: none; }

.wm-modal { position: fixed; inset: 0; z-index: 1110; background: rgba(15, 23, 42, .45); display: flex; align-items: flex-start; justify-content: center; padding: 8vh 16px 16px; overflow-y: auto; }
.wm-modal[hidden] { display: none; }
.wm-modal-box { background: #fff; border-radius: 10px; width: 480px; max-width: 100%; box-shadow: 0 20px 48px rgba(0,0,0,.3); display: flex; flex-direction: column; }
.wm-modal-head { display: flex; align-items: center; justify-content: space-between; padding: .6rem .6rem .6rem 1rem; background: #184f95; color: #fff; border-radius: 10px 10px 0 0; font-weight: 600; }
.wm-modal-head.is-danger { background: #b42b2b; }
.wm-modal-body { padding: 1rem; }
.wm-modal-foot { display: flex; justify-content: flex-end; gap: .4rem; padding: .65rem 1rem; border-top: 1px solid #e2e8f0; }
.wm-modal-targets { font-size: .8rem; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: .45rem .6rem; margin-bottom: .85rem; max-height: 84px; overflow-y: auto; }
.wm-modal-targets b { color: #0f172a; }
.wm-label { font-weight: 600; font-size: .82rem; margin-bottom: .25rem; display: flex; align-items: center; gap: .35rem; }
.wm-types { display: flex; flex-wrap: wrap; gap: .3rem .9rem; max-height: 140px; overflow-y: auto; font-size: .82rem; }
.wm-types label { display: inline-flex; align-items: center; gap: .3rem; margin: 0; font-weight: 400; }
.wm-dispose-warn { font-size: .8rem; color: #8a1c1c; background: #fdecec; border: 1px solid #f5c2c2; border-radius: 6px; padding: .45rem .6rem; margin-bottom: .75rem; }
.wm-modal-error { margin-top: .75rem; font-size: .82rem; color: #b42b2b; background: #fdecec; border-radius: 6px; padding: .4rem .6rem; }
.wm-kbd { font-size: .68rem; opacity: .7; margin-left: .25rem; font-family: inherit; }

.wm-toolbar-right { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.wm-history { display: inline-flex; gap: .3rem; }
.wm-history .btn { white-space: nowrap; }
.wm-history-count { font-size: .72rem; opacity: .75; }

@media (max-width: 991px) {
    .wm-search { width: 100%; }
    .wm-toolbar-right { width: 100%; }
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
    "moveBatch": "{{ route('pages.storageLocation.map.moveBatch') }}",
    "relabel": "{{ route('pages.storageLocation.map.relabel') }}",
    "label": "{{ route('pages.documentStorage.document.label') }}",
    "binderLabel": "{{ route('pages.documentStorage.document.binderLabel') }}",
    "document": "{{ route('pages.storageLocation.map.document') }}",
    "docCreate": "{{ route('pages.storageLocation.map.documents.create') }}",
    "docUpdate": "{{ route('pages.storageLocation.map.documents.update') }}",
    "docDispose": "{{ route('pages.storageLocation.map.documents.dispose') }}",
    "can": @json($can),
    "types": @json($documentTypes),
    "csrf": "{{ csrf_token() }}"
}
</script>

@verbatim
<script>
(function () {
    'use strict';

    var ROUTES = JSON.parse(document.getElementById('wm-config').textContent);
    var CAN = ROUTES.can || {};

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

    // Tên người quản lý kệ theo id, lấy từ lần tải lưới gần nhất.
    var gridManagers = {};

    function managersChips(list) {
        if (!list || !list.length) return '';
        return '<div class="wm-managers"><i class="fas fa-user-tag"></i>' + list.map(function (item) {
            return '<span class="wm-manager-chip">' + esc(item) + '</span>';
        }).join('') + '</div>';
    }

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

    function renderLegend(mode, hasLocked) {
        var items;

        if (mode === 'grid') {
            items = [
                { style: 'background:#fff;border-color:var(--wm-baseline)', label: 'Trống' },
                { style: 'background:var(--wm-f4);border-color:var(--wm-f5)', label: 'Đã lưu' },
                { style: 'background:repeating-linear-gradient(45deg,#f0efec,#f0efec 3px,#dcdbd6 3px,#dcdbd6 6px)', label: 'Ngưng sử dụng' },
                { style: 'background:transparent;border-style:dashed', label: 'Chưa tạo vị trí' },
                { style: 'background:linear-gradient(225deg,#eb6834 0 5px,var(--wm-f4) 5px);border-color:var(--wm-f5)', label: 'Cần in lại nhãn' }
            ];
            if (hasLocked) {
                items.push({ style: 'background:linear-gradient(45deg,#475569 0 5px,#fff 5px)', label: 'Kệ người khác phụ trách' });
            }
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
            ? '<span class="wm-drag-hint"><i class="fas fa-hand-paper mr-1"></i>Kéo ô đã lưu thả vào ô trống để đổi vị trí · '
              + '<b>Shift + kéo</b> chọn các ô đã lưu để dời hoặc huỷ cả nhóm · bấm ô trống để tạo hồ sơ</span>'
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
        clearPicks();
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
                + managersChips((node.managers || []).map(function (m) {
                    return m.name + ' · ' + Number(m.covered).toLocaleString() + ' ô';
                }))
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

    function cellMarkup(cell, column, showText, rowPos) {
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
        if (cell.own) {
            tip += '<br>Phụ trách: ' + esc(cell.own.map(function (id) { return gridManagers[id] || ('#' + id); }).join(', '));
        }
        if (cell.lock) {
            cls += ' wm-cell--locked';
            tip += '<br>🔒 Chỉ người phụ trách mới thao tác được';
        }

        // Ô có hồ sơ kéo được kể cả khi ô đang ngưng sử dụng, để còn dọn hồ sơ ra.
        var dragAttrs = cell.busy && cell.did && !cell.lock
            ? ' data-doc="' + cell.did + '" data-from="' + cell.id + '"'
              + ' data-from-code="' + esc(cell.code) + '" data-doc-code="' + esc(cell.doc || '') + '"'
            : '';

        return '<button type="button" class="wm-cell ' + cls + '" data-cell="' + cell.id + '"'
            + ' data-code="' + esc(cell.code) + '"' + dragAttrs
            + (rowPos ? ' data-shelf="' + cell.shelf + '" data-r="' + rowPos + '" data-c="' + column + '"' : '')
            + ' data-tip="' + esc(tip) + '" aria-label="' + esc(cell.code) + ' — ' + status + '">'
            + (showText ? esc(shortLabel(cell.code)) : '') + '</button>';
    }

    function renderShelfBlock(shelf, tiers, cellsByTier, dense, available) {
        var columns = 1;
        var used = 0;
        var total = 0;
        var maxPosition = 0;
        var ownCounts = {};

        tiers.forEach(function (tier) {
            var cells = cellsByTier.get(Number(tier.id)) || [];
            columns = Math.max(columns, Number(tier.max_locations) || 0, cells.length);
            maxPosition = Math.max(maxPosition, Number(tier.position) || 0);
            cells.forEach(function (cell) {
                total++;
                if (cell.busy) used++;
                (cell.own || []).forEach(function (id) { ownCounts[id] = (ownCounts[id] || 0) + 1; });
            });
        });

        var owners = Object.keys(ownCounts).map(function (id) {
            var name = gridManagers[id] || ('#' + id);
            return name + (ownCounts[id] === total ? ' (cả kệ)' : ' · ' + ownCounts[id] + ' ô');
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
                markup += cellMarkup(cells[column - 1], column, showText, row.position);
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
            + managersChips(owners)
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
        clearPicks();
        var pendingPick = state.pendingPick;
        state.pendingPick = null;
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

        gridManagers = data.managers || {};
        var hasLocked = data.cells.some(function (cell) { return cell.lock; });
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

        renderLegend('grid', hasLocked);

        if (data.lightweight) {
            canvas.insertAdjacentHTML('afterbegin',
                '<p class="wm-empty-note wm-note mb-2">Đang xem ở mức thu nhỏ — di chuột vào ô để xem mã, bấm vào ô để xem tài liệu.</p>');
        }

        // Sau khi dời nhóm, giữ nhóm ở vị trí mới để có thể dời tiếp.
        if (pendingPick) {
            pendingPick.forEach(function (id) {
                var el = canvas.querySelector('.wm-cell[data-cell="' + id + '"][data-doc]');
                if (el) setPicked(el, true);
            });
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

        if (data.managers && data.managers.length) {
            head += '<div class="wm-panel-managers">' + managersChips(data.managers.map(function (name) { return 'Phụ trách: ' + name; })) + '</div>';
        }
        if (!data.can_manage) {
            head += '<div class="wm-lock-note"><i class="fas fa-lock mr-1"></i>Kệ này do người khác phụ trách, bạn chỉ xem được.</div>';
        }

        if (Number(location.status_id) !== 1) {
            head += '<span class="badge badge-secondary mb-2">Ngưng sử dụng</span> ';
        }

        var active = Number(location.status_id) === 1;

        if (!data.documents.length) {
            var createButton = CAN.create && data.can_manage && active
                ? '<div class="wm-crud"><button type="button" class="btn btn-sm btn-success" data-crud="create"'
                  + ' data-location="' + esc(location.id) + '" data-code="' + esc(location.code) + '">'
                  + '<i class="fas fa-plus mr-1"></i>Tạo hồ sơ tại đây</button></div>'
                : '';
            panel.innerHTML = head
                + '<span class="badge badge-light border mb-2">Trống</span>'
                + '<p class="wm-empty-note mt-2">Vị trí này chưa lưu tài liệu nào.</p>'
                + createButton;
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

            // Không được thao tác thì bỏ data-doc để thẻ không kéo được.
            var dragAttrs = data.can_manage
                ? ' data-doc="' + esc(doc.id) + '" data-from="' + esc(location.id) + '"'
                  + ' data-from-code="' + esc(location.code) + '" data-doc-code="' + esc(doc.code) + '"'
                : '';

            return '<div class="wm-doc"' + dragAttrs + '>'
                + '<div><b>' + esc(doc.code) + '</b></div>'
                + '<div>' + esc(doc.name) + (doc.restricted ? ' <span class="badge badge-warning">Riêng tư</span>' : '') + '</div>'
                + '<dl class="mb-0">' + rows + '</dl>'
                + staleNote
                + (data.can_manage && (CAN.update || CAN.dispose)
                    ? '<div class="wm-crud">'
                      + (CAN.update && !doc.restricted
                          ? '<button type="button" class="btn btn-sm btn-outline-primary" data-crud="edit" data-doc-id="' + esc(doc.id) + '"'
                            + ' data-code="' + esc(doc.code) + '"><i class="fas fa-pen mr-1"></i>Sửa</button>'
                          : '')
                      + (CAN.dispose
                          ? '<button type="button" class="btn btn-sm btn-outline-danger" data-crud="dispose" data-doc-id="' + esc(doc.id) + '"'
                            + ' data-code="' + esc(doc.code) + '"><i class="fas fa-trash-alt mr-1"></i>Huỷ hồ sơ</button>'
                          : '')
                      + '</div>'
                    : '')
                + (data.can_manage ? '<div class="wm-doc-hint"><i class="fas fa-arrows-alt mr-1"></i>Kéo thẻ này thả vào ô trống để đổi vị trí</div>' : '')
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

    /* Chọn nhiều ô: Shift + kéo vẽ khung chọn, Shift + bấm bật/tắt từng ô. Chỉ chọn trong một kệ để
       phép dời giữ nguyên hình dạng nhóm theo (tầng, cột) — mỗi ô đích là ô nguồn cộng cùng một độ lệch. */
    var picked = new Set();   // id vị trí (chuỗi) của các ô đã chọn
    var pickedShelf = null;
    var marquee = null;
    var groupMarks = [];      // ô đích đang được tô xem trước khi kéo nhóm
    var marqueeBox = document.createElement('div');
    marqueeBox.className = 'wm-marquee';
    marqueeBox.hidden = true;
    document.body.appendChild(marqueeBox);

    // Chỉ chọn ô đã lưu hồ sơ (để dời nhóm hoặc huỷ); ô người khác phụ trách không mang data-doc nên bị loại.
    var PICKABLE = '.wm-cell[data-doc][data-r]';

    function setPicked(el, on) {
        if (on) {
            picked.add(el.dataset.cell);
            pickedShelf = el.dataset.shelf;
        } else {
            picked.delete(el.dataset.cell);
            if (!picked.size) pickedShelf = null;
        }
        el.classList.toggle('wm-cell--picked', on);
        scheduleSelectionBar();
    }

    function clearPicks() {
        picked.clear();
        pickedShelf = null;
        canvas.querySelectorAll('.wm-cell--picked').forEach(function (el) { el.classList.remove('wm-cell--picked'); });
        scheduleSelectionBar();
    }

    function beginMarquee(event) {
        if (event.target.closest('.wm-shelf-title')) return;
        var shelf = event.target.closest('#wm-canvas .wm-shelf');
        if (!shelf) return;
        marquee = {
            shelf: shelf, x: event.clientX, y: event.clientY, cx: event.clientX, cy: event.clientY,
            moved: false, frame: 0, origin: event.target.closest(PICKABLE)
        };
    }

    function paintMarquee(m) {
        m.frame = 0;
        var left = Math.min(m.x, m.cx), right = Math.max(m.x, m.cx);
        var top = Math.min(m.y, m.cy), bottom = Math.max(m.y, m.cy);
        marqueeBox.style.left = left + 'px';
        marqueeBox.style.top = top + 'px';
        marqueeBox.style.width = (right - left) + 'px';
        marqueeBox.style.height = (bottom - top) + 'px';

        // Đọc toàn bộ toạ độ trước rồi mới đổi class, để trình duyệt chỉ tính layout một lần.
        var hits = [];
        m.shelf.querySelectorAll(PICKABLE).forEach(function (el) {
            var box = el.getBoundingClientRect();
            hits.push([el, box.right >= left && box.left <= right && box.bottom >= top && box.top <= bottom]);
        });
        hits.forEach(function (hit) {
            if (hit[0].classList.contains('wm-cell--picked') !== hit[1]) setPicked(hit[0], hit[1]);
        });
    }

    function moveMarquee(event) {
        marquee.cx = event.clientX;
        marquee.cy = event.clientY;
        if (!marquee.moved) {
            if (Math.abs(marquee.cx - marquee.x) + Math.abs(marquee.cy - marquee.y) < DRAG_THRESHOLD) return;
            marquee.moved = true;
            clearPicks();
            tooltip.hidden = true;
            marqueeBox.hidden = false;
            document.body.classList.add('wm-is-selecting');
        }
        if (!marquee.frame) marquee.frame = requestAnimationFrame(function () { if (marquee) paintMarquee(marquee); });
    }

    function stopMarquee() {
        if (!marquee) return null;
        var m = marquee;
        marquee = null;
        if (m.frame) cancelAnimationFrame(m.frame);
        marqueeBox.hidden = true;
        document.body.classList.remove('wm-is-selecting');
        return m;
    }

    function endMarquee(event) {
        if (marquee.moved) {
            marquee.cx = event.clientX;
            marquee.cy = event.clientY;
            paintMarquee(marquee);
        }
        var m = stopMarquee();

        if (!m.moved && m.origin) {
            // Shift + bấm không kéo: bật/tắt riêng ô đó (chuyển sang kệ khác thì bắt đầu chọn lại).
            if (picked.size && pickedShelf !== m.origin.dataset.shelf) clearPicks();
            setPicked(m.origin, !m.origin.classList.contains('wm-cell--picked'));
        }

        // Nhả chuột xong trình duyệt vẫn bắn click, không được để nó mở chi tiết ô.
        suppressClick = true;
        setTimeout(function () { suppressClick = false; }, 0);

    }

    function cancelMarquee() {
        if (!stopMarquee()) return;
        clearPicks();
    }

    // Toạ độ (kệ, tầng, cột) của mọi ô đang vẽ, dựng một lần cho mỗi lần kéo nhóm.
    function buildCellIndex() {
        var index = new Map();
        canvas.querySelectorAll('.wm-cell[data-cell][data-r]').forEach(function (el) {
            index.set(el.dataset.shelf + '|' + el.dataset.r + '|' + el.dataset.c, el);
        });
        return index;
    }

    // Tính nhóm sẽ rơi vào đâu nếu thả tại (x, y): ô đang cầm rơi vào ô dưới con trỏ, các ô còn lại lệch theo.
    function planGroup(group, x, y) {
        var element = document.elementFromPoint(x, y);
        var anchor = element ? element.closest('#wm-canvas .wm-cell[data-cell][data-r]') : null;
        if (!anchor) return null;

        var grabbed = group.source;
        var shelf = anchor.dataset.shelf;
        var dr = Number(anchor.dataset.r) - Number(grabbed.dataset.r);
        var dc = Number(anchor.dataset.c) - Number(grabbed.dataset.c);
        if (!dr && !dc && shelf === grabbed.dataset.shelf) return null;

        if (!group.index) group.index = buildCellIndex();

        var plan = { key: shelf + '|' + dr + '|' + dc, ok: true, reason: null, pairs: [], marks: [] };
        function reject(reason) {
            plan.ok = false;
            plan.reason = plan.reason || reason;
        }

        group.items.forEach(function (item) {
            var target = group.index.get(shelf + '|' + (item.r + dr) + '|' + (item.c + dc));
            if (!target) {
                reject('Nhóm bị lệch ra ngoài kệ hoặc rơi vào vị trí chưa được tạo.');
                return;
            }
            plan.marks.push(target);

            var cls = target.classList;
            if (cls.contains('wm-cell--off')) reject('Vị trí ' + target.dataset.code + ' đang ngưng sử dụng.');
            else if (cls.contains('wm-cell--locked')) reject('Vị trí ' + target.dataset.code + ' do người khác phụ trách.');
            else if (cls.contains('wm-cell--busy') && !cls.contains('wm-cell--picked')) reject('Vị trí ' + target.dataset.code + ' đã có hồ sơ khác.');

            plan.pairs.push({ documentId: item.docId, toId: target.dataset.cell });
        });

        return plan;
    }

    function setGroupMarks(plan) {
        groupMarks.forEach(function (el) { el.classList.remove('wm-cell--drop', 'wm-cell--drop-bad'); });
        groupMarks = plan ? plan.marks : [];
        groupMarks.forEach(function (el) { el.classList.add(plan.ok ? 'wm-cell--drop' : 'wm-cell--drop-bad'); });
        ghost.classList.toggle('is-ok', !!plan && plan.ok);
        ghost.classList.toggle('is-bad', !!plan && !plan.ok);
    }

    function trackGroup() {
        var plan = planGroup(dragging.group, dragging.x, dragging.y);
        var key = plan ? plan.key : null;
        if (key === dragging.groupKey) return;
        dragging.groupKey = key;
        setGroupMarks(plan);
    }

    function setDropTarget(cell) {
        if (dropTarget === cell) return;
        if (dropTarget) dropTarget.classList.remove('wm-cell--drop');
        dropTarget = cell;
        if (dropTarget) dropTarget.classList.add('wm-cell--drop');
        ghost.classList.toggle('is-ok', !!cell);
    }

    function clearDrag() {
        setDropTarget(null);
        setGroupMarks(null);
        document.body.classList.remove('wm-is-dragging');
        canvas.querySelectorAll('.wm-cell--dragging').forEach(function (el) { el.classList.remove('wm-cell--dragging'); });
        ghost.hidden = true;
        dragging = null;
        press = null;
    }

    function emptyCellAt(x, y) {
        var element = document.elementFromPoint(x, y);
        // Chỉ ô trống đang sử dụng mới nhận hồ sơ; ô ngưng sử dụng mang class --off nên bị loại.
        return element ? element.closest('#wm-canvas .wm-cell--empty[data-cell]:not(.wm-cell--locked)') : null;
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
            y: event.clientY,
            group: null,
            groupKey: null
        };
        press = null;

        // Cầm một ô đã chọn khi đang chọn nhiều ô thì kéo cả nhóm.
        var groupCells = source.classList.contains('wm-cell--picked')
            ? canvas.querySelectorAll('.wm-cell--picked[data-doc]')
            : [];
        if (groupCells.length > 1) {
            var items = [];
            groupCells.forEach(function (el) {
                items.push({ el: el, docId: el.dataset.doc, r: Number(el.dataset.r), c: Number(el.dataset.c) });
            });
            dragging.group = { source: source, items: items, index: null };
        }

        tooltip.hidden = true;
        ghost.innerHTML = dragging.group
            ? '<i class="fas fa-copy mr-1"></i>' + dragging.group.items.length + ' hồ sơ<small>Thả để dời cả nhóm</small>'
            : '<i class="fas fa-file-alt mr-1"></i>' + esc(dragging.docCode || dragging.fromCode) + '<small>Thả vào ô trống</small>';
        ghost.hidden = false;
        document.body.classList.add('wm-is-dragging');
        if (dragging.group) {
            dragging.group.items.forEach(function (item) { item.el.classList.add('wm-cell--dragging'); });
        } else if (source.classList.contains('wm-cell')) {
            source.classList.add('wm-cell--dragging');
        }
        requestAnimationFrame(autoScroll);
    }

    function trackDrag() {
        ghost.style.transform = 'translate(' + (dragging.x + 14) + 'px,' + (dragging.y + 14) + 'px)';
        if (dragging.group) trackGroup();
        else setDropTarget(emptyCellAt(dragging.x, dragging.y));
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
        if (event.shiftKey) {
            beginMarquee(event);
            return;
        }

        // Nút Sửa/Huỷ nằm trong thẻ hồ sơ kéo được; bấm nút thì không được tính là bắt đầu kéo.
        var source = event.target.closest('button[data-crud], a, input') ? null : event.target.closest('[data-doc]');
        // Bấm ra ngoài nhóm đã chọn thì bỏ chọn; bấm vào một ô của nhóm là chuẩn bị kéo cả nhóm.
        if (picked.size && event.target.closest('#wm-canvas') && !event.target.closest('.wm-cell--picked')) {
            clearPicks();
        }
        if (!source) return;
        press = { source: source, x: event.clientX, y: event.clientY };
    });

    // Giữ Shift rồi bấm sẽ làm trình duyệt bôi đen chữ giữa hai điểm bấm.
    canvas.addEventListener('mousedown', function (event) {
        if (event.shiftKey) event.preventDefault();
    });

    window.addEventListener('pointermove', function (event) {
        if (marquee) {
            // Nhả chuột ngoài cửa sổ thì không có pointerup, nên huỷ khi thấy nút đã nhả.
            if (event.buttons === 0) cancelMarquee();
            else moveMarquee(event);
            return;
        }
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
        if (marquee) {
            endMarquee(event);
            return;
        }
        if (!dragging) return;

        var move = dragging;
        var cell = move.group ? null : emptyCellAt(event.clientX, event.clientY);
        var plan = move.group ? planGroup(move.group, event.clientX, event.clientY) : null;
        clearDrag();

        // Thả xong trình duyệt vẫn bắn click lên phần tử chung, không được để nó mở chi tiết ô khác.
        suppressClick = true;
        setTimeout(function () { suppressClick = false; }, 0);

        if (cell) submitMove(move, cell.dataset.cell, cell.dataset.code);
        else if (plan && plan.ok) submitBatch(plan.pairs);
        else if (plan) showToast(esc(plan.reason), { error: true });
    });

    window.addEventListener('pointercancel', function () {
        cancelMarquee();
        clearDrag();
    });
    window.addEventListener('blur', function () {
        cancelMarquee();
        if (dragging) clearDrag();
    });

    document.addEventListener('click', function (event) {
        if (!suppressClick) return;
        suppressClick = false;
        event.stopPropagation();
        event.preventDefault();
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (!modal.hidden) {
            event.stopImmediatePropagation();
            closeModal();
        } else if (dragging) {
            event.stopImmediatePropagation();
            clearDrag();
        } else if (marquee) {
            event.stopImmediatePropagation();
            cancelMarquee();
        } else if (picked.size) {
            clearPicks();
        }
    }, true);

    // Không hỏi xác nhận để thao tác nhanh — bù lại bằng nút "Hoàn tác" ngay trên thông báo.
    function submitMove(move, toId, toCode) {
        showToast('<i class="fas fa-spinner fa-spin mr-1"></i>Đang chuyển ' + esc(move.docCode || move.fromCode) + '...');

        postJson(ROUTES.move, { document_id: move.documentId, to_location_id: toId })
            .then(function (result) {
                setRelabelCount(result.relabel_count);
                recordHistory([result]);

                var message = 'Đã chuyển <b>' + esc(result.document.code) + '</b>: '
                    + esc(result.from.code) + ' → ' + esc(result.to.code);
                if (result.stale) message += ' · cần in lại nhãn';

                var actions = [{ label: 'Hoàn tác', run: undo }];
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

    // Dời cả nhóm trong một giao dịch: hoặc tất cả cùng đổi chỗ, hoặc không hồ sơ nào đổi.
    // pairs: [{ documentId, toId }]
    function submitBatch(pairs) {
        showToast('<i class="fas fa-spinner fa-spin mr-1"></i>Đang chuyển ' + pairs.length + ' hồ sơ...');

        postJson(ROUTES.moveBatch, {
            moves: pairs.map(function (pair) { return { document_id: pair.documentId, to_location_id: pair.toId }; })
        }).then(function (result) {
            setRelabelCount(result.relabel_count);
            recordHistory(result.moves);

            var staleCount = result.moves.filter(function (m) { return m.stale; }).length;
            var message = 'Đã chuyển <b>' + result.moves.length + '</b> hồ sơ';
            if (staleCount) message += ' · ' + staleCount + ' nhãn cần in lại';

            var actions = [{ label: 'Hoàn tác', run: undo }];
            if (staleCount) actions.push({ label: 'Xem nhãn cần in', run: function () { showTab('relabel'); } });
            showToast(message, { actions: actions });

            // Vẽ lại tại chỗ, giữ nhóm ở vị trí mới để có thể dời tiếp.
            state.pendingPick = result.moves.map(function (m) { return String(m.to.id); });
            load(true);
        }).catch(function (error) {
            showToast(esc(error.message), { error: true });
            load(true);
        });
    }

    /* ---------- Hoàn tác / Làm lại (tối đa 10 thao tác) ----------
       Mỗi thao tác là một lần thả (1 hồ sơ hoặc cả nhóm). Hoàn tác đưa hồ sơ về ô cũ, làm lại đưa sang ô mới,
       cả hai đi qua endpoint chuyển hàng loạt nên cả nhóm luôn đổi chỗ trọn vẹn. Lịch sử chỉ sống trong trang này. */
    var HISTORY_MAX = 10;
    var undoStack = [];
    var redoStack = [];
    var historyBusy = false;
    var undoButton = document.getElementById('wm-undo');
    var redoButton = document.getElementById('wm-redo');

    // moves: kết quả trả về từ server, dạng [{ document, from, to }]
    function recordHistory(moves) {
        undoStack.push({
            moves: moves.map(function (m) {
                return {
                    docId: m.document.id, docCode: m.document.code,
                    fromId: m.from.id, fromCode: m.from.code,
                    toId: m.to.id, toCode: m.to.code
                };
            })
        });
        if (undoStack.length > HISTORY_MAX) undoStack.shift();
        redoStack = []; // thao tác mới làm nhánh "làm lại" cũ không còn đúng
        updateHistoryButtons();
    }

    function describeEntry(entry, direction) {
        if (entry.moves.length > 1) return entry.moves.length + ' hồ sơ';
        var m = entry.moves[0];
        return m.docCode + ' ' + (direction === 'undo' ? m.toCode + ' → ' + m.fromCode : m.fromCode + ' → ' + m.toCode);
    }

    function updateHistoryButtons() {
        var lastUndo = undoStack[undoStack.length - 1];
        var lastRedo = redoStack[redoStack.length - 1];

        undoButton.disabled = historyBusy || !lastUndo;
        redoButton.disabled = historyBusy || !lastRedo;
        document.getElementById('wm-undo-count').textContent = undoStack.length ? '(' + undoStack.length + ')' : '';
        document.getElementById('wm-redo-count').textContent = redoStack.length ? '(' + redoStack.length + ')' : '';
        undoButton.title = lastUndo ? 'Hoàn tác: ' + describeEntry(lastUndo, 'undo') + ' (Ctrl+Z)' : 'Chưa có thao tác để hoàn tác (Ctrl+Z)';
        redoButton.title = lastRedo ? 'Làm lại: ' + describeEntry(lastRedo, 'redo') + ' (Ctrl+Y)' : 'Chưa có thao tác để làm lại (Ctrl+Y)';
    }

    function stepHistory(direction) {
        if (historyBusy || dragging || marquee) return;
        var from = direction === 'undo' ? undoStack : redoStack;
        var to = direction === 'undo' ? redoStack : undoStack;
        var entry = from.pop();
        if (!entry) return;

        var verb = direction === 'undo' ? 'hoàn tác' : 'làm lại';
        historyBusy = true;
        updateHistoryButtons();
        showToast('<i class="fas fa-spinner fa-spin mr-1"></i>Đang ' + verb + ' ' + esc(describeEntry(entry, direction)) + '...');

        postJson(ROUTES.moveBatch, {
            moves: entry.moves.map(function (m) {
                return { document_id: m.docId, to_location_id: direction === 'undo' ? m.fromId : m.toId };
            })
        }).then(function (result) {
            setRelabelCount(result.relabel_count);
            to.push(entry);
            if (to.length > HISTORY_MAX) to.shift();

            var message = (direction === 'undo' ? 'Đã hoàn tác' : 'Đã làm lại') + ': <b>' + esc(describeEntry(entry, direction)) + '</b>';
            var actions = [];
            if (undoStack.length) actions.push({ label: 'Hoàn tác', run: undo });
            if (redoStack.length) actions.push({ label: 'Làm lại', run: redo });
            showToast(message, { actions: actions });

            var landed = result.moves.map(function (m) { return String(m.to.id); });
            if (landed.length > 1) state.pendingPick = landed;
            else {
                state.pendingCell = landed[0];
                state.pendingQuiet = true;
            }
            load(true);
        }).catch(function (error) {
            // Thường do ô cũ đã bị người khác dùng; bỏ thao tác này khỏi lịch sử để không chặn các bước còn lại.
            showToast(esc(error.message) + ' — không thể ' + verb + ', đã bỏ thao tác này khỏi lịch sử.', { error: true });
            load(true);
        }).then(function () {
            historyBusy = false;
            updateHistoryButtons();
        });
    }

    function undo() { stepHistory('undo'); }
    function redo() { stepHistory('redo'); }

    undoButton.addEventListener('click', undo);
    redoButton.addEventListener('click', redo);

    document.addEventListener('keydown', function (event) {
        if (!(event.ctrlKey || event.metaKey) || event.altKey || paneMap.hidden || !modal.hidden) return;
        if (event.target.closest('input, textarea, select, [contenteditable="true"]')) return;

        var key = event.key.toLowerCase();
        if (key === 'z' && !event.shiftKey) {
            event.preventDefault();
            undo();
        } else if (key === 'y' || (key === 'z' && event.shiftKey)) {
            event.preventDefault();
            redo();
        }
    });

    // Hồ sơ đã bị huỷ thì mọi bước hoàn tác/làm lại có nó không còn chạy được, bỏ khỏi lịch sử luôn.
    function forgetDocuments(ids) {
        var gone = new Set(ids.map(String));
        function keep(entry) {
            return !entry.moves.some(function (m) { return gone.has(String(m.docId)); });
        }
        undoStack = undoStack.filter(keep);
        redoStack = redoStack.filter(keep);
        updateHistoryButtons();
    }

    /* ---------- Thanh thao tác cho các ô đã chọn ---------- */

    var selbar = document.getElementById('wm-selbar');
    var selbarText = document.getElementById('wm-selbar-text');
    var selFrame = 0;

    function scheduleSelectionBar() {
        if (!selFrame) selFrame = requestAnimationFrame(updateSelectionBar);
    }

    function pickedDocs() {
        var docs = [];
        canvas.querySelectorAll('.wm-cell--picked[data-doc]').forEach(function (el) {
            docs.push({ id: el.dataset.doc, code: el.dataset.docCode || el.dataset.code });
        });
        return docs;
    }

    function updateSelectionBar() {
        selFrame = 0;
        if (!picked.size) {
            selbar.hidden = true;
            return;
        }

        var count = pickedDocs().length;
        selbarText.innerHTML = 'Đã chọn <b>' + count + '</b> hồ sơ' + (count > 1 ? ' — kéo ô đã chọn để dời cả nhóm' : '');

        // Sửa chỉ làm trên 1 hồ sơ; huỷ làm được cả nhóm.
        selbar.querySelector('[data-sel="edit"]').hidden = !CAN.update || count !== 1;
        var dispose = selbar.querySelector('[data-sel="dispose"]');
        dispose.hidden = !CAN.dispose || !count;
        dispose.querySelector('span').textContent = '(' + count + ')';
        selbar.hidden = !count;
    }

    selbar.addEventListener('click', function (event) {
        var button = event.target.closest('[data-sel]');
        if (!button) return;
        var docs = pickedDocs();
        var action = button.dataset.sel;

        if (action === 'clear') clearPicks();
        else if (action === 'edit' && docs.length === 1) openEdit(docs[0]);
        else if (action === 'dispose') openDispose(docs);
    });

    panel.addEventListener('click', function (event) {
        var button = event.target.closest('[data-crud]');
        if (!button) return;
        var action = button.dataset.crud;

        if (action === 'create') openCreate({ id: button.dataset.location, code: button.dataset.code });
        else if (action === 'edit') openEdit({ id: button.dataset.docId, code: button.dataset.code });
        else if (action === 'dispose') openDispose([{ id: button.dataset.docId, code: button.dataset.code }]);
    });

    /* ---------- Hộp thoại tạo / sửa / huỷ ---------- */

    var modal = document.getElementById('wm-modal');
    var form = document.getElementById('wm-form');
    var modalHead = modal.querySelector('.wm-modal-head');
    var modalTitle = document.getElementById('wm-modal-title');
    var modalTargets = document.getElementById('wm-modal-targets');
    var modalError = document.getElementById('wm-modal-error');
    var modalSubmit = document.getElementById('wm-modal-submit');
    var fileCurrent = document.getElementById('wm-file-current');
    var modalCtx = null; // { mode: 'create' | 'edit' | 'dispose', targets: [{ id, code }] } — tạo/sửa luôn đúng 1 phần tử

    document.getElementById('wm-types').innerHTML = (ROUTES.types || []).map(function (type) {
        return '<label><input type="checkbox" name="document_types_id[]" value="' + esc(type.id) + '">' + esc(type.name) + '</label>';
    }).join('') || '<span class="wm-empty-note">Chưa khai báo loại tài liệu.</span>';

    function targetList(label, targets) {
        var shown = targets.slice(0, 40).map(function (t) { return '<b>' + esc(t.code) + '</b>'; }).join(', ');
        return label + ' (' + targets.length + '): ' + shown + (targets.length > 40 ? ', … và ' + (targets.length - 40) + ' ô khác' : '');
    }

    function showModal(ctx) {
        modalCtx = ctx;
        form.reset();
        modalError.hidden = true;
        fileCurrent.innerHTML = '';
        modalSubmit.disabled = false;

        var isDispose = ctx.mode === 'dispose';
        form.querySelector('[data-part="fields"]').hidden = isDispose;
        form.querySelector('[data-part="dispose"]').hidden = !isDispose;
        modalHead.classList.toggle('is-danger', isDispose);
        modalSubmit.className = 'btn btn-sm ' + (isDispose ? 'btn-danger' : 'btn-primary');

        var n = ctx.targets.length;
        if (ctx.mode === 'create') {
            modalTitle.textContent = 'Tạo hồ sơ mới';
            modalTargets.innerHTML = 'Vị trí: <b>' + esc(ctx.targets[0].code) + '</b> — mã hồ sơ lấy theo mã vị trí.';
            modalSubmit.textContent = 'Tạo hồ sơ';
        } else if (ctx.mode === 'edit') {
            modalTitle.textContent = 'Sửa hồ sơ';
            modalTargets.innerHTML = 'Hồ sơ: <b>' + esc(ctx.targets[0].code) + '</b>';
            modalSubmit.textContent = 'Lưu thay đổi';
        } else {
            modalTitle.textContent = 'Huỷ hồ sơ';
            modalTargets.innerHTML = targetList('Hồ sơ', ctx.targets);
            modalSubmit.textContent = n > 1 ? 'Huỷ ' + n + ' hồ sơ' : 'Huỷ hồ sơ';
        }

        tooltip.hidden = true;
        modal.hidden = false;
        var first = form.querySelector(isDispose ? '[name="reason"]' : '[name="name"]');
        if (first) first.focus();
    }

    function closeModal() {
        modal.hidden = true;
        modalCtx = null;
    }

    function openCreate(target) {
        showModal({ mode: 'create', targets: [target] });
    }

    function openDispose(targets) {
        if (!targets.length) return;
        showModal({ mode: 'dispose', targets: targets });
    }

    // Tải thông tin hiện tại để điền sẵn vào form.
    function openEdit(target) {
        showToast('<i class="fas fa-spinner fa-spin mr-1"></i>Đang tải hồ sơ ' + esc(target.code) + '...');
        fetchJson(ROUTES.document, { id: target.id }).then(function (doc) {
            toast.hidden = true;
            showModal({ mode: 'edit', targets: [{ id: doc.id, code: doc.code }] });
            form.elements.name.value = doc.name || '';
            form.elements.expired_date.value = doc.expired_date || '';
            var types = new Set((doc.type_ids || []).map(String));
            form.querySelectorAll('[name="document_types_id[]"]').forEach(function (box) { box.checked = types.has(box.value); });
            if (doc.file_url) {
                fileCurrent.innerHTML = 'Tệp hiện tại: <a href="' + esc(doc.file_url) + '" target="_blank" rel="noopener">mở tệp</a> — chọn tệp mới để thay.';
            }
        }).catch(function () {
            showToast('Không tải được hồ sơ (có thể là hồ sơ riêng tư hoặc đã bị huỷ).', { error: true });
        });
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal || event.target.closest('[data-modal-close]')) closeModal();
    });

    function postForm(url, data) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': ROUTES.csrf },
            body: data
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (!response.ok) {
                    var message = body.message
                        || (response.status === 419 ? 'Phiên làm việc hết hạn, vui lòng tải lại trang.'
                            : response.status === 413 ? 'Tệp đính kèm quá lớn.'
                            : 'Lỗi máy chủ (' + response.status + ').');
                    throw new Error(message);
                }
                return body;
            });
        });
    }

    function modalFail(message) {
        modalError.textContent = message;
        modalError.hidden = false;
        modalSubmit.disabled = false;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!modalCtx || modalSubmit.disabled) return;

        var ctx = modalCtx;
        var ids = ctx.targets.map(function (t) { return t.id; }).join(',');
        var data = new FormData();
        var url;
        var name = form.elements.name.value.trim();
        var file = form.elements.file_attachment.files[0];

        if (ctx.mode === 'dispose') {
            var reason = form.elements.reason.value.trim();
            if (!reason) return modalFail('Vui lòng nhập lý do huỷ hồ sơ.');
            url = ROUTES.docDispose;
            data.append('document_ids', ids);
            data.append('reason', reason);
        } else {
            if (!name) return modalFail('Vui lòng nhập tên hồ sơ.');

            url = ctx.mode === 'create' ? ROUTES.docCreate : ROUTES.docUpdate;
            data.append(ctx.mode === 'create' ? 'location_id' : 'document_id', ctx.targets[0].id);
            data.append('name', name);
            data.append('expired_date', form.elements.expired_date.value);
            form.querySelectorAll('[name="document_types_id[]"]:checked').forEach(function (box) {
                data.append('document_types_id[]', box.value);
            });
            if (file) data.append('file_attachment', file);
        }

        modalSubmit.disabled = true;
        modalError.hidden = true;

        postForm(url, data).then(function (result) {
            closeModal();
            setRelabelCount(result.relabel_count);

            var docs = result.documents || [];
            var n = docs.length;
            var one = n === 1 ? ' <b>' + esc(docs[0].code) + '</b>' : ' <b>' + n + '</b> hồ sơ';
            var messages = { create: 'Đã tạo', edit: 'Đã cập nhật', dispose: 'Đã huỷ' };
            showToast(messages[ctx.mode] + one + (ctx.mode === 'dispose' ? ', vị trí đã trả về trống.' : '.'));

            if (ctx.mode === 'dispose') {
                forgetDocuments(docs.map(function (d) { return d.id; }));
            }

            // Ô đang mở chi tiết nằm trong nhóm vừa thao tác thì mở lại để thấy thông tin mới.
            var touched = docs.map(function (d) { return String(d.location_id); });
            if (state.selectedCell && touched.indexOf(String(state.selectedCell)) !== -1) {
                state.pendingCell = String(state.selectedCell);
                state.pendingQuiet = true;
            }
            load(true);
        }).catch(function (error) {
            modalFail(error.message);
        });
    });

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

    /* Chọn nhiều hồ sơ để in nhãn gáy binder. Danh sách tự tải lại khi quay về cửa sổ,
       nên giữ id đã chọn và bỏ những id không còn trong danh sách. */
    var BINDER_MAX_DOCS = 100;
    var binderSelected = new Set();
    var binderAll = document.getElementById('wm-binder-all');
    var binderPrint = document.getElementById('wm-binder-print');
    var binderClear = document.getElementById('wm-binder-clear');

    function binderLabelUrl(ids) {
        return ROUTES.binderLabel + '?ids=' + encodeURIComponent(ids.join(','));
    }

    function updateBinderSelection() {
        var boxes = relabelBody.querySelectorAll('.wm-binder-pick');
        var checked = 0;
        boxes.forEach(function (box) {
            box.checked = binderSelected.has(box.value);
            if (box.checked) checked++;
        });
        binderAll.checked = boxes.length > 0 && checked === boxes.length;
        binderAll.indeterminate = checked > 0 && checked < boxes.length;
        document.getElementById('wm-binder-count').textContent = binderSelected.size;
        binderPrint.disabled = binderSelected.size === 0;
        binderClear.hidden = binderSelected.size === 0;
    }

    relabelBody.addEventListener('change', function (event) {
        var box = event.target.closest('.wm-binder-pick');
        if (!box) return;
        if (box.checked) binderSelected.add(box.value);
        else binderSelected.delete(box.value);
        updateBinderSelection();
    });

    binderAll.addEventListener('change', function () {
        var on = binderAll.checked;
        relabelBody.querySelectorAll('.wm-binder-pick').forEach(function (box) {
            if (on) binderSelected.add(box.value);
            else binderSelected.delete(box.value);
        });
        updateBinderSelection();
    });

    binderClear.addEventListener('click', function () {
        binderSelected.clear();
        updateBinderSelection();
    });

    binderPrint.addEventListener('click', function () {
        if (binderSelected.size > BINDER_MAX_DOCS) {
            window.alert('Mỗi lần in tối đa ' + BINDER_MAX_DOCS + ' hồ sơ (đang chọn ' + binderSelected.size + ').');
            return;
        }
        window.open(binderLabelUrl(Array.from(binderSelected)), '_blank', 'noopener');
    });

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

            var listed = new Set(data.rows.map(function (row) { return String(row.id); }));
            binderSelected.forEach(function (id) {
                if (!listed.has(id)) binderSelected.delete(id);
            });

            if (!data.rows.length) {
                relabelBody.innerHTML = '<tr><td colspan="9" class="text-center wm-empty-note py-4">'
                    + '<i class="fas fa-check-circle mr-1"></i>Không có nhãn nào cần in lại.</td></tr>';
                updateBinderSelection();
                return;
            }

            relabelBody.innerHTML = data.rows.map(function (row, index) {
                return '<tr>'
                    + '<td class="wm-check"><input type="checkbox" class="wm-binder-pick" value="' + esc(row.id) + '"'
                    + ' title="Chọn để in nhãn binder"></td>'
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
                    + '<a class="btn btn-sm btn-secondary mr-1" href="' + binderLabelUrl([row.id]) + '" target="_blank" rel="noopener"'
                    + ' title="In nhãn gáy binder">'
                    + '<i class="fas fa-tags mr-1"></i>Binder</a>'
                    + '<a class="btn btn-sm btn-primary" href="' + labelUrl(row.id) + '" target="_blank" rel="noopener">'
                    + '<i class="fas fa-print mr-1"></i>In nhãn</a>'
                    + '</td>'
                    + '</tr>';
            }).join('');
            updateBinderSelection();
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
