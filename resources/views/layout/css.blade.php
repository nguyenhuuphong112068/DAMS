		
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}} ">
      <!-- DataTables -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
      <!-- Theme style -->
      <link rel="stylesheet" href="{{asset ('dataTable/dist/css/adminlte.min.css')}}">



      <link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}}">
      <!-- Ionicons -->
      <link rel="stylesheet" href="{{asset ('css/ionicons.min.css')}}">
      <!-- daterange picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/daterangepicker/daterangepicker.css')}}">
      <!-- iCheck for checkboxes and radio inputs -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
      <!-- Bootstrap Color Picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css')}}">
      <!-- Tempusdominus Bbootstrap 4 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
      <!-- Select2 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2/css/select2.min.css')}}">
      
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css')}}">
      <!-- Theme style -->
      <link rel="stylesheet" href="{{asset ('dataTable/dist/css/adminlte.min.css')}}">

      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      
      <!-- Fonts: Sans-serif system -->
      
      <style>
            :root {
                  --primary-navy: #003A4F;
                  --accent-gold: #CDC717;
                  /* Bộ màu cho sơ đồ 3D / mặt bằng kho (pages/shared/shelf3d, zone3d - port từ WMS) */
                  --primary: #0A5570;
                  --primary-dark: #003A4F;
                  --primary-light: #2B7D99;
                  --primary-lighter: #93BFCE;
                  --primary-soft: #E6F1F5;
                  --primary-rgb: 10, 85, 112;
                  --accent: #17B8D4;
                  --text-muted: #6B7A90;
                  --bg-neutral: #F8F9FA;
                  --text-main: #2D3748;
                  --border-radius-lg: 12px;
                  --border-radius-md: 8px;
                  --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
                  --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
                  --transition-fast: 0.2s ease;
                  --transition-normal: 0.3s ease;
            }

            body {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  background-color: var(--bg-neutral);
                  color: var(--text-main);
                  overflow-x: hidden;
            }

            h1, h2, h3, h4, .library-title, .module-header {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  text-transform: uppercase;
                  letter-spacing: 1px;
                  font-weight: 700;
                  color: var(--primary-navy);
            }

            .form-floating > label {
      font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                  font-size: 0.85rem;
                  color: #718096;
            }

            .card {
                  border: none;
                  border-radius: var(--border-radius-lg);
                  box-shadow: var(--shadow-sm);
                  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
            }

            .card:hover {
                  box-shadow: var(--shadow-md);
            }

            .btn-primary {
                  background-color: var(--primary-navy);
                  border-color: var(--primary-navy);
                  border-radius: var(--border-radius-md);
                  padding: 8px 16px;
                  font-weight: 500;
                  transition: all var(--transition-fast);
            }

            .btn-primary:hover {
                  background-color: #002D3D;
                  transform: translateY(-1px);
                  box-shadow: 0 4px 8px rgba(0, 58, 79, 0.2);
            }

            body.modal-open {
                  padding: 0 !important;
                  overflow-y: scroll;
            }
      </style>
