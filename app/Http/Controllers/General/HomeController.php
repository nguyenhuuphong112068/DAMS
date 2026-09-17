<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function showHomeForm()
    {
        session()->put(['title' => 'TRANG CHỦ']);

        $stats = [
            'total_documents'   => DB::table('documents')->count(),
            'total_locations'   => DB::table('locations')->count(),
            'total_warehouses'  => DB::table('warehouses')->count(),
            'total_shelves'     => DB::table('shelves')->count(),
        ];

        return view('pages.general.home', compact('stats'));
    }
}
