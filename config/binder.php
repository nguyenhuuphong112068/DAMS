<?php

/*
|--------------------------------------------------------------------------
| NHÃN GÁY BINDER
|--------------------------------------------------------------------------
*/

return [

    /*
     | NHÃN GÁY BINDER - in từ màn Quản lý lưu trữ, trên giấy A4 thường (máy in HP),
     | nhiều nhãn xếp thành hàng có đường cắt. Kích thước lấy theo 2 mẫu Word
     | "STE_Binder Label 5cm/7cm".
     |
     | Nhãn chia 4 vùng từ trên xuống: logo | nội dung (QR + tên hồ sơ in dọc) |
     | ô định khu (vị trí) | ô phòng ban. Vùng nội dung chiếm phần chiều cao còn lại.
     */
    'label' => [
        // Key = khổ gáy (cm), khớp ?size= của trang in.
        'sizes' => [
            5 => [
                'width_mm' => 59,
                'gap_mm' => 7,
                'per_row' => 3,
                'qr_size_mm' => 18,
            ],
            7 => [
                'width_mm' => 78,
                'gap_mm' => 10,
                'per_row' => 2,
                'qr_size_mm' => 22,
            ],
        ],
        // Chiều cao dùng chung cho cả 2 khổ
        'height_mm' => 207,
        'logo_height_mm' => 26,
        'location_height_mm' => 20,
        'department_height_mm' => 7,
        // Số bản tối đa cho mỗi tài liệu trong một lần in
        'max_copies' => 100,
    ],
];
