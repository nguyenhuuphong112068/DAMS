<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">
    <title>Đăng nhập | {{ config('app.name', 'Quản Lý Kho Hồ Sơ') }}</title>

    <!-- Bootstrap & Fonts -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">

    <style>
        :root {
            --primary-navy: #003A4F;
            --primary-navy-dark: #002733;
            --accent-gold: #CDC717;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(circle at 15% 15%, #0a5570 0%, var(--primary-navy) 45%, var(--primary-navy-dark) 100%);
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-shell {
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            display: flex;
            min-height: 560px;
        }

        /* Left brand panel */
        .brand-panel {
            flex: 0 0 42%;
            background: linear-gradient(160deg, var(--primary-navy) 0%, var(--primary-navy-dark) 100%);
            color: #fff;
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: rgba(205, 199, 23, 0.08);
            top: -120px;
            right: -140px;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            bottom: -90px;
            left: -70px;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: var(--accent-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--primary-navy-dark);
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .brand-panel h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .brand-panel p {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            z-index: 1;
            margin-bottom: 32px;
        }

        .brand-features {
            position: relative;
            z-index: 1;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .brand-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 14px;
        }

        .brand-features i {
            color: var(--accent-gold);
            font-size: 1rem;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            margin-top: auto;
            padding-top: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.7;
        }

        .brand-footer img {
            height: 20px;
        }

        .brand-footer span {
            font-size: 0.75rem;
        }

        /* Right form panel */
        .form-panel {
            flex: 1;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-navy);
            margin-bottom: 6px;
        }

        .form-panel .subtitle {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 28px;
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-navy);
            box-shadow: 0 0 0 3px rgba(0, 58, 79, 0.1);
        }

        .btn-login {
            background-color: var(--primary-navy);
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
            margin-top: 8px;
        }

        .btn-login:hover {
            background-color: #002D3D;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 58, 79, 0.3);
            color: white;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
        }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-navy);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-shell {
                flex-direction: column;
                min-height: 0;
            }

            .brand-panel {
                flex: none;
                padding: 32px 28px;
            }

            .brand-features {
                display: none;
            }

            .form-panel {
                padding: 36px 28px;
            }
        }
    </style>
</head>

<body>

    <div class="auth-shell">
        <div class="brand-panel">
            <div class="brand-icon">
                <i class="bi bi-archive-fill"></i>
            </div>
            <h1>{{ config('app.name', 'Quản Lý Kho Hồ Sơ') }}</h1>
            <p>Hệ thống lưu trữ, định vị và quản lý hồ sơ tài liệu tập trung, an toàn và minh bạch.</p>
            <ul class="brand-features">
                <li><i class="bi bi-check-circle-fill"></i> Quản lý hồ sơ tập trung</li>
                <li><i class="bi bi-check-circle-fill"></i> Định vị và sơ đồ kho trực quan</li>
                <li><i class="bi bi-check-circle-fill"></i> Bảo mật và phân quyền truy cập</li>
            </ul>
            <div class="brand-footer">
                <img src="{{ asset('img/iconstella.svg') }}" alt="Logo">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Quản Lý Kho Hồ Sơ') }}</span>
            </div>
        </div>

        <div class="form-panel">
            @if (session('error'))
                <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
            @endif

            <!-- ✅ Form đăng nhập -->
            <form id="loginForm" action="{{ route('login') }}" method="POST">
                @csrf
                <h2>Đăng nhập hệ thống</h2>
                <p class="subtitle">Vui lòng nhập thông tin tài khoản để tiếp tục</p>

                <div class="mb-3">
                    <label for="username" class="form-label">Tên tài khoản</label>
                    <input type="text" name="username" class="form-control" placeholder="Nhập username" required autofocus value="{{ old('username') }}">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" name="passWord" class="form-control" placeholder="••••••••" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    Đăng nhập hệ thống
                </button>

                <a href="#" class="toggle-link" onclick="toggleForms(true)">Bạn quên mật khẩu?</a>
            </form>

            <!-- ✅ Form đổi mật khẩu (Ẩn mặc định) -->
            <form id="changePassForm" action="{{ route('changePassword') }}" method="POST" style="display: none;">
                @csrf
                <h2>Thiết lập mật khẩu mới</h2>
                <p class="subtitle">Xác nhận tài khoản và cập nhật mật khẩu mới</p>

                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Xác nhận username" required>
                </div>
                <div class="mb-3 password-wrapper">
                    <input type="password" id="oldPassword" name="oldPassword" class="form-control" placeholder="Mật khẩu hiện tại" required>
                </div>
                <div class="mb-3">
                    <input type="password" id="newPassword" name="newPassword" class="form-control" placeholder="Mật khẩu mới" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Cập nhật ngay</button>
                <a href="#" class="toggle-link" onclick="toggleForms(false)">Quay lại đăng nhập</a>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        function toggleForms(showChangePass) {
            document.getElementById('loginForm').style.display = showChangePass ? 'none' : 'block';
            document.getElementById('changePassForm').style.display = showChangePass ? 'block' : 'none';
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("bi-eye-slash", "bi-eye");
            } else {
                input.type = "password";
                icon.classList.replace("bi-eye", "bi-eye-slash");
            }
        }
    </script>
</body>

</html>
