<div class="content-wrapper">
    <div class="card mt-3 sc">
        <div class="card-header sc-head">
            <nav id="sc-crumbs" class="sc-crumbs"></nav>
            <div class="sc-actions">
                <input type="search" id="sc-filter" class="form-control form-control-sm" placeholder="Lọc..." hidden>
                <span id="sc-total" class="sc-total" hidden></span>
                <span id="sc-dirty" class="sc-dirty" hidden>Có thay đổi chưa lưu</span>
                <button type="button" id="sc-add" class="btn btn-sm btn-outline-primary"></button>
                <button type="button" id="sc-reset" class="btn btn-sm btn-outline-secondary" hidden>Hoàn tác</button>
                <button type="button" id="sc-save" class="btn btn-sm btn-primary" hidden>
                    <i class="fas fa-save mr-1"></i> Lưu cấu trúc
                </button>
            </div>
        </div>

        <div class="card-body">
            <p id="sc-note" class="sc-note" hidden></p>

            <div id="sc-wh-form" class="sc-panel" hidden>
                <div class="sc-panel-title" id="sc-wh-title">Kho mới</div>
                <div class="sc-panel-body">
                    <div class="form-group mb-0">
                        <label>Mã kho <span class="text-danger">*</span></label>
                        <input type="text" id="sc-wh-code" class="form-control form-control-sm" placeholder="VD: A8">
                    </div>
                    <div class="form-group mb-0">
                        <label>Tên kho <span class="text-danger">*</span></label>
                        <input type="text" id="sc-wh-name" class="form-control form-control-sm"
                            placeholder="VD: Kho hồ sơ lầu 3">
                    </div>
                    <div class="sc-panel-actions">
                        <button type="button" id="sc-wh-save" class="btn btn-sm btn-primary">Lưu kho</button>
                        <button type="button" id="sc-wh-cancel" class="btn btn-sm btn-outline-secondary">Huỷ</button>
                    </div>
                </div>
            </div>

            <div id="sc-warehouses" class="sc-cards" hidden></div>
            <div id="sc-shelves" class="sc-cards" hidden></div>

            <div id="sc-legend" class="sc-legend sc-legend-top" hidden>
                <span><i class="sc-swatch is-busy"></i> Có hồ sơ</span>
                <span><i class="sc-swatch is-free"></i> Trống</span>
                <span><i class="sc-swatch is-off"></i> Đang khoá</span>
                <span><i class="sc-swatch is-new"></i> Sẽ tạo</span>
                <span><i class="sc-swatch is-cut"></i> Sẽ khoá</span>
            </div>

            {{-- Mỗi khối là một kệ với cấu trúc tầng riêng, JS dựng từ khuôn --}}
            <div id="sc-blocks" hidden></div>

            <div id="sc-add-more" class="sc-add-more" hidden>
                <button type="button" id="sc-add-block" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm kệ
                </button>
                <span class="sc-add-more-hint">Mỗi kệ có cấu trúc tầng riêng; tất cả được tạo cùng lúc khi bấm Lưu.</span>
            </div>
        </div>
    </div>
</div>

<style>
    .sc-head {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
    }

    .sc-crumbs {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
        font-size: 14px;
    }

    .sc-crumb {
        border: none;
        background: none;
        padding: 0;
        color: #4a90d9;
        cursor: pointer;
    }

    .sc-crumb:hover {
        text-decoration: underline;
    }

    .sc-crumb.is-current {
        color: #1b3a5c;
        font-weight: 700;
        cursor: default;
    }

    .sc-crumb.is-current:hover {
        text-decoration: none;
    }

    .sc-crumb-sep {
        color: #adb5bd;
    }

    .sc-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .sc-actions input[type="search"] {
        width: 170px;
    }

    .sc-dirty {
        color: #b26a00;
        font-size: 12px;
        font-weight: 600;
    }

    .sc-note {
        color: #6c757d;
        margin: 8px 0;
    }

    .sc-panel {
        border: 1px solid #cfe0f0;
        background: #f6fafe;
        border-radius: 5px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }

    .sc-panel-title {
        font-weight: 700;
        color: #1b3a5c;
        margin-bottom: 8px;
    }

    .sc-panel-body {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .sc-panel-body .form-group {
        min-width: 220px;
    }

    .sc-panel-actions {
        display: flex;
        gap: 8px;
    }

    /* ---------- Thẻ Kho / Kệ: dùng chung ngôn ngữ thị giác với Sơ Đồ Kho ---------- */
    .sc-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 1.25rem;
        margin-top: .5rem;
    }

    .sc-tile {
        position: relative;
        text-align: left;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem 1.35rem 1.15rem;
        display: flex;
        flex-direction: column;
        cursor: pointer;
        box-shadow: 0 2px 6px -1px rgba(0, 0, 0, .05), 0 1px 3px rgba(0, 0, 0, .03);
        transition: all .24s cubic-bezier(.16, 1, .3, 1);
        overflow: hidden;
        user-select: none;
    }

    .sc-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #94a3b8;
        transition: height .2s ease;
    }

    .sc-tile:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px -6px rgba(0, 58, 93, .12), 0 4px 10px -2px rgba(0, 58, 93, .06);
        border-color: #3b82f6;
    }

    .sc-tile:hover::before {
        height: 5px;
    }

    .sc-tile:hover .sc-tile-arrow {
        color: #2563eb;
    }

    .sc-tile:hover .sc-tile-arrow i {
        transform: translateX(3px);
    }

    .sc-tile:hover .sc-tile-icon {
        background: #e0f2fe;
        color: #0369a1;
        transform: scale(1.05);
    }

    .sc-tile.is-off {
        opacity: .62;
    }

    .sc-tile-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .65rem;
    }

    .sc-tile-code-wrap {
        display: inline-flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
    }

    .sc-tile-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f1f5f9;
        color: #0284c7;
        font-size: 1.1rem;
        transition: all .2s ease;
        flex-shrink: 0;
    }

    .sc-tile-code {
        font-weight: 800;
        font-size: 1.35rem;
        color: #0f172a;
        letter-spacing: -.02em;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sc-tile-tools {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        flex-shrink: 0;
    }

    .sc-tile-badge {
        font-size: .76rem;
        font-weight: 700;
        padding: .28rem .65rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        line-height: 1;
    }

    .sc-tile-edit {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        border-radius: 8px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        cursor: pointer;
        transition: all .2s ease;
    }

    .sc-tile-edit:hover {
        background: #e0f2fe;
        border-color: #7dd3fc;
        color: #0369a1;
    }

    .sc-tile-name {
        font-size: .98rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.45;
        margin-bottom: .9rem;
        min-height: 2.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
    }

    .sc-tile-progress-wrap {
        width: 100%;
        height: 7px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin-bottom: .85rem;
    }

    .sc-tile-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: #3b82f6;
        transition: width .4s ease;
    }

    .sc-tile-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding-top: .75rem;
        border-top: 1px solid #f1f5f9;
        font-size: .82rem;
        color: #64748b;
    }

    .sc-tile-stat {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .sc-tile-used strong {
        color: #0f172a;
        font-weight: 700;
    }

    .sc-tile-avail {
        display: inline-block;
        font-size: .75rem;
        padding: .12rem .45rem;
        border-radius: 4px;
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .sc-tile-avail.is-full {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
        font-weight: 600;
    }

    .sc-tile-arrow {
        font-size: .78rem;
        font-weight: 600;
        color: #64748b;
        transition: color .2s ease;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        margin-left: auto;
    }

    .sc-tile-arrow i {
        transition: transform .2s ease;
        font-size: .7rem;
    }

    /* Mức lấp đầy 0% -> 100%, cùng thang màu với Sơ Đồ Kho */
    .sc-tile.sc-b0::before { background: #cbd5e1; }
    .sc-badge-b0 { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .sc-bar-b0 { background: #cbd5e1; }

    .sc-tile.sc-b1::before { background: #0ea5e9; }
    .sc-badge-b1 { background: #e0f2fe; color: #0284c7; }
    .sc-bar-b1 { background: linear-gradient(90deg, #38bdf8, #0ea5e9); }

    .sc-tile.sc-b2::before { background: #2563eb; }
    .sc-badge-b2 { background: #dbeafe; color: #1d4ed8; }
    .sc-bar-b2 { background: linear-gradient(90deg, #60a5fa, #2563eb); }

    .sc-tile.sc-b3::before { background: #4f46e5; }
    .sc-badge-b3 { background: #ede9fe; color: #4338ca; }
    .sc-bar-b3 { background: linear-gradient(90deg, #818cf8, #4f46e5); }

    .sc-tile.sc-b4::before { background: #d97706; }
    .sc-badge-b4 { background: #fef3c7; color: #b45309; }
    .sc-bar-b4 { background: linear-gradient(90deg, #fbbf24, #d97706); }

    .sc-tile.sc-b5::before { background: #dc2626; }
    .sc-badge-b5 { background: #fee2e2; color: #b91c1c; }
    .sc-bar-b5 { background: linear-gradient(90deg, #f87171, #dc2626); }

    .sc-total {
        font-size: 12px;
        font-weight: 600;
        color: #1b3a5c;
        background: #eef4fb;
        border-radius: 999px;
        padding: 3px 10px;
    }

    .sc-legend-top {
        margin-bottom: 12px;
    }

    .sc-block {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        padding: 14px 16px 12px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .sc-block.is-new {
        border-left: 4px solid #3f9e56;
    }

    .sc-block.has-error {
        border-color: #fca5a5;
        border-left-color: #dc2626;
    }

    .sc-block-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .sc-block-title {
        font-weight: 700;
        color: #1b3a5c;
    }

    .sc-block-title small {
        font-weight: 500;
        color: #64748b;
        margin-left: 6px;
    }

    .sc-block-remove {
        padding: 0 4px;
    }

    .sc-add-more {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 10px 0 4px;
    }

    .sc-add-more-hint {
        font-size: 12px;
        color: #64748b;
    }

    .sc-form {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .sc-form .form-group {
        min-width: 180px;
    }

    .sc-form-wide {
        flex: 1 1 260px;
    }

    .sc-preview {
        flex: 1 1 100%;
        font-size: 12px;
        color: #495057;
        background: #f6fafe;
        border: 1px solid #cfe0f0;
        border-radius: 5px;
        padding: 8px 10px;
    }

    .sc-preview.is-bad {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .sc-preview b {
        color: #1b3a5c;
    }

    .sc-picker {
        margin-bottom: 16px;
    }

    .sc-picker-title {
        font-size: 13px;
        color: #495057;
        margin-bottom: 8px;
    }

    .sc-picker-grid {
        display: grid;
        gap: 2px;
        grid-template-columns: repeat(var(--cols), 13px);
        width: max-content;
        padding: 6px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #fff;
        overflow-x: auto;
        max-width: 100%;
    }

    .sc-pcell {
        width: 13px;
        height: 13px;
        border: 1px solid #ced4da;
        border-radius: 2px;
        background: #f8f9fa;
        cursor: pointer;
    }

    .sc-pcell.is-on {
        background: #7cb9ff;
        border-color: #4a90d9;
    }

    .sc-picker-foot {
        display: flex;
        gap: 12px;
        align-items: baseline;
        margin-top: 8px;
    }

    .sc-picker-readout {
        font-weight: 600;
    }

    .sc-picker-hint {
        font-size: 12px;
        color: #6c757d;
    }

    .sc-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .sc-bulk {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .sc-bulk input {
        width: 80px;
    }

    .sc-legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #495057;
    }

    .sc-swatch {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 2px;
        vertical-align: -1px;
        margin-right: 3px;
    }

    .sc-grid {
        overflow-x: auto;
        padding-bottom: 6px;
    }

    .sc-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 3px;
    }

    .sc-row-label {
        width: 88px;
        flex: none;
        font-size: 12px;
        text-align: right;
        color: #343a40;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sc-cells {
        display: grid;
        grid-template-columns: repeat(var(--cols), var(--sc-cell));
        gap: 2px;
        flex: none;
    }

    .sc-cell {
        height: var(--sc-cell);
        border-radius: 2px;
        border: 1px solid transparent;
    }

    .sc-cell.is-free,
    .sc-swatch.is-free {
        background: #eef1f4;
        border-color: #d6dce2;
    }

    .sc-cell.is-busy,
    .sc-swatch.is-busy {
        background: #4a90d9;
        border-color: #3a7bbf;
    }

    .sc-cell.is-off,
    .sc-swatch.is-off {
        background: repeating-linear-gradient(45deg, #cfd4d9, #cfd4d9 3px, #b9bfc5 3px, #b9bfc5 6px);
        border-color: #aeb4ba;
    }

    .sc-cell.is-new,
    .sc-swatch.is-new {
        background: #d7f0dc;
        border: 1px dashed #3f9e56;
    }

    .sc-cell.is-cut,
    .sc-swatch.is-cut {
        background: #f9d7d7;
        border-color: #d97b7b;
    }

    .sc-handle {
        flex: none;
        width: 10px;
        height: 22px;
        border: 1px solid #adb5bd;
        border-radius: 3px;
        background: #f1f3f5;
        cursor: ew-resize;
        padding: 0;
        touch-action: none;
    }

    .sc-handle:hover,
    .sc-handle.is-dragging {
        background: #4a90d9;
        border-color: #3a7bbf;
    }

    .sc-num {
        flex: none;
        width: 62px;
        font-size: 12px;
        padding: 1px 4px;
        border: 1px solid #ced4da;
        border-radius: 3px;
    }

    .sc-drop {
        flex: none;
        border: none;
        background: transparent;
        color: #adb5bd;
        cursor: pointer;
        padding: 0 4px;
    }

    .sc-drop:hover {
        color: #dc3545;
    }

    .sc-row.is-removed .sc-row-label,
    .sc-row.is-removed .sc-num {
        text-decoration: line-through;
        opacity: .6;
    }

    .sc-foot {
        display: flex;
        gap: 14px;
        align-items: center;
        margin-top: 12px;
    }

    .sc-summary {
        font-size: 13px;
        color: #495057;
    }
</style>

<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script type="application/json" id="sc-config">
{
    "warehouses": "{{ route('pages.storageLocation.structure.warehouses') }}",
    "saveWarehouse": "{{ route('pages.storageLocation.structure.saveWarehouse') }}",
    "shelves": "{{ route('pages.storageLocation.structure.shelves') }}",
    "detail": "{{ route('pages.storageLocation.structure.detail') }}",
    "apply": "{{ route('pages.storageLocation.structure.apply') }}",
    "applyMany": "{{ route('pages.storageLocation.structure.applyMany') }}",
    "previewCodes": "{{ route('pages.storageLocation.structure.previewCodes') }}",
    "maxTiers": {{ $maxTiers }},
    "maxLocations": {{ $maxLocations }},
    "maxBatchShelves": {{ $maxBatchShelves }},
    "csrf": "{{ csrf_token() }}"
}
</script>

@verbatim
<script>
(function () {
    'use strict';

    var CFG = JSON.parse(document.getElementById('sc-config').textContent);

    function byId(id) {
        return document.getElementById(id);
    }

    var elCrumbs = byId('sc-crumbs');
    var elFilter = byId('sc-filter');
    var elAdd = byId('sc-add');
    var elNote = byId('sc-note');
    var elWarehouses = byId('sc-warehouses');
    var elShelves = byId('sc-shelves');
    var elWhForm = byId('sc-wh-form');
    var elWhTitle = byId('sc-wh-title');
    var elWhCode = byId('sc-wh-code');
    var elWhName = byId('sc-wh-name');
    var elWhSave = byId('sc-wh-save');
    var elWhCancel = byId('sc-wh-cancel');
    var elLegend = byId('sc-legend');
    var elBlocks = byId('sc-blocks');
    var elAddMore = byId('sc-add-more');
    var elAddBlock = byId('sc-add-block');
    var elTotal = byId('sc-total');
    var elSave = byId('sc-save');
    var elReset = byId('sc-reset');
    var elDirty = byId('sc-dirty');

    // view: 'warehouses' | 'shelves' | 'create' (nhiều kệ mới, mỗi kệ một khối) | 'editor' (sửa một kệ có sẵn)
    var state = {
        view: 'warehouses',
        warehouses: [], shelves: [],
        warehouse: null,
        blocks: [], dirty: false, editingWarehouse: null
    };

    var TITLES = { success: 'Đã lưu', warning: 'Chưa làm được', error: 'Không thực hiện được' };

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function toast(icon, text) {
        Swal.fire({
            icon: icon,
            title: TITLES[icon] || '',
            text: text,
            timer: icon === 'success' ? 2600 : undefined,
            showConfirmButton: icon !== 'success'
        });
    }

    function setDirty(value) {
        state.dirty = value;
        elDirty.hidden = !value;
    }

    // Tăng phần số cuối của mã, giữ nguyên số chữ số (A8.09 -> A8.10); mã không có số cuối thì không gợi ý.
    function nextCode(code) {
        var match = /^(.*?)(\d+)$/.exec(code);
        if (!match) return null;

        var digits = String(parseInt(match[2], 10) + 1);
        while (digits.length < match[2].length) digits = '0' + digits;
        return match[1] + digits;
    }

    var BUCKETS = ['Trống', '1-25%', '26-50%', '51-75%', '76-99%', 'Đầy'];

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

    function tileMarkup(item, options) {
        var used = Number(item.used) || 0;
        var total = Number(item.total) || 0;
        var pct = total ? Math.round(used / total * 100) : 0;
        var bucket = bucketOf(used, total);
        var available = Math.max(0, total - used);
        var off = Number(item.status_id) !== 1;

        return '<div class="sc-tile sc-b' + bucket + (off ? ' is-off' : '') + '" data-id="' + item.id + '"'
            + ' title="' + esc(item.name || item.code) + ' — ' + used.toLocaleString() + '/' + total.toLocaleString()
            + ' ô đã lưu (' + pct + '%)">'
            + '<div class="sc-tile-top">'
            +   '<div class="sc-tile-code-wrap">'
            +     '<span class="sc-tile-icon"><i class="fas ' + options.icon + '"></i></span>'
            +     '<span class="sc-tile-code">' + esc(item.code) + '</span>'
            +   '</div>'
            +   '<div class="sc-tile-tools">'
            +     '<span class="sc-tile-badge sc-badge-b' + bucket + '">' + pct + '% ' + BUCKETS[bucket] + '</span>'
            +     (options.editable
                    ? '<button type="button" class="sc-tile-edit" data-edit="' + item.id + '" title="Sửa mã và tên">'
                        + '<i class="fas fa-pen"></i></button>'
                    : '')
            +   '</div>'
            + '</div>'
            + '<div class="sc-tile-name">' + esc(item.name) + (off ? ' (ngưng dùng)' : '') + '</div>'
            + '<div class="sc-tile-progress-wrap">'
            +   '<div class="sc-tile-progress-bar sc-bar-b' + bucket + '" style="width:'
            +   Math.min(100, Math.max(pct, pct > 0 ? 3 : 0)) + '%"></div>'
            + '</div>'
            + '<div class="sc-tile-footer">'
            +   '<div class="sc-tile-stat">'
            +     '<span class="sc-tile-used"><strong>' + options.meta + '</strong></span>'
            +     (!total
                    ? '<span class="sc-tile-avail">Chưa có ô</span>'
                    : available > 0
                        ? '<span class="sc-tile-avail">Trống ' + available.toLocaleString() + '</span>'
                        : '<span class="sc-tile-avail is-full">Đầy</span>')
            +   '</div>'
            +   '<span class="sc-tile-arrow">' + options.action + ' <i class="fas fa-chevron-right ml-1"></i></span>'
            + '</div>'
            + '</div>';
    }

    function matches(item) {
        var keyword = elFilter.value.trim().toLowerCase();
        return !keyword
            || item.code.toLowerCase().indexOf(keyword) >= 0
            || (item.name || '').toLowerCase().indexOf(keyword) >= 0;
    }

    /* ---------- điều hướng ---------- */

    function show(view) {
        state.view = view;
        var isGrid = view === 'create' || view === 'editor';

        elWarehouses.hidden = view !== 'warehouses';
        elShelves.hidden = view !== 'shelves';
        elBlocks.hidden = !isGrid;
        elLegend.hidden = !isGrid;
        elAddMore.hidden = view !== 'create';
        elTotal.hidden = view !== 'create';
        elSave.hidden = !isGrid;
        elReset.hidden = !isGrid;
        elFilter.hidden = isGrid;
        elFilter.value = '';
        elNote.hidden = true;

        // Nút thêm ở đầu trang vẫn giữ khi đang tạo kệ: bấm tiếp là thêm một khối nữa bên dưới.
        elAdd.hidden = view === 'editor';
        elAdd.innerHTML = '<i class="fas fa-plus mr-1"></i> '
            + (view === 'warehouses' ? 'Kho mới' : view === 'create' ? 'Thêm kệ' : 'Kệ mới');

        if (!isGrid) {
            clearBlocks();
            setDirty(false);
        }

        hideWarehouseForm();
        renderCrumbs();
    }

    function renderCrumbs() {
        var parts = ['<button type="button" class="sc-crumb'
            + (state.view === 'warehouses' ? ' is-current' : '') + '" data-go="warehouses">Kho</button>'];

        if (state.warehouse) {
            parts.push('<span class="sc-crumb-sep">/</span>');
            parts.push('<button type="button" class="sc-crumb'
                + (state.view === 'shelves' ? ' is-current' : '') + '" data-go="shelves">'
                + esc(state.warehouse.code) + ' — ' + esc(state.warehouse.name) + '</button>');
        }

        if (state.view === 'create' || state.view === 'editor') {
            var label = state.view === 'create'
                ? 'Kệ mới'
                : esc((state.blocks[0] && state.blocks[0].el.code.value) || 'Kệ');
            parts.push('<span class="sc-crumb-sep">/</span>');
            parts.push('<button type="button" class="sc-crumb is-current">' + label + '</button>');
        }

        elCrumbs.innerHTML = parts.join(' ');
    }

    elCrumbs.addEventListener('click', function (event) {
        var crumb = event.target.closest('.sc-crumb[data-go]');
        if (!crumb || crumb.classList.contains('is-current')) return;

        if (state.dirty && !confirm('Bỏ các thay đổi chưa lưu?')) return;

        if (crumb.dataset.go === 'warehouses') {
            state.warehouse = null;
            show('warehouses');
            loadWarehouses();
        } else {
            show('shelves');
            loadShelves();
        }
    });

    elFilter.addEventListener('input', function () {
        if (state.view === 'warehouses') renderWarehouses();
        else if (state.view === 'shelves') renderShelves();
    });

    /* ---------- cấp 1: kho ---------- */

    function loadWarehouses() {
        elWarehouses.innerHTML = '<p class="sc-note">Đang tải danh sách kho...</p>';

        return fetch(CFG.warehouses)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                state.warehouses = data.warehouses;
                renderWarehouses();
            });
    }

    function renderWarehouses() {
        if (!state.warehouses.length) {
            elWarehouses.innerHTML = '<p class="sc-note">Bộ phận này chưa có kho nào. Bấm "Kho mới" để tạo.</p>';
            return;
        }

        var list = state.warehouses.filter(matches);
        if (!list.length) {
            elWarehouses.innerHTML = '<p class="sc-note">Không có kho nào khớp "' + esc(elFilter.value) + '".</p>';
            return;
        }

        elWarehouses.innerHTML = list.map(function (warehouse) {
            return tileMarkup(warehouse, {
                icon: 'fa-warehouse',
                meta: warehouse.shelves.toLocaleString() + ' kệ · ' + warehouse.total.toLocaleString() + ' vị trí',
                action: 'Xem kệ',
                editable: true
            });
        }).join('');
    }

    elWarehouses.addEventListener('click', function (event) {
        var edit = event.target.closest('.sc-tile-edit');
        if (edit) {
            openWarehouseForm(state.warehouses.find(function (w) { return String(w.id) === edit.dataset.edit; }));
            return;
        }

        var card = event.target.closest('.sc-tile');
        if (!card) return;

        state.warehouse = state.warehouses.find(function (w) { return String(w.id) === card.dataset.id; });
        show('shelves');
        loadShelves();
    });

    function openWarehouseForm(warehouse) {
        state.editingWarehouse = warehouse || null;
        elWhTitle.textContent = warehouse ? 'Sửa kho ' + warehouse.code : 'Kho mới';
        elWhCode.value = warehouse ? warehouse.code : '';
        elWhName.value = warehouse ? warehouse.name : '';
        elWhForm.hidden = false;
        elWhCode.focus();
    }

    function hideWarehouseForm() {
        elWhForm.hidden = true;
        state.editingWarehouse = null;
    }

    elWhCancel.addEventListener('click', hideWarehouseForm);

    elWhSave.addEventListener('click', function () {
        var code = elWhCode.value.trim();
        var name = elWhName.value.trim();

        if (!code || !name) {
            toast('warning', 'Nhập mã kho và tên kho trước khi lưu.');
            return;
        }

        elWhSave.disabled = true;

        post(CFG.saveWarehouse, {
            id: state.editingWarehouse ? state.editingWarehouse.id : null,
            code: code,
            name: name
        }).then(function (result) {
            elWhSave.disabled = false;
            if (!result.ok) {
                toast('error', result.data.message || 'Không lưu được kho.');
                return;
            }

            toast('success', result.data.message);
            hideWarehouseForm();
            loadWarehouses();
        });
    });

    /* ---------- cấp 2: kệ ---------- */

    function loadShelves() {
        elShelves.innerHTML = '<p class="sc-note">Đang tải danh sách kệ...</p>';

        return fetch(CFG.shelves + '?warehouse_id=' + encodeURIComponent(state.warehouse.id))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                state.shelves = data.shelves;
                renderShelves();
            });
    }

    function renderShelves() {
        if (!state.shelves.length) {
            elShelves.innerHTML = '<p class="sc-note">Kho này chưa có kệ nào. Bấm "Kệ mới" để tạo.</p>';
            return;
        }

        var list = state.shelves.filter(matches);
        if (!list.length) {
            elShelves.innerHTML = '<p class="sc-note">Không có kệ nào khớp "' + esc(elFilter.value) + '".</p>';
            return;
        }

        elShelves.innerHTML = list.map(function (shelf) {
            return tileMarkup(shelf, {
                icon: 'fa-layer-group',
                meta: shelf.tiers.toLocaleString() + ' tầng · ' + shelf.total.toLocaleString() + ' vị trí',
                action: 'Sửa cấu trúc',
                editable: false
            });
        }).join('');
    }

    elShelves.addEventListener('click', function (event) {
        var card = event.target.closest('.sc-tile');
        if (card) {
            loadShelf(card.dataset.id);
        }
    });

    elAdd.addEventListener('click', function () {
        if (state.view === 'warehouses') {
            openWarehouseForm(null);
        } else if (state.view === 'shelves') {
            startCreate();
        } else if (state.view === 'create') {
            addNewBlock();
        }
    });

    elAddBlock.addEventListener('click', addNewBlock);

    /* ---------- danh sách khối kệ ---------- */

    function clearBlocks() {
        state.blocks.forEach(function (block) { block.root.remove(); });
        state.blocks = [];
    }

    function startCreate() {
        show('create');
        addNewBlock();
        setDirty(false);
    }

    function addNewBlock() {
        if (state.blocks.length >= CFG.maxBatchShelves) {
            toast('warning', 'Một lượt tạo tối đa ' + CFG.maxBatchShelves + ' kệ. Hãy lưu bớt rồi tạo tiếp.');
            return;
        }

        var previous = state.blocks[state.blocks.length - 1];
        var block = createBlock('new');

        if (previous) {
            // Kệ sau thường nối tiếp mã kệ trước (A8.01 rồi A8.02), nên gợi ý sẵn.
            block.el.name.value = previous.el.name.value;
            var suggested = nextCode(previous.el.code.value.trim());
            if (suggested) {
                block.el.code.value = suggested;
            }
        } else {
            block.el.name.value = 'Kệ {ma}';
        }

        elBlocks.appendChild(block.root);
        state.blocks.push(block);
        block.openPicker();
        block.refreshPreview();
        renumberBlocks();
        blocksChanged();

        if (previous) {
            setDirty(true);
            block.root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        block.el.code.focus({ preventScroll: true });
    }

    function removeBlock(block) {
        var index = state.blocks.indexOf(block);
        if (index < 0) return;

        block.root.remove();
        state.blocks.splice(index, 1);
        renumberBlocks();
        blocksChanged();
        setDirty(true);
    }

    function renumberBlocks() {
        state.blocks.forEach(function (block, index) {
            block.setTitle(index + 1);
            if (block.el.remove) {
                block.el.remove.hidden = state.blocks.length < 2;
            }
        });
    }

    // Chạy lại mỗi khi một kệ đổi mã hoặc cấu trúc: cộng tổng và đánh dấu mã trùng giữa
    // các kệ ngay trên màn hình, trước khi máy chủ phải từ chối.
    function blocksChanged() {
        if (state.view !== 'create') return;

        var owners = {};
        state.blocks.forEach(function (block) {
            var code = block.code();
            if (code) owners[code] = (owners[code] || 0) + 1;
        });

        var cells = 0;
        state.blocks.forEach(function (block) {
            block.duplicate = !!block.code() && owners[block.code()] > 1;
            block.paintPreview();
            cells += block.cells();
        });

        elTotal.textContent = state.blocks.length + ' kệ · ' + cells.toLocaleString() + ' vị trí';
    }

    /* ---------- một khối kệ: form + lưới chọn nhanh + lưới tầng ---------- */

    function blockTemplate(isNew) {
        return '<section class="sc-block' + (isNew ? ' is-new' : '') + '">'
            + '<div class="sc-block-head">'
            +   '<span class="sc-block-title"></span>'
            +   (isNew
                    ? '<button type="button" class="btn btn-sm btn-link text-danger sc-block-remove" hidden>'
                        + '<i class="fas fa-times mr-1"></i>Bỏ kệ này</button>'
                    : '')
            + '</div>'
            + '<div class="sc-form">'
            +   '<div class="form-group mb-0">'
            +     '<label>Mã kệ <span class="text-danger">*</span></label>'
            +     '<input type="text" class="form-control form-control-sm sc-f-code" placeholder="VD: A8.01">'
            +   '</div>'
            +   '<div class="form-group mb-0 sc-form-wide">'
            +     '<label>Tên kệ <span class="text-danger">*</span></label>'
            +     '<input type="text" class="form-control form-control-sm sc-f-name" placeholder="VD: Kệ A8.01">'
            +     (isNew ? '<small class="form-text text-muted">Dùng <code>{ma}</code> để chèn mã kệ vào tên.</small>' : '')
            +   '</div>'
            +   (isNew ? '<div class="sc-preview"></div>' : '')
            + '</div>'
            + '<div class="sc-picker" hidden>'
            +   '<div class="sc-picker-title">Kéo chuột để chọn số tầng (dọc) và số vị trí mỗi tầng (ngang), rồi bấm để xác nhận.</div>'
            +   '<div class="sc-picker-grid"></div>'
            +   '<div class="sc-picker-foot">'
            +     '<span class="sc-picker-readout">Chưa chọn</span>'
            +     '<span class="sc-picker-hint">Cần nhiều hơn khung này? Chọn tạm rồi chỉnh số ở bước sau.</span>'
            +   '</div>'
            + '</div>'
            + '<div class="sc-editor" hidden>'
            +   '<div class="sc-toolbar">'
            +     '<div class="sc-bulk">'
            +       '<label class="mb-0">Đặt mọi tầng thành</label>'
            +       '<input type="number" class="form-control form-control-sm sc-bulk-value" min="1" max="' + CFG.maxLocations + '">'
            +       '<button type="button" class="btn btn-sm btn-outline-secondary sc-bulk-apply">Áp dụng</button>'
            +     '</div>'
            +   '</div>'
            +   '<div class="sc-grid"></div>'
            +   '<div class="sc-foot">'
            +     '<button type="button" class="btn btn-sm btn-outline-secondary sc-add-tier">'
            +       '<i class="fas fa-plus mr-1"></i> Thêm tầng</button>'
            +     '<span class="sc-summary"></span>'
            +   '</div>'
            + '</div>'
            + '</section>';
    }

    function newTier(position, max) {
        return {
            position: position, max: max, origMax: 0, lastBusy: 0,
            busy: new Set(), off: new Set(), removed: false, wasRemoved: false,
            label: 'Tầng ' + String(position).padStart(2, '0')
        };
    }

    var PICKER_MAX_ROWS = Math.min(CFG.maxTiers, 30);
    var PICKER_MAX_COLS = Math.min(CFG.maxLocations, 60);

    function createBlock(mode) {
        var isNew = mode === 'new';
        var holder = document.createElement('div');
        holder.innerHTML = blockTemplate(isNew);
        var root = holder.firstElementChild;

        function find(selector) {
            return root.querySelector(selector);
        }

        var el = {
            title: find('.sc-block-title'),
            remove: find('.sc-block-remove'),
            code: find('.sc-f-code'),
            name: find('.sc-f-name'),
            preview: find('.sc-preview'),
            picker: find('.sc-picker'),
            pickerGrid: find('.sc-picker-grid'),
            readout: find('.sc-picker-readout'),
            editor: find('.sc-editor'),
            grid: find('.sc-grid'),
            summary: find('.sc-summary'),
            addTier: find('.sc-add-tier'),
            bulkValue: find('.sc-bulk-value'),
            bulkApply: find('.sc-bulk-apply')
        };

        // tiers: [{ position, max, origMax, lastBusy, busy:Set, off:Set, removed, wasRemoved, label }]
        var block = {
            mode: mode, root: root, el: el,
            shelfId: null, tiers: [], duplicate: false,
            previewData: null, previewTimer: null
        };

        var pickerRows = 6;
        var pickerCols = 20;
        // Trong lúc kéo, cỡ ô phải đứng yên: nếu để nó co giãn theo số cột thì mốc quy đổi
        // từ toạ độ chuột ra số ô đổi liên tục và mép kéo bị giật.
        var lockedSize = null;

        function changed() {
            setDirty(true);
            if (isNew) blocksChanged();
        }

        block.setTitle = function (number) {
            el.title.textContent = isNew ? 'Kệ mới #' + number : 'Cấu trúc kệ';
        };

        block.code = function () {
            return el.code.value.trim();
        };

        block.cells = function () {
            return block.tiers
                .filter(function (tier) { return !tier.removed; })
                .reduce(function (sum, tier) { return sum + tier.max; }, 0);
        };

        block.tierPayload = function () {
            return block.tiers
                .filter(function (tier) { return !tier.removed; })
                .map(function (tier) { return { position: tier.position, max_locations: tier.max }; });
        };

        /* xem trước mã kệ */

        // Kết quả máy chủ có thể là của lần gõ trước, nên chỉ tin khi khớp đúng mã đang nhập.
        block.taken = function () {
            var data = block.previewData;
            return !!data && data.code === block.code() && data.taken;
        };

        block.paintPreview = function () {
            if (!el.preview) return;

            var code = block.code();
            root.classList.remove('has-error');

            function bad(html) {
                el.preview.className = 'sc-preview is-bad';
                el.preview.innerHTML = html;
                root.classList.add('has-error');
            }

            if (!code) {
                el.preview.className = 'sc-preview';
                el.preview.textContent = 'Nhập mã kệ để xem trước.';
                return;
            }

            if (block.duplicate) {
                bad('Mã <b>' + esc(code) + '</b> trùng với một kệ mới khác trong lượt này.');
                return;
            }

            if (block.taken()) {
                bad('Mã <b>' + esc(code) + '</b> đã tồn tại. Hãy đổi mã kệ.');
                return;
            }

            var cells = block.cells();
            el.preview.className = 'sc-preview';
            el.preview.innerHTML = 'Sẽ tạo kệ <b>' + esc(code) + '</b>'
                + (cells ? ' với <b>' + cells.toLocaleString() + ' vị trí</b>.' : ' — chưa chọn số tầng.');
        };

        block.refreshPreview = function () {
            if (!el.preview) return;

            clearTimeout(block.previewTimer);
            var code = block.code();

            if (!code) {
                block.previewData = null;
                return;
            }

            block.previewTimer = setTimeout(function () {
                fetch(CFG.previewCodes + '?code=' + encodeURIComponent(code))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        block.previewData = data;
                        block.paintPreview();
                    });
            }, 250);
        };

        if (isNew) {
            el.code.addEventListener('input', function () { block.refreshPreview(); changed(); });
            el.name.addEventListener('input', function () { setDirty(true); });
            el.remove.addEventListener('click', function () { removeBlock(block); });
        } else {
            el.code.addEventListener('input', function () { setDirty(true); renderCrumbs(); });
            el.name.addEventListener('input', function () { setDirty(true); });
        }

        /* lưới chọn nhanh kiểu Word */

        block.openPicker = function () {
            pickerRows = 6;
            pickerCols = 20;
            el.picker.hidden = false;
            el.editor.hidden = true;
            el.readout.textContent = 'Chưa chọn';
            drawPicker(0, 0);
        };

        function drawPicker(hoverRow, hoverCol) {
            el.pickerGrid.style.setProperty('--cols', pickerCols);
            var html = '';
            for (var r = 1; r <= pickerRows; r++) {
                for (var c = 1; c <= pickerCols; c++) {
                    var on = r <= hoverRow && c <= hoverCol ? ' is-on' : '';
                    html += '<span class="sc-pcell' + on + '" data-r="' + r + '" data-c="' + c + '"></span>';
                }
            }
            el.pickerGrid.innerHTML = html;
        }

        function highlightPicker(hoverRow, hoverCol) {
            el.pickerGrid.querySelectorAll('.sc-pcell').forEach(function (cell) {
                cell.classList.toggle('is-on',
                    Number(cell.dataset.r) <= hoverRow && Number(cell.dataset.c) <= hoverCol);
            });
        }

        el.pickerGrid.addEventListener('mousemove', function (event) {
            var cell = event.target.closest('.sc-pcell');
            if (!cell) return;

            var r = Number(cell.dataset.r);
            var c = Number(cell.dataset.c);

            // Nở thêm khi con trỏ chạm mép, giống lưới chèn bảng của Word.
            var grew = false;
            if (r === pickerRows && pickerRows < PICKER_MAX_ROWS) { pickerRows = Math.min(pickerRows + 2, PICKER_MAX_ROWS); grew = true; }
            if (c === pickerCols && pickerCols < PICKER_MAX_COLS) { pickerCols = Math.min(pickerCols + 5, PICKER_MAX_COLS); grew = true; }

            if (grew) {
                drawPicker(r, c);
            } else {
                highlightPicker(r, c);
            }

            el.readout.textContent = r + ' tầng × ' + c + ' vị trí = ' + (r * c) + ' ô';
        });

        el.pickerGrid.addEventListener('click', function (event) {
            var cell = event.target.closest('.sc-pcell');
            if (!cell) return;

            var rows = Number(cell.dataset.r);
            var cols = Number(cell.dataset.c);

            block.tiers = [];
            for (var position = 1; position <= rows; position++) {
                block.tiers.push(newTier(position, cols));
            }

            el.picker.hidden = true;
            el.editor.hidden = false;
            block.render();
            changed();
        });

        /* lưới tầng */

        function cellSize(columns) {
            if (lockedSize) return lockedSize;
            var available = Math.max(el.grid.clientWidth - 190, 260);
            return Math.max(9, Math.min(Math.floor(available / columns) - 2, 26));
        }

        block.render = function () {
            var visible = block.tiers.filter(function (tier) { return !(tier.removed && tier.origMax === 0); });
            if (!visible.length) {
                el.grid.innerHTML = '<p class="sc-note">Chưa có tầng nào. Bấm "Thêm tầng" để bắt đầu.</p>';
                el.summary.textContent = '';
                return;
            }

            var columns = 1;
            visible.forEach(function (tier) {
                columns = Math.max(columns, tier.max, tier.origMax);
            });

            var size = cellSize(columns);
            var rows = visible.slice().sort(function (a, b) { return b.position - a.position; });

            el.grid.innerHTML = rows.map(rowMarkup).join('');
            el.grid.style.setProperty('--sc-cell', size + 'px');
            el.grid.querySelectorAll('.sc-cells').forEach(function (node) {
                node.style.setProperty('--cols', node.dataset.cols);
            });

            updateSummary();
        };

        function updateSummary() {
            var active = block.tiers.filter(function (tier) { return !tier.removed; });
            el.summary.textContent = active.length + ' tầng · ' + block.cells() + ' vị trí';
        }

        function cellsMarkup(tier) {
            var width = Math.max(tier.max, tier.origMax);
            var markup = '';

            for (var p = 1; p <= width; p++) {
                var cls;
                if (tier.removed || p > tier.max) {
                    cls = 'is-cut';
                } else if (p > tier.origMax) {
                    cls = 'is-new';
                } else if (tier.busy.has(p)) {
                    cls = 'is-busy';
                } else if (tier.off.has(p)) {
                    cls = 'is-off';
                } else {
                    cls = 'is-free';
                }
                markup += '<span class="sc-cell ' + cls + '"></span>';
            }

            return markup;
        }

        // Kéo một hàng chỉ đổi hàng đó, nên vẽ lại mình nó thay vì dựng lại cả lưới.
        function renderRow(tier) {
            var row = el.grid.querySelector('.sc-row[data-pos="' + tier.position + '"]');
            if (!row) {
                block.render();
                return;
            }

            var cells = row.querySelector('.sc-cells');
            var width = Math.max(tier.max, tier.origMax);
            cells.dataset.cols = width;
            cells.style.setProperty('--cols', width);
            cells.innerHTML = cellsMarkup(tier);
            row.querySelector('.sc-num').value = tier.max;
            updateSummary();
        }

        function rowMarkup(tier) {
            var width = Math.max(tier.max, tier.origMax);
            var note = tier.lastBusy ? ' (ô ' + tier.lastBusy + ' đang có hồ sơ)' : '';

            return '<div class="sc-row' + (tier.removed ? ' is-removed' : '') + '" data-pos="' + tier.position + '">'
                + '<span class="sc-row-label" title="' + esc(tier.label) + '">' + esc(tier.label) + '</span>'
                + '<div class="sc-cells" data-cols="' + width + '">' + cellsMarkup(tier) + '</div>'
                + '<button type="button" class="sc-handle" data-pos="' + tier.position + '"'
                + ' title="Kéo để đổi số vị trí' + esc(note) + '"></button>'
                + '<input type="number" class="sc-num" data-pos="' + tier.position + '" min="1" max="' + CFG.maxLocations
                + '" value="' + tier.max + '"' + (tier.removed ? ' disabled' : '') + '>'
                + '<button type="button" class="sc-drop" data-pos="' + tier.position + '"'
                + ' title="' + (tier.removed ? 'Dùng lại tầng này' : 'Gỡ tầng này') + '">'
                + '<i class="fas fa-' + (tier.removed ? 'undo' : 'times') + '"></i></button>'
                + '</div>';
        }

        function findTier(position) {
            return block.tiers.find(function (tier) { return tier.position === Number(position); });
        }

        // Không cho thu nhỏ qua ô cuối cùng đang chứa hồ sơ: thao tác sai bị chặn ngay ở tay.
        function clampMax(tier, value) {
            var floor = Math.max(1, tier.lastBusy);
            return Math.min(Math.max(value, floor), CFG.maxLocations);
        }

        el.grid.addEventListener('pointerdown', function (event) {
            var handle = event.target.closest('.sc-handle');
            if (!handle) return;

            var tier = findTier(handle.dataset.pos);
            if (!tier || tier.removed) return;

            var cells = handle.parentElement.querySelector('.sc-cells');
            var left = cells.getBoundingClientRect().left;
            var size = parseFloat(getComputedStyle(el.grid).getPropertyValue('--sc-cell')) || 14;

            lockedSize = size;
            handle.classList.add('is-dragging');
            handle.setPointerCapture(event.pointerId);
            event.preventDefault();

            function onMove(moveEvent) {
                var next = clampMax(tier, Math.round((moveEvent.clientX - left) / (size + 2)));
                if (next === tier.max) return;
                tier.max = next;
                setDirty(true);
                renderRow(tier);
            }

            function onUp() {
                handle.classList.remove('is-dragging');
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                lockedSize = null;
                block.render();
                changed();
            }

            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
        });

        el.grid.addEventListener('change', function (event) {
            var input = event.target.closest('.sc-num');
            if (!input) return;

            var tier = findTier(input.dataset.pos);
            if (!tier) return;

            var next = clampMax(tier, Number(input.value) || 1);
            if (next !== Number(input.value)) {
                toast('warning', 'Tầng ' + tier.position + ' không thể nhỏ hơn ' + next
                    + ' vì ô ' + tier.lastBusy + ' đang chứa hồ sơ.');
            }
            tier.max = next;
            block.render();
            changed();
        });

        el.grid.addEventListener('click', function (event) {
            var drop = event.target.closest('.sc-drop');
            if (!drop) return;

            var tier = findTier(drop.dataset.pos);
            if (!tier) return;

            if (!tier.removed && tier.lastBusy) {
                toast('warning', tier.label + ' đang chứa hồ sơ (tới ô ' + tier.lastBusy
                    + '), không gỡ được. Hãy chuyển hồ sơ đi trước.');
                return;
            }

            if (isNew) {
                // Kệ mới chưa có gì để khoá, nên gỡ là bỏ hẳn rồi đánh số lại; để nguyên thì
                // gỡ tầng giữa sẽ tạo kệ có "Tầng 01", "Tầng 03" mà thiếu "Tầng 02".
                block.tiers = block.tiers
                    .filter(function (item) { return item !== tier; })
                    .sort(function (a, b) { return a.position - b.position; });
                block.tiers.forEach(function (item, index) {
                    item.position = index + 1;
                    item.label = 'Tầng ' + String(index + 1).padStart(2, '0');
                });
            } else {
                tier.removed = !tier.removed;
            }

            block.render();
            changed();
        });

        el.addTier.addEventListener('click', function () {
            var highest = block.tiers.reduce(function (max, tier) { return Math.max(max, tier.position); }, 0);
            if (highest >= CFG.maxTiers) {
                toast('warning', 'Tối đa ' + CFG.maxTiers + ' tầng mỗi kệ.');
                return;
            }

            var last = block.tiers.filter(function (tier) { return !tier.removed; }).pop();
            block.tiers.push(newTier(highest + 1, last ? last.max : 20));
            block.render();
            changed();
        });

        el.bulkApply.addEventListener('click', function () {
            var value = Number(el.bulkValue.value);
            if (!value) return;

            var blocked = [];
            block.tiers.forEach(function (tier) {
                if (tier.removed) return;
                var next = clampMax(tier, value);
                if (next !== value) blocked.push(tier.label + ' (tối thiểu ' + next + ')');
                tier.max = next;
            });

            block.render();
            changed();

            if (blocked.length) {
                toast('warning', 'Một số tầng không xuống được ' + value + ' vì đang chứa hồ sơ: ' + blocked.join(', '));
            }
        });

        block.setTitle(1);
        return block;
    }

    window.addEventListener('resize', function () {
        state.blocks.forEach(function (block) {
            if (!block.el.editor.hidden) block.render();
        });
    });

    /* ---------- sửa một kệ có sẵn ---------- */

    function loadShelf(shelfId) {
        show('editor');
        clearBlocks();
        elNote.textContent = 'Đang tải cấu trúc...';
        elNote.hidden = false;

        fetch(CFG.detail + '?shelf_id=' + encodeURIComponent(shelfId))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                clearBlocks();

                var block = createBlock('edit');
                block.shelfId = data.shelf.id;
                block.el.code.value = data.shelf.code;
                block.el.name.value = data.shelf.name;
                block.tiers = data.tiers.map(function (tier) {
                    return {
                        position: Number(tier.position) || 0,
                        max: Number(tier.max_locations) || 0,
                        origMax: Number(tier.max_locations) || 0,
                        lastBusy: Number(tier.last_busy) || 0,
                        busy: new Set(tier.busy || []),
                        off: new Set(tier.off || []),
                        removed: Number(tier.status_id) !== 1,
                        wasRemoved: Number(tier.status_id) !== 1,
                        label: tier.name || tier.code
                    };
                }).filter(function (tier) { return tier.position > 0; });

                elBlocks.appendChild(block.root);
                state.blocks.push(block);
                block.el.editor.hidden = false;

                elNote.hidden = true;
                setDirty(false);
                renderCrumbs();
                block.render();
            })
            .catch(function () {
                elNote.textContent = 'Không tải được cấu trúc kệ.';
            });
    }

    elReset.addEventListener('click', function () {
        if (state.view === 'editor' && state.blocks[0]) {
            loadShelf(state.blocks[0].shelfId);
            return;
        }

        if (state.dirty && !confirm('Bỏ tất cả kệ đang khai báo?')) return;
        show('shelves');
        loadShelves();
    });

    /* ---------- lưu ---------- */

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            })
            .catch(function () {
                return { ok: false, data: { message: 'Không kết nối được máy chủ.' } };
            });
    }

    elSave.addEventListener('click', function () {
        if (state.view === 'create') {
            saveNewShelves();
        } else {
            saveExistingShelf();
        }
    });

    function saveNewShelves() {
        var groups = [];
        var problems = [];
        var firstBad = null;

        state.blocks.forEach(function (block, index) {
            var code = block.code();
            var name = block.el.name.value.trim();
            var tiers = block.tierPayload();
            var problem = null;

            if (!code) problem = 'chưa nhập mã kệ';
            else if (!name) problem = 'chưa nhập tên kệ';
            else if (block.duplicate) problem = 'mã ' + code + ' trùng với kệ mới khác';
            else if (block.taken()) problem = 'mã ' + code + ' đã tồn tại';
            else if (!tiers.length) problem = 'chưa chọn số tầng và vị trí';

            block.root.classList.toggle('has-error', !!problem);
            if (problem) {
                problems.push('Kệ mới #' + (index + 1) + ': ' + problem);
                firstBad = firstBad || block;
            }

            groups.push({ code: code, name: name, tiers: tiers });
        });

        if (problems.length) {
            toast('warning', problems.join(' • '));
            firstBad.root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        var cells = state.blocks.reduce(function (sum, block) { return sum + block.cells(); }, 0);

        Swal.fire({
            icon: 'question',
            title: 'Tạo ' + groups.length + ' kệ?',
            text: 'Tổng ' + cells.toLocaleString() + ' vị trí sẽ được sinh ra.',
            showCancelButton: true, confirmButtonText: 'Tạo', cancelButtonText: 'Huỷ'
        }).then(function (choice) {
            if (!choice.isConfirmed) return;

            elSave.disabled = true;
            post(CFG.applyMany, { warehouse_id: state.warehouse.id, groups: groups }).then(function (result) {
                elSave.disabled = false;
                if (!result.ok) {
                    toast('error', result.data.message || 'Không tạo được.');
                    return;
                }

                toast('success', result.data.message);
                setDirty(false);
                show('shelves');
                loadShelves();
            });
        });
    }

    function saveExistingShelf() {
        var block = state.blocks[0];
        if (!block) return;

        var code = block.el.code.value.trim();
        var name = block.el.name.value.trim();

        if (!code || !name) {
            toast('warning', 'Nhập mã kệ và tên kệ trước khi lưu.');
            return;
        }

        var tiers = block.tierPayload();
        if (!tiers.length) {
            toast('warning', 'Kệ phải có ít nhất một tầng.');
            return;
        }

        var dropped = block.tiers.filter(function (tier) { return tier.removed && !tier.wasRemoved; });
        var shrunk = block.tiers.filter(function (tier) { return !tier.removed && tier.max < tier.origMax; });

        var warning = '';
        if (dropped.length) warning += 'Gỡ ' + dropped.length + ' tầng. ';
        if (shrunk.length) warning += 'Thu nhỏ ' + shrunk.length + ' tầng. ';
        if (warning) warning += 'Các vị trí bị loại sẽ bị khoá (không xoá) và không xếp hồ sơ vào được nữa.';

        var send = function () {
            elSave.disabled = true;

            post(CFG.apply, {
                shelf_id: block.shelfId,
                code: code,
                name: name,
                warehouse_id: state.warehouse.id,
                tiers: tiers
            }).then(function (result) {
                elSave.disabled = false;
                if (!result.ok) {
                    toast('error', result.data.message || 'Không lưu được.');
                    return;
                }

                toast('success', result.data.message);
                setDirty(false);
                loadShelf(result.data.shelf_id);
            });
        };

        if (warning) {
            Swal.fire({
                icon: 'warning', title: 'Xác nhận thu nhỏ', text: warning,
                showCancelButton: true, confirmButtonText: 'Vẫn lưu', cancelButtonText: 'Huỷ'
            }).then(function (choice) { if (choice.isConfirmed) send(); });
        } else {
            send();
        }
    }

    window.addEventListener('beforeunload', function (event) {
        if (state.dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    show('warehouses');
    loadWarehouses();
})();
</script>
@endverbatim
