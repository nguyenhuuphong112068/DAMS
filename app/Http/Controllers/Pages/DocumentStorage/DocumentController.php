<?php

namespace App\Http\Controllers\Pages\DocumentStorage;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\StorageLocation\ShelfAccess;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function index()
    {
        // KIỂM TRA SESSION AN TOÀN TRƯỚC KHI CHẠY
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return redirect()->route('login')->with('error', 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.');
        }

        try {
            $user = session('user');
            $selectedDeptId = $user['selected_department_id'];

            $departments = DB::table('deparments')->where('active', true)->get();
            $document_types = DB::table('document_types')->get();
            
            // Fetch storage hierarchy for filters
            $warehouses = DB::table('warehouses')->where('active', true)->get();
            $shelves = DB::table('shelves')->where('active', true)->get();
            $tiers = DB::table('tiers')->where('active', true)->get();
            // Vị trí (~60k dòng) không render sẵn; tải theo tầng qua route locations

            session()->put(['title' => 'QUẢN LÝ LƯU TRỮ']);

            // Đếm tài liệu hết hạn (dữ liệu bảng được tải qua AJAX ở route data)
            $expiredCount = DB::table('documents')
                ->where('department_id', $selectedDeptId)
                ->whereNotNull('expired_date')
                ->whereDate('expired_date', '<=', now()->toDateString())
                ->count();

            return view('pages.DocumentStorage.Document.list', [
                'departments' => $departments,
                'document_types' => $document_types,
                'warehouses' => $warehouses,
                'shelves' => $shelves,
                'tiers' => $tiers,
                'expiredCount' => $expiredCount,
                'canUpdate' => user_has_permission($user['userId'], 'document.update', 'boolean'),
                'canDispose' => user_has_permission($user['userId'], 'document.dispose', 'boolean'),
            ]);
        } catch (\Exception $e) {
            Log::error("Document index error: " . $e->getMessage());
            return "Lỗi Server (500): " . $e->getMessage() . " tại dòng " . $e->getLine();
        }
    }

    // Danh sách vị trí của một tầng, dùng cho bộ lọc và modal thêm/sửa
    public function locations(Request $request)
    {
        if (!session()->has('user') || !$request->filled('tier_id')) {
            return response()->json([]);
        }

        $user = session('user');
        $userDeptId = $user['department_id'] ?? $user['selected_department_id'] ?? null;

        $locations = DB::table('locations')
            ->where('tier_id', $request->tier_id)
            ->where(function ($q) use ($request, $userDeptId) {
                $q->where(function ($q) use ($userDeptId) {
                    $q->where('department_id', $userDeptId)->where('status_id', 1);
                });
                // Giữ vị trí hiện tại của tài liệu đang sửa dù không còn hoạt động
                if ($request->filled('include_id')) {
                    $q->orWhere('id', $request->include_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($locations);
    }

    // Tìm vị trí cho modal thêm/sửa (Select2 AJAX): chọn vị trí trước sẽ tự suy ra Kho/Kệ/Tầng
    public function locationSearch(Request $request)
    {
        if (!session()->has('user')) {
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }

        $user = session('user');
        $userDeptId = $user['department_id'] ?? $user['selected_department_id'] ?? null;
        $perPage = 30;
        $page = max(1, (int) $request->input('page', 1));

        $query = DB::table('locations')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->where('locations.department_id', $userDeptId)
            ->where('locations.status_id', 1)
            // Chỉ vị trí trống (hoặc vị trí của chính tài liệu đang sửa)
            ->whereNotExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('documents')
                    ->whereColumn('documents.location_id', 'locations.id');
                if ($request->filled('document_id')) {
                    $q->where('documents.id', '<>', $request->document_id);
                }
            });

        foreach (['warehouse_id', 'shelf_id', 'tier_id'] as $field) {
            if ($request->filled($field)) {
                $query->where("locations.$field", $request->input($field));
            }
        }

        // Kệ đã giao cho người quản lý kệ khác thì không gợi ý, tránh chọn xong mới bị từ chối.
        ShelfAccess::scopeManageable($query, $user['userId']);

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('locations.name', 'like', "%{$keyword}%")
                    ->orWhere('locations.code', 'like', "%{$keyword}%");
            });
        }

        $rows = $query->orderBy('warehouses.code')->orderBy('shelves.code')->orderBy('tiers.code')->orderBy('locations.code')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get([
                'locations.id', 'locations.name', 'locations.code',
                'locations.warehouse_id', 'locations.shelf_id', 'locations.tier_id',
                'warehouses.name as warehouse_name', 'shelves.name as shelf_name', 'tiers.name as tier_name',
            ]);

        $more = $rows->count() > $perPage;
        $results = $rows->take($perPage)->map(fn($r) => [
            'id' => $r->id,
            'text' => $r->code . ' - ' . $r->name,
            'path' => implode(' / ', array_filter([$r->warehouse_name, $r->shelf_name, $r->tier_name])),
            'warehouse_id' => $r->warehouse_id,
            'shelf_id' => $r->shelf_id,
            'tier_id' => $r->tier_id,
        ])->values();

        return response()->json(['results' => $results, 'pagination' => ['more' => $more]]);
    }

    // DataTables server-side: chỉ truy vấn đúng trang đang xem
    public function data(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return response()->json(['error' => 'Phiên làm việc hết hạn'], 401);
        }

        $selectedDeptId = session('user')['selected_department_id'];
        $today = now()->toDateString();

        $base = DB::table('documents')
            ->where('documents.department_id', $selectedDeptId)
            ->leftJoin('locations', 'documents.location_id', '=', 'locations.id');

        $recordsTotal = (clone $base)->count();

        $keyword = trim($request->input('search.value', ''));
        if ($keyword !== '') {
            $base->where(function ($q) use ($keyword) {
                $q->where('documents.code', 'like', "%{$keyword}%")
                    ->orWhere('documents.name', 'like', "%{$keyword}%")
                    ->orWhere('locations.code', 'like', "%{$keyword}%")
                    ->orWhere('locations.name', 'like', "%{$keyword}%");
            });
        }
        if ($request->filled('type_id')) {
            $base->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('document_has_types')
                    ->whereColumn('document_has_types.document_id', 'documents.id')
                    ->where('document_has_types.document_type_id', $request->type_id);
            });
        }
        foreach (['warehouse_id', 'shelf_id', 'tier_id'] as $field) {
            if ($request->filled($field)) {
                $base->where("locations.$field", $request->input($field));
            }
        }
        if ($request->filled('location_id')) {
            $base->where('documents.location_id', $request->location_id);
        }
        if ($request->expiry === 'expired') {
            $base->whereNotNull('documents.expired_date')->whereDate('documents.expired_date', '<=', $today);
        } elseif ($request->expiry === 'valid') {
            $base->where(function ($q) use ($today) {
                $q->whereNull('documents.expired_date')->orWhereDate('documents.expired_date', '>', $today);
            });
        }

        $recordsFiltered = (clone $base)->count();

        // Chỉ số cột phải khớp với thứ tự <th> trong dataTable.blade.php
        // Cột vị trí sắp theo thứ bậc Kho → Kệ → Tầng → Vị trí (theo mã để Kho A1 lên trước)
        $locationOrder = ['warehouses.code', 'shelves.code', 'tiers.code', 'locations.code'];
        $sortable = [1 => $locationOrder, 3 => ['documents.name'], 5 => ['documents.expired_date'], 6 => ['documents.status_id']];
        $orderCols = $sortable[(int) $request->input('order.0.column', 1)] ?? $locationOrder;
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $isAll = ($length === -1);

        $rows = $base
            ->leftJoin('deparments', 'documents.department_id', '=', 'deparments.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
            ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
            ->select(
                'documents.id', 'documents.code', 'documents.name', 'documents.owner', 'documents.filepath',
                'documents.location_id', 'documents.department_id', 'documents.expired_date',
                'documents.is_private', 'documents.status_id',
                'deparments.name as department_name',
                'locations.name as location_name', 'locations.code as location_code',
                'locations.warehouse_id', 'locations.shelf_id', 'locations.tier_id',
                'warehouses.name as warehouse_name', 'warehouses.code as warehouse_code',
                'shelves.name as shelf_name', 'shelves.code as shelf_code',
                'tiers.name as tier_name', 'tiers.code as tier_code'
            );
        foreach ($orderCols as $col) {
            $rows->orderBy($col, $orderDir);
        }
        $rows = $rows->orderBy('documents.id');

        if (!$isAll) {
            $length = $length > 0 ? min($length, 500) : 10;
            $rows = $rows->offset($start)->limit($length);
        }

        $rows = $rows->get();

        $types = DB::table('document_has_types')
            ->join('document_types', 'document_has_types.document_type_id', '=', 'document_types.id')
            ->whereIn('document_has_types.document_id', $rows->pluck('id'))
            ->select('document_has_types.document_id', 'document_types.id', 'document_types.name')
            ->get()
            ->groupBy('document_id');

        // Xem "Tất cả" có thể là hàng chục nghìn dòng, khi đó lấy theo bộ phận thay vì liệt kê id.
        $locationIds = $rows->pluck('location_id')->filter()->unique()->values()->all();
        $owners = count($locationIds) > 1000
            ? ShelfAccess::owners('department_id', $selectedDeptId)
            : ShelfAccess::owners('id', $locationIds);
        $ownerNames = ShelfAccess::names($owners);
        $viewerId = session('user')['userId'];
        $supervisor = ShelfAccess::isSupervisor($viewerId);

        foreach ($rows as $row) {
            $rowOwners = $owners[(int) $row->location_id] ?? [];
            $row->managers = implode(', ', array_map(fn ($id) => $ownerNames[$id] ?? '#' . $id, $rowOwners));
            $row->can_manage = $supervisor || ShelfAccess::allows($viewerId, $owners, (int) $row->location_id);

            $docTypes = $types[$row->id] ?? collect();
            $row->type_ids = $docTypes->pluck('id')->values();
            $row->type_names = $docTypes->pluck('name')->implode(', ');
            $row->is_expired = $row->expired_date && $row->expired_date <= $today;
            $row->expired_display = $row->expired_date ? \Carbon\Carbon::parse($row->expired_date)->format('d/m/Y') : null;
            $row->file_url = $row->filepath
                ? (strpos($row->filepath, 'http') === 0 ? $row->filepath : asset($row->filepath))
                : null;
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Trang in nhãn tài liệu (mã QR), mở tab mới khi bấm vào mã QR trên bảng.
     *
     * Số lượng nhãn chọn ngay trên trang in (nhân bản nhãn bằng JS) nên không nạp lại
     * trang; lúc bấm In, trang gọi labelPrinted() để ghi audit log.
     */
    public function label(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return redirect()->route('login')->with('error', 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.');
        }

        $row = DB::table('documents')
            ->leftJoin('locations', 'documents.location_id', '=', 'locations.id')
            ->leftJoin('deparments', 'documents.department_id', '=', 'deparments.id')
            ->select(
                'documents.id', 'documents.code', 'documents.name', 'documents.owner',
                'documents.expired_date', 'documents.created_at',
                'locations.code as location_code', 'locations.name as location_name',
                'deparments.name as department_name'
            )
            ->where('documents.id', $request->id)
            ->where('documents.department_id', session('user')['selected_department_id'])
            ->first();

        if (!$row) {
            abort(404, 'Không tìm thấy tài liệu.');
        }

        $qrValue = $row->location_code ?: $row->code;

        return view('pages.DocumentStorage.Document.label', [
            'document' => $row,
            'label' => config('document.label'),
            // ECC Q (25%): chịu được logo Stella đè giữa mã. border 1 module: vùng
            // trắng tối thiểu để QR gọn trong góc 1/4 nhãn.
            // Mã hoá MÃ VỊ TRÍ (location_code) chứ không phải mã tài liệu, để quét QR
            // ra đúng vị trí lưu trữ hiện tại của hồ sơ.
            'qr' => QrCode::render((string) $qrValue, 'Q', 1),
            'qrValue' => $qrValue,
            'maxCopies' => (int) config('document.label.max_copies', 100),
        ]);
    }

    /**
     * GHI AUDIT LOG MỖI LẦN IN NHÃN TÀI LIỆU.
     *
     * Trang in gọi vào đây ngay trước khi in (kể cả khi bấm Ctrl+P). Ngoài nhật ký chỉ
     * cập nhật labeled_location_id: nhãn vừa in mang vị trí hiện tại, nên hồ sơ rời
     * khỏi danh sách "Nhãn cần in lại" trên Sơ Đồ Kho.
     */
    public function labelPrinted(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return response()->json(['ok' => false, 'message' => 'Phiên làm việc hết hạn'], 401);
        }

        $maxCopies = (int) config('document.label.max_copies', 100);
        $copies = max(1, min($maxCopies, (int) $request->input('copies', 1)));

        $row = DB::table('documents')
            ->select('id', 'code', 'name', 'location_id')
            ->where('id', $request->id)
            ->where('department_id', session('user')['selected_department_id'])
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy tài liệu cần in nhãn.'], 404);
        }

        $printedAt = now();

        DB::table('documents')->where('id', $row->id)->update(['labeled_location_id' => $row->location_id]);

        AuditTrialController::log(
            'In nhãn',
            'documents',
            $row->id,
            'NA',
            'In nhãn tài liệu: ' . ($row->name ?: '(chưa có tên)')
                . ' | Mã tài liệu: ' . $row->code
                . ' | Số lượng nhãn: ' . $copies
                . ' | Thời điểm in: ' . $printedAt->format('d/m/Y H:i:s')
        );

        return response()->json([
            'ok' => true,
            'copies' => $copies,
            'printedAt' => $printedAt->format('d/m/Y H:i:s'),
        ]);
    }

    /** Số tài liệu tối đa cho một lần in nhãn gáy binder. */
    private const BINDER_LABEL_MAX_DOCS = 100;

    /**
     * Trang in NHÃN GÁY BINDER (A4, máy in thường) cho 1 hoặc nhiều tài liệu:
     * ?ids=1,2,3 (chọn nhiều ở bảng) hoặc ?id=1. Khổ 5cm/7cm chọn qua ?size=.
     * Nội dung lấy thẳng từ tài liệu, giữ thứ tự chọn.
     */
    public function binderLabel(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return redirect()->route('login')->with('error', 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.');
        }

        $ids = $this->binderLabelIds($request);

        $rows = DB::table('documents')
            ->leftJoin('locations', 'documents.location_id', '=', 'locations.id')
            ->leftJoin('deparments', 'documents.department_id', '=', 'deparments.id')
            ->select(
                'documents.id', 'documents.code', 'documents.name',
                'locations.code as location_code', 'locations.name as location_name',
                'deparments.name as department_name'
            )
            ->whereIn('documents.id', $ids)
            ->where('documents.department_id', session('user')['selected_department_id'])
            ->get()
            ->keyBy('id');

        $docs = collect($ids)->map(fn($id) => $rows->get($id))->filter()->values();
        if ($docs->isEmpty()) {
            abort(404, 'Không tìm thấy tài liệu.');
        }

        $sizes = config('binder.label.sizes');
        $width = (int) $request->input('size', 5);
        if (!isset($sizes[$width])) {
            $width = 5;
        }

        $labels = $docs->map(function ($row) {
            $qrValue = $row->location_code ?: $row->code;
            return ['record' => $row, 'qr' => QrCode::render((string) $qrValue, 'Q', 1), 'qrValue' => $qrValue];
        })->all();

        $idList = $docs->pluck('id')->implode(',');

        return view('pages.DocumentStorage.Document.binderLabel', [
            'labels' => $labels,
            'recordIds' => $idList,
            'size' => $sizes[$width],
            'maxCopies' => (int) config('binder.label.max_copies', 100),
            'logUrl' => route('pages.documentStorage.document.binderLabelPrinted'),
            'backUrl' => route('pages.documentStorage.document.list'),
            'currentSize' => $width,
            'sizeUrls' => collect(array_keys($sizes))->mapWithKeys(fn($w) => [
                $w => route('pages.documentStorage.document.binderLabel', ['ids' => $idList, 'size' => $w]),
            ])->all(),
        ]);
    }

    // Ghi audit log in nhãn gáy binder, mỗi tài liệu 1 dòng. Không đụng labeled_location_id
    // (chỉ dành cho nhãn QR).
    public function binderLabelPrinted(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return response()->json(['ok' => false, 'message' => 'Phiên làm việc hết hạn'], 401);
        }

        $maxCopies = (int) config('binder.label.max_copies', 100);
        $copies = max(1, min($maxCopies, (int) $request->input('copies', 1)));

        $rows = DB::table('documents')
            ->select('id', 'code', 'name')
            ->whereIn('id', $this->binderLabelIds($request))
            ->where('department_id', session('user')['selected_department_id'])
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy tài liệu cần in nhãn.'], 404);
        }

        $printedAt = now();

        foreach ($rows as $row) {
            AuditTrialController::log(
                'In nhãn',
                'documents',
                $row->id,
                'NA',
                'In nhãn gáy binder: ' . ($row->name ?: '(chưa có tên)')
                    . ' | Mã tài liệu: ' . $row->code
                    . ' | Số lượng nhãn: ' . $copies
                    . ' | In cùng lúc: ' . $rows->count() . ' tài liệu'
                    . ' | Thời điểm in: ' . $printedAt->format('d/m/Y H:i:s')
            );
        }

        return response()->json(['ok' => true, 'copies' => $copies, 'documents' => $rows->count()]);
    }

    /** @return array<int,int> id tài liệu từ ?ids=1,2,3 hoặc ?id=1, bỏ trùng, giữ thứ tự */
    private function binderLabelIds(Request $request): array
    {
        return collect(explode(',', (string) $request->input('ids', $request->input('id', ''))))
            ->map(fn($v) => (int) trim($v))
            ->filter(fn($v) => $v > 0)
            ->unique()
            ->take(self::BINDER_LABEL_MAX_DOCS)
            ->values()
            ->all();
    }

    /**
     * Mã tài liệu chính là mã vị trí lưu trữ, nên không nhập tay mà suy ra từ vị trí.
     * Trả về [mã, lỗi]; vị trí đã có hồ sơ khác thì báo lỗi.
     */
    private function resolveDocumentCode($locationId, $documentId = null)
    {
        $location = DB::table('locations')->where('id', $locationId)->first(['id', 'code']);
        if (!$location) {
            return [null, 'Vị trí lưu trữ không tồn tại.'];
        }

        $occupied = DB::table('documents')
            ->where('location_id', $location->id)
            ->when($documentId, fn($q) => $q->where('id', '<>', $documentId))
            ->exists();
        if ($occupied) {
            return [null, 'Vị trí ' . $location->code . ' đã có hồ sơ khác.'];
        }

        $duplicated = DB::table('documents')
            ->where('code', $location->code)
            ->when($documentId, fn($q) => $q->where('id', '<>', $documentId))
            ->exists();
        if ($duplicated) {
            return [null, 'Mã tài liệu ' . $location->code . ' đã tồn tại.'];
        }

        return [$location->code, null];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'location_id' => 'required',
            'department_id' => 'required',
        ]);

        [$code, $codeError] = $validator->fails() ? [null, null] : $this->resolveDocumentCode($request->location_id);
        $codeError = $codeError ?: ShelfAccess::denied(session('user')['userId'], [$request->location_id]);
        if ($codeError) {
            $validator->after(fn($v) => $v->errors()->add('location_id', $codeError));
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        $filepath = $request->filepath;
        if ($request->hasFile('file_attachment')) {
            $file = $request->file('file_attachment');
            $folderPath = 'uploads/documents/' . date('Y/m');
            $destinationPath = public_path($folderPath);

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);
            $filepath = $folderPath . '/' . $fileName;
        }

        $docId = DB::table('documents')->insertGetId([
            'code' => $code,
            'name' => $request->name,
            'owner' => session('user')['fullName'],
            'filepath' => $filepath,
            'location_id' => $request->location_id,
            'department_id' => $request->department_id,
            'expired_date' => $request->expired_date,
            'is_private' => false,
            'status_id' => 1, // Default Active
            'created_by' => session('user')['fullName'],
            'created_at' => now(),
        ]);

        if ($request->document_types_id) {
            $typesData = [];
            foreach ($request->document_types_id as $typeId) {
                $typesData[] = [
                    'document_id' => $docId,
                    'document_type_id' => $typeId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            DB::table('document_has_types')->insert($typesData);
        }

        return redirect()->back()->with('success', 'Đã thêm tài liệu thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'location_id' => 'required',
            'department_id' => 'required',
        ]);

        $document = DB::table('documents')->where('id', $request->id)->first(['id', 'code', 'location_id']);
        $code = $document->code ?? null;
        $denied = $document ? ShelfAccess::denied(session('user')['userId'], [$document->location_id, $request->location_id]) : null;
        if (!$validator->fails() && !$document) {
            $validator->after(fn($v) => $v->errors()->add('id', 'Không tìm thấy tài liệu.'));
        } elseif (!$validator->fails() && $denied) {
            $validator->after(fn($v) => $v->errors()->add('location_id', $denied));
        } elseif (!$validator->fails() && (int) $document->location_id !== (int) $request->location_id) {
            // Đổi vị trí thì mã tài liệu đổi theo mã vị trí mới
            [$code, $codeError] = $this->resolveDocumentCode($request->location_id, $document->id);
            if ($codeError) {
                $validator->after(fn($v) => $v->errors()->add('location_id', $codeError));
            }
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        $filepath = $request->filepath;
        if ($request->hasFile('file_attachment')) {
            $file = $request->file('file_attachment');
            $folderPath = 'uploads/documents/' . date('Y/m');
            $destinationPath = public_path($folderPath);

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);
            $filepath = $folderPath . '/' . $fileName;
        }

        DB::table('documents')->where('id', $request->id)->update([
            'code' => $code,
            'name' => $request->name,
            'filepath' => $filepath,
            'location_id' => $request->location_id,
            'department_id' => $request->department_id,
            'expired_date' => $request->expired_date,
            'updated_by' => session('user')['fullName'],
            'updated_at' => now(),
        ]);

        // Sync document types
        DB::table('document_has_types')->where('document_id', $request->id)->delete();
        if ($request->document_types_id) {
            $typesData = [];
            foreach ($request->document_types_id as $typeId) {
                $typesData[] = [
                    'document_id' => $request->id,
                    'document_type_id' => $typeId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            DB::table('document_has_types')->insert($typesData);
        }

        return redirect()->back()->with('success', 'Cập nhật tài liệu thành công!');
    }

    /**
     * HUỶ HỒ SƠ.
     *
     * Lưu bản chụp hồ sơ + vị trí vào document_disposals rồi xoá hồ sơ khỏi documents,
     * nhờ vậy vị trí đang giữ trở thành vị trí trống ở mọi nơi (sơ đồ kho, chọn vị trí...).
     */
    public function dispose(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return response()->json(['message' => 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.'], 401);
        }

        $reason = trim((string) $request->input('reason'));
        if ($reason === '') {
            return response()->json(['message' => 'Vui lòng nhập lý do huỷ hồ sơ.'], 422);
        }

        $departmentId = session('user')['selected_department_id'];
        $actor = session('user')['fullName'] ?? 'NA';
        $documentId = (int) $request->input('id');

        $result = DB::transaction(function () use ($departmentId, $actor, $documentId, $reason) {
            $document = DB::table('documents')
                ->where('id', $documentId)
                ->where('department_id', $departmentId)
                ->lockForUpdate()
                ->first();

            if (!$document) {
                return ['status' => 404, 'message' => 'Không tìm thấy hồ sơ hoặc hồ sơ đã bị huỷ.'];
            }

            $denied = ShelfAccess::denied(session('user')['userId'], [$document->location_id]);
            if ($denied) {
                return ['status' => 403, 'message' => $denied];
            }

            $location = DB::table('locations')
                ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('shelves', 'locations.shelf_id', '=', 'shelves.id')
                ->leftJoin('tiers', 'locations.tier_id', '=', 'tiers.id')
                ->where('locations.id', $document->location_id)
                ->select(
                    'locations.code', 'locations.name',
                    'warehouses.name as warehouse_name', 'shelves.name as shelf_name', 'tiers.name as tier_name'
                )
                ->first();

            $typeNames = DB::table('document_has_types')
                ->join('document_types', 'document_has_types.document_type_id', '=', 'document_types.id')
                ->where('document_has_types.document_id', $document->id)
                ->pluck('document_types.name')
                ->implode(', ');

            DB::table('document_disposals')->insert([
                'document_id'         => $document->id,
                'code'                => $document->code,
                'name'                => $document->name,
                'owner'               => $document->owner,
                'filepath'            => $document->filepath,
                'department_id'       => $document->department_id,
                'type_names'          => $typeNames !== '' ? mb_substr($typeNames, 0, 500) : null,
                'expired_date'        => $document->expired_date,
                'location_id'         => $document->location_id,
                'location_code'       => $location->code ?? null,
                'location_name'       => $location->name ?? null,
                'warehouse_name'      => $location->warehouse_name ?? null,
                'shelf_name'          => $location->shelf_name ?? null,
                'tier_name'           => $location->tier_name ?? null,
                'reason'              => $reason,
                'document_created_by' => $document->created_by,
                'document_created_at' => $document->created_at,
                'disposed_by'         => $actor,
                'disposed_at'         => now(),
            ]);

            DB::table('document_has_types')->where('document_id', $document->id)->delete();
            DB::table('documents')->where('id', $document->id)->delete();

            AuditTrialController::log(
                'Huỷ hồ sơ',
                'documents',
                $document->id,
                'Hồ sơ ' . $document->code . ' tại vị trí ' . ($location->code ?? $document->location_id),
                'Huỷ hồ sơ ' . $document->code . ' - ' . $document->name
                    . ' | Trả vị trí trống: ' . ($location->code ?? $document->location_id)
                    . ' | Lý do: ' . $reason
            );

            return ['status' => 200, 'message' => 'Đã huỷ hồ sơ ' . $document->code . ', vị trí đã được trả về trống.'];
        });

        return response()->json(['message' => $result['message']], $result['status']);
    }

    // DataTables server-side cho tab Lịch sử huỷ hồ sơ
    public function disposals(Request $request)
    {
        if (!session()->has('user') || !isset(session('user')['selected_department_id'])) {
            return response()->json(['error' => 'Phiên làm việc hết hạn'], 401);
        }

        $base = DB::table('document_disposals')
            ->where('department_id', session('user')['selected_department_id']);

        $recordsTotal = (clone $base)->count();

        $keyword = trim($request->input('search.value', ''));
        if ($keyword !== '') {
            $base->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('location_code', 'like', "%{$keyword}%")
                    ->orWhere('location_name', 'like', "%{$keyword}%")
                    ->orWhere('reason', 'like', "%{$keyword}%")
                    ->orWhere('disposed_by', 'like', "%{$keyword}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        // Chỉ số cột phải khớp với thứ tự <th> của bảng lịch sử huỷ trong dataTable.blade.php
        $sortable = [1 => 'location_code', 2 => 'name', 5 => 'disposed_by', 6 => 'disposed_at'];
        $orderCol = $sortable[(int) $request->input('order.0.column')] ?? 'disposed_at';
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 500) : 500;

        $rows = $base->orderBy($orderCol, $orderDir)
            ->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        foreach ($rows as $row) {
            $row->location_path = implode(' / ', array_filter([$row->warehouse_name, $row->shelf_name, $row->tier_name]));
            $row->disposed_display = $row->disposed_at ? \Carbon\Carbon::parse($row->disposed_at)->format('d/m/Y H:i') : null;
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}
