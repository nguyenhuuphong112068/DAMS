{{--
| NHÃN GÁY BINDER - TRANG IN A4
|
| Trang in độc lập, KHÔNG dùng layout.master. Mở từ màn Quản lý lưu trữ
| (DocumentController::binderLabel) cho 1 hoặc nhiều tài liệu chọn cùng lúc.
| In trên giấy A4 thường bằng máy in HP.
|
| $labels: mảng ['record' => {id, code, name, location_code, location_name,
| department_name}, 'qr' => QrCode::render(), 'qrValue' => chuỗi mã hoá].
|
| Bố cục theo mẫu Word "STE_Binder Label 5cm/7cm", mỗi nhãn 4 vùng từ trên xuống:
|   1. Logo STELLA
|   2. Nội dung: QR (mã vị trí) rồi TÊN HỒ SƠ chữ to in dọc, đọc từ dưới lên
|   3. Ô định khu: mã vị trí
|   4. Ô phòng ban
| Nhãn xếp thành hàng (3 nhãn/hàng khổ 5cm, 2 nhãn/hàng khổ 7cm), trên/dưới mỗi hàng
| có đường cắt nét chấm kèm hình kéo. Thanh công cụ (DocumentStorage.shared.binderLabelToolbar) nhân
| nhãn theo số lượng và chia hàng theo data-per-row.
--}}

@php
    $cfg = config('binder.label');
    $w = $size['width_mm'];
    $gap = $size['gap_mm'];
    $perRow = $size['per_row'];
    $h = $cfg['height_mm'];
    $logoH = $cfg['logo_height_mm'];
    $locH = $cfg['location_height_mm'];
    $deptH = $cfg['department_height_mm'];
    $contentH = $h - $logoH - $locH - $deptH;

    $qrSizeMm = $size['qr_size_mm'];

    // Cỡ chữ tự co theo độ dài để vừa ô (bề rộng ký tự đậm ~0.6em)
    $fit = fn(string $text, float $room, float $max) => round(min($max, $room / max(1, mb_strlen($text) * 0.6)), 2);

    $cards = array_map(function ($item) use ($fit, $qrSizeMm, $contentH, $w) {
        $r = $item['record'];
        $modules = $item['qr']['modules'] ?? 0;
        $border = $item['qr']['border'] ?? 0;
        $qrBoxMm = $modules ? round($qrSizeMm * ($modules + 2 * $border) / $modules, 2) : $qrSizeMm;

        $name = trim((string) $r->name) ?: '—';
        $nameRoom = $contentH - $qrBoxMm - 14;          // chiều dài dành cho chữ dọc
        $nameSize = $fit($name, $nameRoom, 9);
        if ($nameSize < 5.5) {
            // Tên dài: cho xuống 2 dòng dọc, giới hạn theo bề rộng nhãn
            $nameSize = round(min(7, $fit($name, $nameRoom * 2, 9), ($w - 10) / 2.5), 2);
        }

        $location = $r->location_code ?: ($r->location_name ?: '—');
        $dept = $r->department_name ?: '—';

        return [
            'qr' => $item['qr'],
            'qrValue' => $item['qrValue'],
            'qrBoxMm' => $qrBoxMm,
            'name' => $name,
            'nameSize' => $nameSize,
            'location' => $location,
            'locationSize' => $fit($location, $w - 6, 6),
            'dept' => $dept,
            'deptSize' => $fit($dept, $w - 4, 3),
        ];
    }, $labels);

    $pageTitle = count($labels) === 1
        ? 'Nhãn gáy binder - ' . $labels[0]['record']->code
        : 'Nhãn gáy binder - ' . count($labels) . ' tài liệu';
@endphp
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/iconstella.svg') }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #E9EEF3;
            color: #003A4F;
            font-family: Arimo, Arial, Helvetica, sans-serif;
        }

        /* Hàng lẻ (ít nhãn hơn) vẫn canh trái để đường cắt thẳng hàng giữa các trang */
        #labelStack {
            width: max-content;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10mm;
            padding: 18px;
        }

        /* ---------- Một hàng nhãn + đường cắt trên/dưới ---------- */
        /* padding-top chừa chỗ cho kéo + vạch cắt dọc nằm trên đường cắt ngang, để
           chúng vẫn nằm trong vùng in khi hàng bắt đầu ở đầu trang */
        .sheet-row {
            width: max-content;
            padding: 10mm 2mm 2mm 5mm;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .cut-line {
            position: relative;
            display: block;
            border-top: 0.25mm dotted #8A8A8A;
        }

        .cut-line::before {
            content: "\2702";
            position: absolute;
            left: -5mm;
            top: -2mm;
            font-family: "Segoe UI Symbol", "DejaVu Sans", sans-serif;
            font-size: 3.5mm;
            line-height: 1;
            color: #000;
        }

        .cells {
            display: flex;
            gap: {{ $gap }}mm;
            padding: 5mm 0;
        }

        .cut-cell { position: relative; }

        /* Vạch cắt dọc ở 2 mép nhãn, phía trên đường cắt ngang */
        .cut-cell::before,
        .cut-cell::after {
            content: "";
            position: absolute;
            top: -10mm;
            height: 5mm;
            border-left: 0.25mm dotted #8A8A8A;
        }

        .cut-cell::before { left: 0; }
        .cut-cell::after { right: 0; }

        .scissor {
            position: absolute;
            top: -14mm;
            font-family: "Segoe UI Symbol", "DejaVu Sans", sans-serif;
            font-size: 3.2mm;
            line-height: 1;
            color: #000;
            transform: translateX(-50%) rotate(90deg);
        }

        .scissor.l { left: 0; }
        .scissor.r { left: 100%; }

        /* ---------- Nhãn ---------- */
        .binder-label {
            width: {{ $w }}mm;
            height: {{ $h }}mm;
            display: flex;
            flex-direction: column;
            border: 0.3mm solid #CDC717;
            background: #fff;
            overflow: hidden;
        }

        .zone-logo {
            height: {{ $logoH }}mm;
            flex: none;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 0.3mm solid #CDC717;
        }

        .zone-logo img {
            width: {{ round(min($w - 12, 42), 1) }}mm;
            height: auto;
            display: block;
        }

        .zone-content {
            height: {{ $contentH }}mm;
            flex: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 4mm 2mm;
        }

        .qr-wrap { flex: none; }

        .qr-wrap svg { width: 100%; height: 100%; display: block; }

        .qr-empty {
            font-size: 2mm;
            font-weight: 700;
            color: #B91C1C;
            text-align: center;
        }

        /* Chữ dọc đọc từ dưới lên (giống textDirection btLr của Word) */
        .zone-name {
            flex: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-align: center;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: 0.2mm;
            word-break: break-word;
        }

        .zone-location {
            height: {{ $locH }}mm;
            flex: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 2mm;
            border-top: 0.3mm solid #CDC717;
            font-weight: 700;
            white-space: nowrap;
        }

        .zone-dept {
            height: {{ $deptH }}mm;
            flex: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1mm;
            border-top: 0.3mm solid #CDC717;
            font-weight: 700;
            white-space: nowrap;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 4mm;
            }

            html, body { background: #fff; }

            #labelStack { padding: 0; gap: 0; }

            .sheet-row {
                page-break-after: always;
                break-after: page;
            }

            .sheet-row:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>

<body>

    @include('pages.DocumentStorage.shared.binderLabelToolbar', [
        'recordIds' => $recordIds,
        'docCount' => count($cards),
        'logUrl' => $logUrl,
        'backUrl' => $backUrl,
        'maxCopies' => $maxCopies,
        'currentSize' => $currentSize ?? null,
        'sizeUrls' => $sizeUrls ?? [],
    ])

    {{-- Mỗi tài liệu 1 .cut-cell mẫu; thanh công cụ nhân theo số bản rồi chia thành .sheet-row --}}
    <div id="labelStack" data-per-row="{{ $perRow }}">
        <div class="sheet-row">
            <span class="cut-line"></span>
            <div class="cells">
                @foreach ($cards as $card)
                    <div class="cut-cell">
                        <span class="scissor l">&#9986;</span>
                        <span class="scissor r">&#9986;</span>
                        <div class="binder-label">
                            <div class="zone-logo">
                                <img src="{{ asset('img/logo/stella_binder_label.jpeg') }}" alt="STELLA">
                            </div>
                            <div class="zone-content">
                                <div class="qr-wrap" style="width: {{ $card['qrBoxMm'] }}mm; height: {{ $card['qrBoxMm'] }}mm;">
                                    @if (!empty($card['qr']['svg']))
                                        {!! $card['qr']['svg'] !!}
                                    @else
                                        <span class="qr-empty">Mã "{{ $card['qrValue'] }}" không tạo được QR</span>
                                    @endif
                                </div>
                                <div class="zone-name" style="font-size: {{ $card['nameSize'] }}mm">{{ $card['name'] }}</div>
                            </div>
                            <div class="zone-location" style="font-size: {{ $card['locationSize'] }}mm">{{ $card['location'] }}</div>
                            <div class="zone-dept" style="font-size: {{ $card['deptSize'] }}mm">{{ $card['dept'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <span class="cut-line"></span>
        </div>
    </div>

</body>

</html>
