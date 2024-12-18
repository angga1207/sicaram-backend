<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Instance;
use App\Models\Ref\Periode;
use Illuminate\Support\Str;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Data\TargetKinerja;
use App\Models\Ref\KodeSumberDana;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SIPDController extends Controller
{
    use JsonReturner;

    function listLogs(Request $request)
    {
        $datas = [];
        $logs = DB::table('sipd_upload_logs')
            ->select('*')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        foreach ($logs as $log) {
            $user = User::find($log->user_id);
            $datas[] = [
                'id' => $log->id,
                'created_at' => $log->created_at,
                'file_name' => $log->file_name,
                'message' => $log->message,
                'status' => $log->status,
                'type' => $log->type,
                'author' => $user->fullname,
                'author_photo' => asset($user->photo),
            ];
        }
        return $this->successResponse($datas);
    }

    function uploadApbdFromRekapV5(Request $request)
    {
        // set time limit to 0
        set_time_limit(0);
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ], [], [
            'periode' => 'Periode',
            'file' => 'Berkas Excel',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $reqTahun = $request->year;
            $reqMonth = $request->month;

            $files = glob(storage_path('app/public/rkp5/*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $countMissingSubKegiatan = 0;
            $missingSubKegiatan = [];
            $messages = [];

            $file = $request->file('file');
            $path = $file->store('public/rkp5');
            $path = str_replace('public/', '', $path);
            $path = storage_path('app/public/' . $path);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $allData = [];
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $allData = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, true, true, true);
            $allData = collect($allData);
            // return collect($allData)->where('O', '1.02.02.2.02.0036');

            if (
                $sheet->getCellByColumnAndRow(1, 1)->getValue() !== 'NO' &&
                $sheet->getCellByColumnAndRow(2, 1)->getValue() !== 'TAHUN' &&
                $sheet->getCellByColumnAndRow(3, 1)->getValue() !== 'KODE URUSAN' &&
                $sheet->getCellByColumnAndRow(4, 1)->getValue() !== 'NAMA URUSAN' &&
                $sheet->getCellByColumnAndRow(5, 1)->getValue() !== 'KODE SKPD' &&
                $sheet->getCellByColumnAndRow(6, 1)->getValue() !== 'NAMA SKPD' &&
                $sheet->getCellByColumnAndRow(7, 1)->getValue() !== 'KODE SUB UNIT' &&
                $sheet->getCellByColumnAndRow(8, 1)->getValue() !== 'NAMA SUB UNIT' &&
                $sheet->getCellByColumnAndRow(9, 1)->getValue() !== 'KODE BIDANG URUSAN' &&
                $sheet->getCellByColumnAndRow(10, 1)->getValue() !== 'NAMA BIDANG URUSAN' &&
                $sheet->getCellByColumnAndRow(11, 1)->getValue() !== 'KODE PROGRAM' &&
                $sheet->getCellByColumnAndRow(12, 1)->getValue() !== 'NAMA PROGRAM' &&
                $sheet->getCellByColumnAndRow(13, 1)->getValue() !== 'KODE KEGIATAN' &&
                $sheet->getCellByColumnAndRow(14, 1)->getValue() !== 'NAMA KEGIATAN' &&
                $sheet->getCellByColumnAndRow(15, 1)->getValue() !== 'KODE SUB KEGIATAN' &&
                $sheet->getCellByColumnAndRow(16, 1)->getValue() !== 'NAMA SUB KEGIATAN' &&
                $sheet->getCellByColumnAndRow(17, 1)->getValue() !== 'KODE SUMBER DANA' &&
                $sheet->getCellByColumnAndRow(18, 1)->getValue() !== 'NAMA SUMBER DANA' &&
                $sheet->getCellByColumnAndRow(19, 1)->getValue() !== 'KODE REKENING' &&
                $sheet->getCellByColumnAndRow(20, 1)->getValue() !== 'NAMA REKENING' &&
                $sheet->getCellByColumnAndRow(21, 1)->getValue() !== 'PAKET/KELOMPOK' &&
                $sheet->getCellByColumnAndRow(22, 1)->getValue() !== 'NAMA PAKET/KELOMPOK' &&
                $sheet->getCellByColumnAndRow(23, 1)->getValue() !== 'PAGU'
            ) {
                return $this->errorResponse('Format Excel tidak sesuai');
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            $arrMonths = collect($arrMonths)->skip($reqMonth - 1);
            $arrMonths = $arrMonths->values()->toArray();
            // return $arrMonths;

            // initiate date & userId
            $dateNow = now();
            $userId = auth()->user()->id === 1 ? 6 : auth()->user()->id;

            $allDataInstanceIds = $allData->pluck('G')->unique()->toArray();
            $allDataInstanceIds = collect($allDataInstanceIds)->skip(1);
            $allDataInstanceIds = $allDataInstanceIds->values();


            $checkIfSetdaExists = $allData->pluck('E')->unique()->toArray();
            $checkIfSetdaExists = collect($checkIfSetdaExists)->skip(1);
            $checkIfSetdaExists = $checkIfSetdaExists->values();
            if ($checkIfSetdaExists->contains('4.01.0.00.0.00.01.0000')) {
                $allDataInstanceIds = $allDataInstanceIds->push('4.01.0.00.0.00.01.0000');
            }

            $allDataInstanceIds = $allDataInstanceIds->values()->toArray();
            $instanceIds = Instance::whereIn('code', $allDataInstanceIds)
                ->get()
                ->pluck('id')
                ->toArray();

            $allDataSubKegiatanIds = $allData->pluck('O')->unique()->toArray();
            $allDataSubKegiatanIds = collect($allDataSubKegiatanIds)->skip(1);
            $allDataSubKegiatanIds = $allDataSubKegiatanIds->values()->toArray();

            foreach ($arrMonths as $month) {
                $arrSubKegiatan = DB::table('ref_sub_kegiatan')
                    ->whereIn('instance_id', $instanceIds)
                    ->whereIn('fullcode', $allDataSubKegiatanIds)
                    ->get();
                // return [$arrMonths, $arrSubKegiatan];
                foreach ($arrSubKegiatan as $subKegiatan) {
                    $totalPaguSubKegiatan = $allData->where('O', $subKegiatan->fullcode)->sum('W');
                    DB::table('data_apbd_detail_sub_kegiatan')
                        ->updateOrInsert(
                            [
                                'instance_id' => $subKegiatan->instance_id,
                                'program_id' => $subKegiatan->program_id,
                                'kegiatan_id' => $subKegiatan->kegiatan_id,
                                'sub_kegiatan_id' => $subKegiatan->id,
                                'year' => $reqTahun,
                                'month' => $month,
                            ],
                            [
                                'anggaran_modal' => $totalPaguSubKegiatan,
                                'total_anggaran' => $totalPaguSubKegiatan,
                                'percent_anggaran' => 100,
                                'percent_kinerja' => 100,
                                'status' => 'active',
                                'created_by' => $userId,
                                'updated_by' => null,
                                'created_at' => $dateNow,
                                'updated_at' => $dateNow,
                            ]
                        );
                }

                $arrKegiatans = DB::table('ref_kegiatan')->get();
                foreach ($arrKegiatans as $kegiatan) {
                    $totalPaguKegiatan = $allData->where('M', $kegiatan->fullcode)->sum('W');

                    DB::table('data_apbd_detail_kegiatan')
                        ->updateOrInsert(
                            [
                                'instance_id' => $kegiatan->instance_id,
                                'program_id' => $kegiatan->program_id,
                                'kegiatan_id' => $kegiatan->id,
                                'year' => $reqTahun,
                                'month' => $month,
                            ],
                            [
                                'anggaran_modal' => $totalPaguKegiatan ?? 0,
                                'total_anggaran' => $totalPaguKegiatan ?? 0,
                                'percent_anggaran' => 100,
                                'percent_kinerja' => 100,
                                'status' => 'active',
                                'created_by' => $userId,
                                'updated_by' => null,
                                'created_at' => $dateNow,
                                'updated_at' => $dateNow,
                            ]
                        );
                }

                $arrPrograms = DB::table('ref_program')->get();
                foreach ($arrPrograms as $program) {
                    $totalPaguProgram = $allData->where('K', $program->fullcode)->sum('W');

                    DB::table('data_apbd')
                        ->updateOrInsert(
                            [
                                'instance_id' => $program->instance_id,
                                'program_id' => $program->id,
                                'year' => $reqTahun,
                                'month' => $month,
                            ],
                            [
                                'total_anggaran' => $totalPaguProgram ?? 0,
                                'percent_anggaran' => 100,
                                'percent_kinerja' => 100,
                                'status' => 'verified',
                                'created_by' => $userId,
                                'updated_by' => null,
                                'created_at' => $dateNow,
                                'updated_at' => $dateNow,
                            ]
                        );
                }
            }

            $logs = DB::table('sipd_upload_logs')
                ->insert([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'status' => 'success',
                    'message' => $request->message,
                    'type' => 'apbd',
                    'user_id' => $userId,
                    'created_at' => $dateNow,
                    'updated_at' => $dateNow,
                ]);

            DB::commit();
            return $this->successResponse($messages, 'Data APBD Berhasil diupload');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine() . ' - ' . $th->getFile());
        }
    }

    // upload an untuk input target sebelum realisasi
    function uploadSubToRekening(Request $request)
    {
        // handle 524 error
        // ini_set('memory_limit', '2G');
        set_time_limit(0);
        ini_set('max_input_time', 3600);

        // upload_max_filesize = 100M
        // post_max_size = 100M
        // max_execution_time = 300
        // max_input_time = 300

        $validate = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
        ], [], [
            'file' => 'Berkas Excel',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $files = glob(storage_path('app/public/rkp5/*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $reqTahun = $request->year;
            $reqMonth = $request->month;

            $countMissingSubKegiatan = 0;
            $missingSubKegiatan = [];
            $messages = [];

            $file = $request->file('file');
            $path = $file->store('public/rkp5');
            $path = str_replace('public/', '', $path);
            $path = storage_path('app/public/' . $path);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            if (
                $sheet->getCellByColumnAndRow(1, 1)->getValue() !== 'NO' &&
                $sheet->getCellByColumnAndRow(2, 1)->getValue() !== 'TAHUN' &&
                $sheet->getCellByColumnAndRow(3, 1)->getValue() !== 'KODE URUSAN' &&
                $sheet->getCellByColumnAndRow(4, 1)->getValue() !== 'NAMA URUSAN' &&
                $sheet->getCellByColumnAndRow(5, 1)->getValue() !== 'KODE SKPD' &&
                $sheet->getCellByColumnAndRow(6, 1)->getValue() !== 'NAMA SKPD' &&
                $sheet->getCellByColumnAndRow(7, 1)->getValue() !== 'KODE SUB UNIT' &&
                $sheet->getCellByColumnAndRow(8, 1)->getValue() !== 'NAMA SUB UNIT' &&
                $sheet->getCellByColumnAndRow(9, 1)->getValue() !== 'KODE BIDANG URUSAN' &&
                $sheet->getCellByColumnAndRow(10, 1)->getValue() !== 'NAMA BIDANG URUSAN' &&
                $sheet->getCellByColumnAndRow(11, 1)->getValue() !== 'KODE PROGRAM' &&
                $sheet->getCellByColumnAndRow(12, 1)->getValue() !== 'NAMA PROGRAM' &&
                $sheet->getCellByColumnAndRow(13, 1)->getValue() !== 'KODE KEGIATAN' &&
                $sheet->getCellByColumnAndRow(14, 1)->getValue() !== 'NAMA KEGIATAN' &&
                $sheet->getCellByColumnAndRow(15, 1)->getValue() !== 'KODE SUB KEGIATAN' &&
                $sheet->getCellByColumnAndRow(16, 1)->getValue() !== 'NAMA SUB KEGIATAN' &&
                $sheet->getCellByColumnAndRow(17, 1)->getValue() !== 'KODE SUMBER DANA' &&
                $sheet->getCellByColumnAndRow(18, 1)->getValue() !== 'NAMA SUMBER DANA' &&
                $sheet->getCellByColumnAndRow(19, 1)->getValue() !== 'KODE REKENING' &&
                $sheet->getCellByColumnAndRow(20, 1)->getValue() !== 'NAMA REKENING' &&
                $sheet->getCellByColumnAndRow(21, 1)->getValue() !== 'PAKET/KELOMPOK' &&
                $sheet->getCellByColumnAndRow(22, 1)->getValue() !== 'NAMA PAKET/KELOMPOK' &&
                $sheet->getCellByColumnAndRow(23, 1)->getValue() !== 'PAGU'
            ) {
                return $this->errorResponse('Format Excel tidak sesuai');
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 2; $row <= $highestRow; $row++) {
                $tahun = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $kodeUrusan = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                $namaUrusan = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                $kodeSkpd = $sheet->getCellByColumnAndRow(5, $row)->getValue();
                $namaSkpd = $sheet->getCellByColumnAndRow(6, $row)->getValue();
                $kodeSubUnit = $sheet->getCellByColumnAndRow(7, $row)->getValue();
                $namaSubUnit = $sheet->getCellByColumnAndRow(8, $row)->getValue();
                $kodeBidangUrusan = $sheet->getCellByColumnAndRow(9, $row)->getValue();
                $namaBidangUrusan = $sheet->getCellByColumnAndRow(10, $row)->getValue();
                $kodeProgram = $sheet->getCellByColumnAndRow(11, $row)->getValue();
                $namaProgram = $sheet->getCellByColumnAndRow(12, $row)->getValue();
                $kodeKegiatan = $sheet->getCellByColumnAndRow(13, $row)->getValue();
                $namaKegiatan = $sheet->getCellByColumnAndRow(14, $row)->getValue();
                $kodeSubKegiatan = $sheet->getCellByColumnAndRow(15, $row)->getValue();
                $namaSubKegiatan = $sheet->getCellByColumnAndRow(16, $row)->getValue();
                $kodeSumberDana = $sheet->getCellByColumnAndRow(17, $row)->getValue();
                $namaSumberDana = $sheet->getCellByColumnAndRow(18, $row)->getValue();
                $kodeRekening = $sheet->getCellByColumnAndRow(19, $row)->getValue();
                $namaRekening = $sheet->getCellByColumnAndRow(20, $row)->getValue();
                $jenis = $sheet->getCellByColumnAndRow(21, $row)->getValue();
                $namaPaket = $sheet->getCellByColumnAndRow(22, $row)->getValue();
                $pagu = $sheet->getCellByColumnAndRow(23, $row)->getValue();

                if ($kodeSkpd == '4.01.0.00.0.00.01.0000') {
                    $instance = Instance::where('code', $kodeSkpd)->first();
                } else {
                    $instance = Instance::where('code', $kodeSubUnit)->first();
                }
                // if (!$instance) {
                //     // return $this->errorResponse('Perangkat Daerah tidak ditemukan');
                //     continue;
                // }
                if ($instance) {
                    // Makai Get Karena Kode Sub Kegiatan Mungkin memiliki lebih dari satu sub kegiatan di database
                    $arrSubKegiatan = SubKegiatan::where('fullcode', $kodeSubKegiatan)
                        ->where('instance_id', $instance->id)
                        ->get();

                    $sumberDana = KodeSumberDana::where('fullcode', $kodeSumberDana)->first();
                    if (!$sumberDana && $kodeSumberDana) {
                        $fullcode = null;
                        $code1 = null;
                        $code2 = null;
                        $code3 = null;
                        $code4 = null;
                        $code5 = null;
                        $code6 = null;
                        if ($fullcode !== null) {
                            $fullcode = (string)$kodeSumberDana;
                            $code1 = substr($fullcode, 0, 1);
                            $code2 = substr($fullcode, 2, 1);
                            if ($code2 === '') {
                                $code2 = null;
                            }
                            $code3 = substr($fullcode, 4, 2);
                            if ($code3 === '') {
                                $code3 = null;
                            }
                            $code4 = substr($fullcode, 7, 2);
                            if ($code4 === '') {
                                $code4 = null;
                            }
                            $code5 = substr($fullcode, 10, 2);
                            if ($code5 === '') {
                                $code5 = null;
                            }
                            $code6 = substr($fullcode, 13, 4);
                            if ($code6 === '') {
                                $code6 = null;
                            }
                        }
                        $sumberDana = new KodeSumberDana();
                        $sumberDana->fullcode = $kodeSumberDana;
                        $sumberDana->name = $namaSumberDana;
                        $sumberDana->periode_id = 1;
                        $sumberDana->year = $tahun;
                        $sumberDana->code_1 = $code1;
                        $sumberDana->code_2 = $code2;
                        $sumberDana->code_3 = $code3;
                        $sumberDana->code_4 = $code4;
                        $sumberDana->code_5 = $code5;
                        $sumberDana->code_6 = $code6;
                        $sumberDana->created_by = auth()->user()->id;

                        $parent = KodeSumberDana::where('fullcode', substr($kodeSumberDana, 0, 4))->first();
                        if ($parent) {
                            $sumberDana->parent_id = $parent->id;
                        }
                        $sumberDana->save();
                    }

                    $rekening = KodeRekening::where('fullcode', $kodeRekening)->first() ?? null;
                    if (!$rekening) {
                        $expKodeRekening = Str::of($kodeRekening)->explode(".");
                        if ($expKodeRekening->count() == 6) {
                            $rekening = new KodeRekening();
                            $rekening->code_1 = $expKodeRekening[0];
                            $rekening->code_2 = $expKodeRekening[1];
                            $rekening->code_3 = $expKodeRekening[2];
                            $rekening->code_4 = $expKodeRekening[3];
                            $rekening->code_5 = $expKodeRekening[4];
                            $rekening->code_6 = $expKodeRekening[5];
                            $rekening->fullcode = $kodeRekening;
                            $rekening->name = $namaRekening;
                            $rekening->periode_id = $request->periode ?? 1;
                            $rekening->year = $tahun;
                            $rekening->parent_id = KodeRekening::where('code_1', $rekening->code_1)
                                ->where('code_2', $rekening->code_2)
                                ->where('code_3', $rekening->code_3)
                                ->where('code_4', $rekening->code_4)
                                ->where('code_5', $rekening->code_5)
                                ->first()->id ?? null;
                            $rekening->save();
                        }
                    }

                    if ($rekening) {
                        if ($arrSubKegiatan->count() > 0) {
                            foreach ($arrSubKegiatan as $subKegiatan) {
                                if ($subKegiatan) {
                                    $periode = Periode::whereYear('start_date', '<=', $tahun)
                                        ->whereYear('end_date', '>=', $tahun)
                                        ->first();

                                    $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
                                    $arrMonths = collect($arrMonths)->skip($reqMonth - 1);
                                    $arrMonths = $arrMonths->values()->toArray();

                                    foreach ($arrMonths as $month) {
                                        $targetKinerja = TargetKinerja::where('year', $tahun)
                                            ->where('month', $month)
                                            ->where('instance_id', $instance->id)
                                            ->where('sub_kegiatan_id', $subKegiatan->id)
                                            ->where('kode_rekening_id', $rekening->id)
                                            ->where('sumber_dana_id', $sumberDana->id)
                                            ->where('type', $jenis)
                                            ->where('nama_paket', $namaPaket)
                                            ->first();
                                        if (!$targetKinerja) {
                                            $targetKinerja = new TargetKinerja();
                                            $targetKinerja->year = $tahun;
                                            $targetKinerja->month = $month;
                                            $targetKinerja->instance_id = $instance->id;
                                            $targetKinerja->urusan_id = $subKegiatan->urusan_id ?? null;
                                            $targetKinerja->bidang_urusan_id = $subKegiatan->bidang_id ?? null;
                                            $targetKinerja->program_id = $subKegiatan->program_id ?? null;
                                            $targetKinerja->kegiatan_id = $subKegiatan->kegiatan_id ?? null;
                                            $targetKinerja->sub_kegiatan_id = $subKegiatan->id ?? null;
                                            $targetKinerja->created_by = auth()->user()->id;
                                            $targetKinerja->status = 'draft';
                                            $targetKinerja->status_leader = 'draft';
                                        }


                                        // Pengecualian Detail Start
                                        if ($rekening) {
                                            // Jika Kode 5.2
                                            if ($rekening->code_1 === '5' && $rekening->code_2 === '2') {
                                                $targetKinerja->is_detail = TRUE;
                                            }
                                            // Jika Kode 5.1.01, 5.1.03, 5.1.04
                                            if (
                                                $rekening->code_1 === '5' && $rekening->code_2 === '1' &&
                                                ($rekening->code_3 === '01' || $rekening->code_3 === '03' || $rekening->code_3 === '04')
                                            ) {

                                                $targetKinerja->is_detail = TRUE;
                                            }
                                        }

                                        $targetKinerja->sumber_dana_id = $sumberDana->id ?? null;
                                        $targetKinerja->kode_rekening_id = $rekening->id ?? null;
                                        $targetKinerja->pagu_sipd = $pagu ?? 0;
                                        // $targetKinerja->pagu_sebelum_pergeseran = $rekening->pagu_sebelum_pergeseran;
                                        // $targetKinerja->pagu_setelah_pergeseran = $rekening->pagu_setelah_pergeseran;
                                        // $targetKinerja->pagu_selisih = $rekening->pagu_selisih;

                                        $targetKinerja->periode_id = $periode->id;
                                        $targetKinerja->type = $jenis;
                                        $targetKinerja->nama_paket = $namaPaket;
                                        $targetKinerja->save();

                                        $realisasi = Realisasi::where('year', $tahun)
                                            ->where('month', $month)
                                            ->where('periode_id', $periode->id)
                                            ->where('instance_id', $instance->id)
                                            ->where('target_id', $targetKinerja->id)
                                            ->where('sub_kegiatan_id', $subKegiatan->id)
                                            ->where('kode_rekening_id', $rekening->id)
                                            ->where('sumber_dana_id', $sumberDana->id)
                                            ->where('type', $jenis)
                                            ->where('nama_paket', $namaPaket)
                                            ->first();
                                        if (!$realisasi) {
                                            $realisasi = new Realisasi();
                                            $realisasi->periode_id = $periode->id;
                                            $realisasi->year = $tahun;
                                            $realisasi->month = $month;
                                            $realisasi->instance_id = $instance->id;
                                            $realisasi->target_id = $targetKinerja->id;
                                            $realisasi->urusan_id = $subKegiatan->urusan_id ?? null;
                                            $realisasi->bidang_urusan_id = $subKegiatan->bidang_id ?? null;
                                            $realisasi->program_id = $subKegiatan->program_id ?? null;
                                            $realisasi->kegiatan_id = $subKegiatan->kegiatan_id ?? null;
                                            $realisasi->sub_kegiatan_id = $subKegiatan->id ?? null;
                                            $realisasi->kode_rekening_id = $rekening->id ?? null;
                                            $realisasi->sumber_dana_id = $sumberDana->id ?? null;
                                            $realisasi->type = $jenis;
                                            $realisasi->nama_paket = $namaPaket;
                                            $realisasi->created_by = auth()->user()->id;
                                            $realisasi->status = 'draft';
                                            $realisasi->status_leader = 'draft';
                                            $realisasi->save();
                                        }
                                    }
                                }
                            }
                        } else {
                            $countMissingSubKegiatan++;
                            $missingSubKegiatan[] = [
                                'kode_sub_kegiatan' => $kodeSubKegiatan,
                                'nama_sub_kegiatan' => $namaSubKegiatan,
                                'nama_instansi' => $namaSubUnit,
                            ];
                        }
                    }
                }
            }

            if (count($missingSubKegiatan) > 0) {
                $missingSubKegiatan = collect($missingSubKegiatan);
                // $missingSubKegiatan remove duplicate data
                $missingSubKegiatan = $missingSubKegiatan->unique('kode_sub_kegiatan');
                $missingSubKegiatan = $missingSubKegiatan->sortBy('kode_sub_kegiatan')->values()->all();

                $messages['message'] = 'Terdapat ' . count($missingSubKegiatan) . ' Sub Kegiatan yang tidak terdeteksi';
                $messages['missing_data'] = $missingSubKegiatan;
            }

            $logs = DB::table('sipd_upload_logs')
                ->insert([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'status' => 'success',
                    'message' => json_encode($messages),
                    'type' => 'target-belanja',
                    'user_id' => auth()->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return $this->successResponse($messages, 'Data Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }
}
