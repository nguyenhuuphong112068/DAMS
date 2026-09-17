<?php
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\StatusController;
use App\Http\Controllers\Pages\MaterData\DocumentTypeController;
use App\Http\Controllers\Pages\StorageLocation\WarehouseController;
use App\Http\Controllers\Pages\StorageLocation\RoomController;
use App\Http\Controllers\Pages\StorageLocation\ShelfController;
use App\Http\Controllers\Pages\StorageLocation\TierController;
use App\Http\Controllers\Pages\StorageLocation\LocationController;
use App\Http\Controllers\Pages\StorageLocation\WarehouseMapController;
use App\Http\Controllers\Pages\DocumentStorage\DocumentController;
use App\Http\Controllers\Pages\DocumentStorage\DocumentRoutingController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');
Route::POST('/import', [UploadDataController::class, 'import'])->name('upload.import');
Route::POST('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');

// 1. Dữ Liệu Gốc (Master Data)
Route::prefix('/materData')
    ->name('pages.materData.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/department')->name('department.')->controller(DepartmentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/status')->name('status.')->controller(StatusController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/documentType')->name('documentType.')->controller(DocumentTypeController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
    });

// 2. Vị Trí Lưu Trữ (Storage Location)
Route::prefix('/storageLocation')
    ->name('pages.storageLocation.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/warehouse')->name('warehouse.')->controller(WarehouseController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/room')->name('room.')->controller(RoomController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/shelf')->name('shelf.')->controller(ShelfController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/tier')->name('tier.')->controller(TierController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/location')->name('location.')->controller(LocationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('datatable', 'datatable')->name('datatable');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/map')->name('map.')->controller(WarehouseMapController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('summary', 'summary')->name('summary');
            Route::get('grid', 'grid')->name('grid');
            Route::get('cell', 'cell')->name('cell');
            Route::get('locate', 'locate')->name('locate');
            Route::post('move', 'move')->name('move');
            Route::get('relabel', 'relabel')->name('relabel');
        });
    });

// 3. Quản Lý Tài Liệu (Document Storage)
Route::prefix('/documentStorage')
    ->name('pages.documentStorage.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/document')->name('document.')->controller(DocumentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('data', 'data')->name('data');
            Route::get('locations', 'locations')->name('locations');
            Route::get('locationSearch', 'locationSearch')->name('locationSearch');
            Route::get('label', 'label')->name('label');                    // Trang in nhãn QR
            Route::post('labelPrinted', 'labelPrinted')->name('labelPrinted'); // Ghi audit log mỗi lần in
            Route::get('binderLabel', 'binderLabel')->name('binderLabel');   // In nhãn gáy binder (A4)
            Route::post('binderLabelPrinted', 'binderLabelPrinted')->name('binderLabelPrinted');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('dispose', 'dispose')->name('dispose');             // Huỷ hồ sơ, trả vị trí trống
            Route::get('disposals', 'disposals')->name('disposals');        // Lịch sử huỷ hồ sơ
        });

        // Theo dõi đường đi của hồ sơ (Luân chuyển)
        Route::prefix('/routing')->name('routing.')->controller(DocumentRoutingController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');                   // Bước 1: Văn thư khởi tạo
            Route::post('forward', 'forward')->name('forward');             // Bước 2: Chuyển tiếp
            Route::post('confirmReceive', 'confirmReceive')->name('confirmReceive');
            Route::post('finish', 'finish')->name('finish');                // Bước 3: Kết thúc & lưu trữ
            Route::post('cancel', 'cancel')->name('cancel');
        });
    });