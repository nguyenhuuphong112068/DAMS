<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\General\HomeController;
use App\Http\Controllers\General\PublicDocumentLookupController;
use App\Http\Controllers\General\SwitchProductionController;
use App\Http\Controllers\HomeController as ControllersHomeController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;


Route::get('/', [LoginController::class, 'showLogin']);

Route::post('/', [LoginController::class, 'login'])->name('login');

// Tra cứu vị trí hồ sơ (Thẻ / 3D / Mặt bằng) của một bộ phận - CÔNG KHAI, không cần đăng nhập
Route::get('/tra-cuu-ho-so', [PublicDocumentLookupController::class, 'index'])->name('publicDocumentLookup');

Route::post('/changePassword', [LoginController::class, 'changePassword'])->name('changePassword');

Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/home', [HomeController::class, 'showHomeForm'])->name('pages.general.home')->middleware(CheckLogin::class);

Route::get('/switch', [SwitchProductionController::class, 'switchProduction'])->name('switch')->middleware(CheckLogin::class);







?>