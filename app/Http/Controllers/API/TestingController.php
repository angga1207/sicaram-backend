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
use App\Models\Data\TargetKinerja;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Data\Realisasi;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\RealisasiRincian;

class TestingController extends Controller
{

    function index(Request $request)
    {
        if ($request->password !== 'angga123') {
            return response()->json([
                'message' => 'password salah',
            ]);
        }

        DB::beginTransaction();
        try {
            $datas = Realisasi::where('anggaran_bulan_ini', '<', 0)
                ->get();
            foreach ($datas as $data) {
                $data->anggaran_bulan_ini = 0;
                $data->save();
            }

            DB::commit();
            return response()->json([
                'message' => 'success',
                'data' => $datas,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    // function index(Request $request)
    // {
    //     if ($request->password !== 'angga123') {
    //         return response()->json([
    //             'message' => 'password salah',
    //         ]);
    //     }
    //     DB::beginTransaction();
    //     try {
    //         $return = [];
    //         $arrRealisasi = Realisasi::where('anggaran', '>', 0)
    //             ->groupBy('sub_kegiatan_id')
    //             ->select('sub_kegiatan_id')
    //             ->get();
    //         $arrRealisasi = collect($arrRealisasi)->pluck('sub_kegiatan_id');

    //         $arrMonths = [12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1];
    //         // $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    //         // $arrMonths = [1];

    //         foreach ($arrRealisasi as $subKegiatanId) {
    //             foreach ($arrMonths as $month) {

    //                 $arrDataRealisasiRincian = RealisasiRincian::where('sub_kegiatan_id', $subKegiatanId)
    //                     ->where('month', $month)
    //                     ->get();
    //                 foreach ($arrDataRealisasiRincian as $DataRealisasiRincian) {
    //                     // if ($month == 1) {
    //                     // if ($DataRealisasiRincian && $DataRealisasiRincian->anggaran != 0) {
    //                     //     $DataRealisasiRincian->anggaran_bulan_ini = $DataRealisasiRincian->anggaran;
    //                     //     $DataRealisasiRincian->save();
    //                     // }
    //                     // }
    //                 }

    //                 $arrDataRealisasiKeterangan = RealisasiKeterangan::where('sub_kegiatan_id', $subKegiatanId)
    //                     ->where('month', $month)
    //                     ->get();

    //                 foreach ($arrDataRealisasiKeterangan as $DataRealisasiKeterangan) {
    //                     // if ($month == 1) {
    //                     // if ($DataRealisasiKeterangan && $DataRealisasiKeterangan->anggaran != 0) {
    //                     //     $DataRealisasiKeterangan->anggaran_bulan_ini = $DataRealisasiKeterangan->anggaran;
    //                     //     $DataRealisasiKeterangan->save();
    //                     // }
    //                     // }
    //                 }

    //                 $arrDataRealisasi = Realisasi::where('sub_kegiatan_id', $subKegiatanId)
    //                     ->where('month', $month)
    //                     ->get();
    //                 foreach ($arrDataRealisasi as $DataRealisasi) {
    //                     // if ($month == 1) {
    //                     //     if ($DataRealisasi->anggaran != 0) {
    //                     //         $DataRealisasi->anggaran_bulan_ini = $DataRealisasi->anggaran;
    //                     //         $DataRealisasi->save();
    //                     //     }
    //                     // }
    //                     if ($month > 1) {
    //                         $LastRealisasi = Realisasi::where('sub_kegiatan_id', $subKegiatanId)
    //                             ->where('month', $month - 1)
    //                             ->where('year', $DataRealisasi->year)
    //                             ->where('kode_rekening_id', $DataRealisasi->kode_rekening_id)
    //                             ->where('sumber_dana_id', $DataRealisasi->sumber_dana_id)
    //                             ->where('type', $DataRealisasi->type)
    //                             ->where('is_detail', $DataRealisasi->is_detail)
    //                             ->where('nama_paket', $DataRealisasi->nama_paket)
    //                             ->first();

    //                         if ($LastRealisasi) {
    //                             if ($DataRealisasi->anggaran !== $LastRealisasi->anggaran) {
    //                                 // if ($LastRealisasi->month != 1) {
    //                                 $LastRealisasi->anggaran_bulan_ini = $LastRealisasi->anggaran;
    //                                 $LastRealisasi->save();
    //                                 // }
    //                                 $DataRealisasi->anggaran_bulan_ini = $DataRealisasi->anggaran - $LastRealisasi->anggaran_bulan_ini;
    //                                 $DataRealisasi->save();
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         DB::commit();
    //         return $return;
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //         ]);
    //     }
    // }

    // function index()
    // {
    //     // set time limit to unlimited
    //     set_time_limit(0);

    //     DB::beginTransaction();
    //     try {
    //         $year = 2024;
    //         // generate realisasi sub kegiatan from data realisasi
    //         $arrSubKegiatans = SubKegiatan::get();

    //         foreach ($arrSubKegiatans as $subKegiatan) {
    //             for ($month = 1; $month <= 12; $month++) {
    //                 $DataRealisasi = Realisasi::where('sub_kegiatan_id', $subKegiatan->id)
    //                     ->where('month', $month)
    //                     ->where('year', $year)
    //                     ->get();
    //                 if (Instance::find($subKegiatan->instance_id) !== null) {
    //                     DB::table('data_realisasi_sub_kegiatan')
    //                         ->updateOrInsert(
    //                             [
    //                                 'year' => $year,
    //                                 'month' => $month,
    //                                 'instance_id' => $subKegiatan->instance_id,
    //                                 'urusan_id' => $subKegiatan->urusan_id,
    //                                 'bidang_urusan_id' => $subKegiatan->bidang_id,
    //                                 'program_id' => $subKegiatan->program_id,
    //                                 'kegiatan_id' => $subKegiatan->kegiatan_id,
    //                                 'sub_kegiatan_id' => $subKegiatan->id,
    //                             ],
    //                             [
    //                                 'realisasi_anggaran' => $DataRealisasi->sum('anggaran') ?? 0,
    //                                 'persentase_realisasi_kinerja' => $DataRealisasi->avg('persentase_kinerja') ?? 0,
    //                             ]
    //                         );
    //                 }
    //             }
    //         }
    //         DB::commit();
    //         return response()->json([
    //             'message' => 'success',
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //         ]);
    //     }
    // }

    // function index()
    // {
    //     DB::beginTransaction();
    //     try {

    //         // $dataKinerjaTarget = TargetKinerja::get();

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //         ]);
    //     }
    // }

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
