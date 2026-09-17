<?php

/*
|--------------------------------------------------------------------------
| TÀI LIỆU - DỮ LIỆU CỐ ĐỊNH
|--------------------------------------------------------------------------
*/

return [

    /*
     | NHÃN TÀI LIỆU - in khi bấm vào mã QR trên màn hình Quản lý tài liệu.
     | Cùng khổ và cách in với nhãn lô vật tư của WMS. Đổi khổ nhãn thì sửa ở đây,
     | trang in tự co giãn theo.
     */
    'label' => [
        // Khung nhãn - nhỏ hơn cuộn tem, canh giữa nên chừa lề trắng đều 1mm.
        'width_mm' => 58,
        'height_mm' => 38,
        // Khổ cuộn tem thực đang nạp trên máy in.
        'media_width_mm' => 60,
        'media_height_mm' => 40,
        // Cạnh phần MÃ QR (mm, chưa tính vùng trắng). QR Model 2, mức sửa lỗi Q,
        // nằm ở góc dưới bên phải nhãn, giữa QR có logo Stella.
        'qr_size_mm' => 16,
        // Độ phân giải đầu in Zebra (ZD421: bản 203 hoặc 300 dpi - xem tem dưới đáy máy).
        // Dùng để render nhãn ra ảnh đúng số chấm khi in thẳng bằng ZPL.
        'dpi' => 300,
        // Số nhãn tối đa cho một lần in
        'max_copies' => 100,
    ],
];
