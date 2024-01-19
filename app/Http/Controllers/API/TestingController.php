<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TestingController extends Controller
{
    function index()
    {
        $datas = [];
        $datas = DB::table('ref_sub_kegiatan')->get();

        return response()->json([
            'message' => 'success',
            'data' => $datas,
        ]);
    }
}
