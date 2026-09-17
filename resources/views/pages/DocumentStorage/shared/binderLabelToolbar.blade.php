{{--
| TÀI LIỆU - THANH CÔNG CỤ TRANG IN NHÃN GÁY BINDER
|
| Khác thanh công cụ nhãn QR tài liệu (labelToolbar.blade.php): nhãn này in trên
| giấy A4 thường bằng máy in HP qua hộp thoại in chuẩn của trình duyệt (window.print),
| KHÔNG dùng Zebra Browser Print. Chỉ có ô chọn số lượng nhãn + nút In.
|
| MỖI LẦN IN ĐỀU GHI AUDIT LOG trước khi in (POST $logUrl). Ghi hỏng thì KHÔNG in.
| Bấm Ctrl+P cũng ghi (bắt qua beforeprint).
|
| Biến truyền vào:
|   $recordIds   id bản ghi đang in, nối bằng dấu phẩy (1 hoặc nhiều)
|   $docCount    số bản ghi (nhãn khác nhau) trên trang
|   $logUrl      route ghi audit log in nhãn (POST ids = danh sách id)
|   $backUrl     route quay lại màn hình danh sách
|   $maxCopies   số bản tối đa cho MỖI bản ghi
|   $sizeUrls    (tuỳ chọn) [khổ cm => url] - hiện ô chọn khổ gáy, đổi khổ thì nạp lại trang
|   $currentSize (tuỳ chọn) khổ gáy đang in
--}}

@php($sizeUrls = $sizeUrls ?? [])
@php($docCount = $docCount ?? 1)

<style>
    .toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        padding: 14px;
        background: #fff;
        border-bottom: 1px solid #d7dee6;
    }

    .toolbar button,
    .toolbar a {
        border: 0;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all .2s ease;
    }

    .toolbar .go {
        background: #2E7BC4;
        color: #fff;
    }

    .toolbar .go:hover:not(:disabled) {
        background: #1F5E9E;
        transform: translateY(-1px);
    }

    .toolbar .back {
        background: #EAF3FC;
        color: #1F5E9E;
    }

    .toolbar .back:hover {
        background: #D9E9F9;
    }

    .toolbar .note {
        width: 100%;
        margin: 0;
        text-align: center;
        color: #64748B;
        font-size: 12px;
        font-weight: 400;
    }

    .copies {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 14px;
        background: #EAF3FC;
        border-radius: 8px;
    }

    .copies select {
        height: 30px;
        padding: 0 6px;
        border: 1px solid #9CC7EE;
        border-radius: 8px;
        background: #fff;
        color: #2D3748;
        font-size: 14px;
        font-weight: 700;
    }

    .copies label {
        font-size: 14px;
        font-weight: 600;
        color: #1F5E9E;
        white-space: nowrap;
    }

    .copies .step {
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: 8px;
        background: #fff;
        color: #1F5E9E;
        font-size: 17px;
        font-weight: 700;
        line-height: 1;
    }

    .copies .step:hover {
        background: #2E7BC4;
        color: #fff;
        transform: none;
    }

    .copies input {
        width: 68px;
        height: 30px;
        padding: 0 6px;
        border: 1px solid #9CC7EE;
        border-radius: 8px;
        background: #fff;
        color: #2D3748;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
    }

    .toolbar .log-state {
        width: 100%;
        margin: 0;
        text-align: center;
        font-size: 12.5px;
        font-weight: 600;
    }

    .toolbar .log-state.ok { color: #16A34A; }
    .toolbar .log-state.fail { color: #DC2626; }

    @media print {
        .toolbar {
            display: none !important;
        }
    }
</style>

<div class="toolbar">
    @if (count($sizeUrls) > 1)
        <div class="copies">
            <label for="binderSize">Khổ gáy</label>
            <select id="binderSize" onchange="window.location.href = this.value">
                @foreach ($sizeUrls as $cm => $url)
                    <option value="{{ $url }}" {{ (int) $currentSize === (int) $cm ? 'selected' : '' }}>{{ $cm }} cm</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($docCount > 1)
        <div class="copies">
            <label>{{ $docCount }} tài liệu</label>
        </div>
    @endif

    <div class="copies">
        <label for="copies">{{ $docCount > 1 ? 'Số bản / tài liệu' : 'Số lượng nhãn' }}</label>
        <button type="button" class="step" id="copiesMinus" title="Bớt 1 nhãn">&minus;</button>
        <input type="number" id="copies" name="copies" value="1" min="1" max="{{ $maxCopies }}" step="1"
            title="Số nhãn sẽ in, tối đa {{ $maxCopies }} nhãn một lần">
        <button type="button" class="step" id="copiesPlus" title="Thêm 1 nhãn">+</button>
    </div>

    <button type="button" class="go" id="btnPrint">In nhãn</button>
    <a class="back" href="{{ $backUrl }}">Quay lại</a>

    <p class="note">
        Mỗi trang <b>A4</b> in 1 hàng nhãn, cắt theo đường chấm. Trong hộp thoại in: chọn máy in HP, khổ
        <b>A4</b>, Scale <b>100%</b> (không chọn Fit to page), bỏ tick <b>Headers and footers</b>.
        Mỗi lần in đều ghi vào <b>Audit Trail</b>.
    </p>

    <p class="log-state" id="logState"></p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var MAX = {{ (int) $maxCopies }};
        var LOG_URL = '{{ $logUrl }}';
        var RECORD_IDS = @json((string) $recordIds);
        var TOKEN = '{{ csrf_token() }}';

        var stack = document.getElementById('labelStack');
        var input = document.getElementById('copies');
        var btnPrint = document.getElementById('btnPrint');
        var logState = document.getElementById('logState');

        // Bản gốc: mỗi bản ghi 1 ô nhãn (.cut-cell) + 1 hàng rỗng (.sheet-row).
        // Nhân mỗi nhãn theo số bản (các bản cùng tài liệu đứng liền nhau) rồi chia hàng.
        var PER_ROW = parseInt(stack.dataset.perRow, 10) || 3;
        var masters = Array.prototype.map.call(stack.querySelectorAll('.cut-cell'), function (el) {
            return el.cloneNode(true);
        });
        var rowTemplate = stack.querySelector('.sheet-row').cloneNode(true);
        rowTemplate.querySelector('.cells').innerHTML = '';
        var alreadyLogged = false; // tránh ghi nhật ký 2 lần khi bấm nút rồi beforeprint lại chạy

        function copies() {
            var n = parseInt(input.value, 10);
            if (isNaN(n) || n < 1) n = 1;
            return n > MAX ? MAX : n;
        }

        function total() {
            return copies() * masters.length;
        }

        function render() {
            var n = copies();
            input.value = n;
            stack.innerHTML = '';
            var cells = null;
            var i = 0;
            masters.forEach(function (master) {
                for (var c = 0; c < n; c++, i++) {
                    if (i % PER_ROW === 0) {
                        var row = rowTemplate.cloneNode(true);
                        stack.appendChild(row);
                        cells = row.querySelector('.cells');
                    }
                    cells.appendChild(master.cloneNode(true));
                }
            });
        }

        function say(message, isOk) {
            logState.textContent = message;
            logState.className = 'log-state ' + (isOk ? 'ok' : 'fail');
        }

        input.addEventListener('input', function () {
            if (input.value !== '') render();
        });
        input.addEventListener('change', render);
        document.getElementById('copiesMinus').addEventListener('click', function () {
            input.value = copies() - 1;
            render();
        });
        document.getElementById('copiesPlus').addEventListener('click', function () {
            input.value = copies() + 1;
            render();
        });

        function logPrint(n, beacon) {
            var body = new FormData();
            body.append('_token', TOKEN);
            body.append('ids', RECORD_IDS);
            body.append('copies', n);

            if (beacon && navigator.sendBeacon) {
                return Promise.resolve(navigator.sendBeacon(LOG_URL, body));
            }

            return fetch(LOG_URL, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) { return response.ok; });
        }

        btnPrint.addEventListener('click', function () {
            var n = copies();
            btnPrint.disabled = true;
            say('Đang ghi nhật ký in nhãn…', true);

            logPrint(n, false).then(function (ok) {
                btnPrint.disabled = false;

                if (!ok) {
                    say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
                    return;
                }

                say('Đã ghi nhật ký in ' + total() + ' nhãn vào Audit Trail.', true);
                alreadyLogged = true;
                window.print();
            }).catch(function () {
                btnPrint.disabled = false;
                say('Chưa ghi được nhật ký in nhãn nên chưa in. Vui lòng thử lại.', false);
            });
        });

        // In bằng Ctrl+P / menu trình duyệt: vẫn phải có nhật ký
        window.addEventListener('beforeprint', function () {
            if (alreadyLogged) return;
            alreadyLogged = true;
            logPrint(copies(), true);
            say('Đã ghi nhật ký in ' + total() + ' nhãn vào Audit Trail.', true);
        });

        window.addEventListener('afterprint', function () {
            alreadyLogged = false;
        });

        render();
    });
</script>
