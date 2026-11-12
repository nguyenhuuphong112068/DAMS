{{--
|--------------------------------------------------------------------------
| THẺ | 3D | MẶT BẰNG CHO CÁC MÀN VỊ TRÍ LƯU TRỮ (Cấu Trúc Kho, Sơ Đồ Kho, Phân Chia Kệ Quản Lý)
|--------------------------------------------------------------------------
| Bọc nội dung sẵn có của trang (chế độ "Thẻ") cùng sơ đồ 3D từng kệ / Mặt bằng cả kho (pages/shared/zone3d).
| Trang đặt trong rootEl:
|   <div class="mz-head zv-head"><div class="mz-tools"></div></div>   (nút Thẻ | 3D | Mặt bằng + ô lọc nằm ở đây)
|   ...các khối nội dung "Thẻ" mang class mz-body (ẩn đi khi đang xem 3D / Mặt bằng)
| rồi gọi:
|   var views = window.damsZoneViews.mount(rootEl, {
|       modeKey: 'dams.xxx.zoneView',          // nhớ kiểu xem riêng của từng màn
|       focus: function () { return { warehouseId, shelfId }; },   // kho / kệ trang đang đứng
|       open: function (key, node) {...}       // bấm thùng hồ sơ (mặc định: hộp SweetAlert liệt kê hồ sơ)
|   });
|   views.refocus()      - trang vừa đổi kho / kệ ở chế độ Thẻ
|   views.invalidate()   - dữ liệu đổi (chuyển / huỷ hồ sơ, sửa cấu trúc): lần xem 3D sau tải lại
|   views.enable(bool)   - ẩn bộ nút + về Thẻ khi trang sang màn không áp dụng (vd. đang sửa một kệ)
| Dữ liệu (FloorPlanController::zoneView) chỉ tải khi người dùng bấm 3D / Mặt bằng lần đầu.
--}}
@include('pages.shared.zone3d')

@once
<style>
    .zv-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 12px;
    }

    .zv-head[hidden] {
        display: none !important;
    }

    .zv-head .mz-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .zv-head .mz-search {
        position: relative;
    }

    .zv-head .mz-search input {
        padding-left: 30px;
        min-width: 240px;
        border-radius: var(--border-radius-md);
        font-size: 0.85rem;
    }

    .zv-head .mz-search > i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-lighter);
    }

    /* ô lọc chỉ lọc sơ đồ 3D / Mặt bằng, chế độ Thẻ dùng ô tìm sẵn có của trang */
    .mz-root:not(.mz-mode-3d) .zv-head .mz-search {
        display: none;
    }

    .zv-loading {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .mz-mode-3d .mz-body {
        display: none !important;
    }

    .zv-swal-table {
        width: 100%;
        font-size: 0.82rem;
        text-align: left;
        border-collapse: collapse;
    }

    .zv-swal-table th,
    .zv-swal-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 6px 8px;
    }

    .zv-swal-table th {
        background: var(--primary-soft);
        color: var(--primary-dark);
    }
</style>

<script>
    (function() {
        'use strict';

        var DATA_URL = @json(route('pages.storageLocation.zoneView'));
        var STATE_LABELS = { ok: 'Đang lưu', warn: 'Sắp hết hạn lưu trữ', danger: 'Quá hạn lưu trữ' };

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        // Hộp chi tiết mặc định khi bấm thùng hồ sơ trên 3D / Mặt bằng.
        function defaultOpen(key, node) {
            if (!node || !node.items) return;
            var rows = node.items.map(function(x) {
                return '<tr><td><b>' + esc(x.code) + '</b></td><td>' +
                    (x.private ? '<i class="fas fa-lock mr-1"></i>Hồ sơ hạn chế' : esc(x.name || '—')) + '</td><td>' +
                    esc(x.private ? '' : (x.owner || '—')) + '</td><td>' + esc(x.expiry) +
                    (x.state !== 'ok' ? '<br><small style="color:' + (x.state === 'danger' ? '#B91C1C' : '#B45309') + '">' +
                        STATE_LABELS[x.state] + '</small>' : '') + '</td></tr>';
            }).join('');
            var html = '<div style="text-align:left;font-size:.82rem;color:#64748b;margin-bottom:8px">' + esc(node.path) + '</div>' +
                '<table class="zv-swal-table"><thead><tr><th>Mã hồ sơ</th><th>Tên hồ sơ</th><th>Người sở hữu</th><th>Hạn lưu trữ</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table>';
            if (window.Swal) {
                Swal.fire({ title: 'Vị trí ' + esc(node.code), html: html, width: 760, confirmButtonText: 'Đóng', confirmButtonColor: '#003A4F' });
            } else {
                window.alert(node.code + '\n' + node.items.map(function(x) { return x.code + ' - ' + (x.name || ''); }).join('\n'));
            }
        }

        // Máy chủ gửi gọn (FloorPlanController::zoneView): dựng lại đường dẫn Kho / Kệ / Tầng, chuỗi lọc và giá trị
        // mặc định của từng hồ sơ ở đây.
        function expand(zones, nodes) {
            function byId(list) {
                var map = {};
                (list || []).forEach(function(x) { map[x.id] = x; });
                return map;
            }
            var warehouses = byId(zones.warehouses), shelves = byId(zones.shelves), tiers = byId(zones.tiers);

            Object.keys(nodes).forEach(function(key) {
                var node = nodes[key];
                var tier = tiers[node.t] || {};
                var shelf = shelves[tier.shelf_id] || {};
                var wh = warehouses[shelf.warehouse_id] || {};
                node.path = (wh.name || 'Chưa gán kho') + ' / ' + (shelf.name || 'Chưa gán kệ') + ' / ' + (tier.name || 'Chưa gán tầng');
                node.items = (node.items || []).map(function(x) {
                    return {
                        code: x.code || '', name: x.name || '', owner: x.owner || '', private: !!x.private,
                        expiry: x.expiry || '—', state: x.state || 'ok'
                    };
                });
                node.search = (node.code + ' ' + node.path + ' ' + node.items.map(function(x) {
                    return x.code + ' ' + x.name + ' ' + x.owner;
                }).join(' ')).toLowerCase();
            });
            return nodes;
        }

        function mount(root, opts) {
            opts = opts || {};
            var tools = root.querySelector('.mz-tools');
            var head = root.querySelector('.zv-head');
            var modeKey = opts.modeKey || 'dams.zoneViews';
            var loaded = false, stale = false, loading = false, nodes = {};

            root.classList.add('mz-root');
            // Bộ nút giống hệt của zone3d (zone3d thấy đã có .mz-view thì không chèn thêm) + ô lọc sơ đồ
            tools.insertAdjacentHTML('afterbegin',
                '<div class="mz-search"><i class="fas fa-search"></i>' +
                '<input type="text" class="form-control form-control-sm" placeholder="Lọc sơ đồ: mã hồ sơ, tên, vị trí..." autocomplete="off"></div>' +
                '<div class="mz-view" role="group" aria-label="Kiểu sơ đồ">' +
                '<button type="button" data-mz-view="cards" class="is-active"><i class="fas fa-th-large mr-1"></i>Thẻ</button>' +
                '<button type="button" data-mz-view="3d" title="Xem 3D từng kệ"><i class="fas fa-cube mr-1"></i>3D</button>' +
                '<button type="button" data-mz-view="room" title="Xem cả kho theo mặt bằng"><i class="fas fa-vector-square mr-1"></i>Mặt bằng</button></div>');

            function focusNow() {
                return typeof opts.focus === 'function' ? opts.focus() : null;
            }

            function load(mode) {
                if (loading) return;
                loading = true;
                var note = document.createElement('span');
                note.className = 'zv-loading';
                note.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải sơ đồ...';
                tools.appendChild(note);

                fetch(DATA_URL, { headers: { Accept: 'application/json' } })
                    .then(function(r) {
                        if (!r.ok) throw new Error('Không tải được dữ liệu sơ đồ.');
                        return r.json();
                    })
                    .then(function(data) {
                        nodes = expand(data.zones, data.nodes || {});
                        loaded = true;
                        stale = false;
                        window.damsZone3d.init(root, {
                            zones: data.zones,
                            nodes: nodes,
                            modeKey: modeKey,
                            mode: mode,
                            focus: focusNow(),
                            open: function(key) { (opts.open || defaultOpen)(key, nodes[key]); }
                        });
                    })
                    .catch(function(error) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Không thực hiện được', text: error.message });
                    })
                    .then(function() {
                        loading = false;
                        note.remove();
                    });
            }

            // Bắt trước zone3d (pha capture): chưa có dữ liệu / dữ liệu cũ thì tải rồi mới chuyển kiểu xem.
            root.addEventListener('click', function(event) {
                var btn = event.target.closest('[data-mz-view]');
                if (!btn || !root.contains(btn)) return;
                var mode = btn.getAttribute('data-mz-view');
                if (mode === 'cards' || (loaded && !stale)) return;
                event.stopPropagation();
                load(mode);
            }, true);

            // Lần trước người dùng rời màn ở 3D / Mặt bằng thì mở lại đúng kiểu đó
            var saved = null;
            try { saved = localStorage.getItem(modeKey); } catch (err) { saved = null; }

            var api = {
                refocus: function() {
                    if (loaded) window.damsZone3d.focus(root, focusNow());
                },
                invalidate: function() {
                    if (!loaded) return;
                    stale = true;
                    // Đang xem 3D / Mặt bằng thì tải lại ngay
                    var mode = window.damsZone3d.mode(root);
                    if (mode !== 'cards') load(mode);
                },
                enable: function(on) {
                    if (head) head.hidden = !on;
                    if (!on && loaded && window.damsZone3d.mode(root) !== 'cards') window.damsZone3d.setMode(root, 'cards', true);
                },
                // Trang cần hiện lại nội dung Thẻ (vd. nhảy tới một ô từ ô tìm kiếm của trang)
                showCards: function() {
                    if (loaded && window.damsZone3d.mode(root) !== 'cards') window.damsZone3d.setMode(root, 'cards');
                },
                start: function() {
                    if (saved === '3d' || saved === 'room') load(saved);
                }
            };
            return api;
        }

        window.damsZoneViews = { mount: mount };
    })();
</script>
@endonce
