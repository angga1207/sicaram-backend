<?php

namespace App\Http\Controllers\API;

use App\Models\Instance;
use App\Models\Ref\Bidang;
use App\Models\Ref\Urusan;
use App\Models\Ref\Program;
use Illuminate\Support\Str;
use App\Models\Ref\Kegiatan;
use Illuminate\Http\Request;
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TestingController extends Controller
{

    // function index()
    // {
    //     // DB::beginTransaction();
    //     // $datas = SubKegiatan::where('id', 1)->get();
    //     $datas = SubKegiatan::get();

    //     foreach ($datas as $data) {
    //         $code = Str::afterLast($data->fullcode, '.');
    //         $data->code = $code;
    //         $data->save();
    //     }

    //     // DB::commit();

    //     return response()->json([
    //         'message' => 'success',
    //         'data' => $datas,
    //     ]);
    // }

    // function index()
    // {
    //     $datas = SubKegiatan::get();

    //     foreach ($datas as $data) {
    //         // if (str()->length($data->code) == 1) {
    //         //     $data->code = '000' . $data->code;
    //         //     // $newFullCode = str()->substr($data->fullcode, 0, 13) . $data->code;
    //         //     // $data->fullcode = $newFullCode;
    //         //     // return [$data, $newFullCode];
    //         //     // return $data;
    //         // }
    //         // if (str()->length($data->code) == 2) {
    //         //     $data->code = '00' . $data->code;
    //         //     // $newFullCode = str()->substr($data->fullcode, 0, 13) . $data->code;
    //         //     // $data->fullcode = $newFullCode;
    //         //     // return [$data, $newFullCode];
    //         // }
    //         // $data->code = (string)'000' . $data->code;
    //         // $data->save();
    //     }

    //     return response()->json([
    //         'message' => 'success',
    //         'data' => $datas,
    //     ]);
    // }
}
