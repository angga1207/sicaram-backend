<?php

namespace App\Http\Controllers\API\Accountancy;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class LRAController extends Controller
{
    use JsonReturner;

    // getLRA
    function getLRA(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'year' => 'Tahun',
            'periode' => 'Periode',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            if ($request->instance) {
                $datas = DB::table('acc_lra')
                    ->where('instance_id', $request->instance)
                    ->where('year', $request->year)
                    ->where('periode_id', $request->periode)
                    ->get();
            } elseif (!$request->instance) {
                $arrKodeRekening = DB::table('acc_lra')
                    ->select('kode_rekening')
                    ->where('year', $request->year)
                    ->where('periode_id', $request->periode)
                    ->groupBy('kode_rekening')
                    ->get()
                    ->pluck('kode_rekening')
                    ->toArray();

                foreach ($arrKodeRekening as $key => $kodeRekening) {
                    $data = DB::table('acc_lra')
                        ->where('kode_rekening', $kodeRekening)
                        ->where('year', $request->year)
                        ->where('periode_id', $request->periode)
                        ->get();
                    $datas[] = [
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => null,
                        'kode_rekening' => $kodeRekening,
                        'uraian' => $data[0]->uraian ?? '-',
                        'anggaran' => $data->sum('anggaran'),
                        'realisasi' => $data->sum('realisasi'),
                        'realisasi_last_year' => $data->sum('realisasi_last_year'),
                        'realisasi_percentage' => $data->avg('realisasi_percentage'),
                    ];
                }
                $datas = collect($datas);
                $datas = $datas->sortBy('kode_rekening')->values();
            }
            return $this->successResponse($datas, 'Data LRA berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // postLRA
    function postLRA(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'periode' => 'Periode',
            'year' => 'Tahun',
            'file' => 'Berkas LRA'
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $now = now();

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $fileName = 'LRA-' . $request->instance . '-' . $request->year . '-' . $request->periode . '.' . $file->getClientOriginalExtension();
            $file->move('uploads', $fileName);

            $path = public_path('uploads/' . $fileName);
            // $data = Excel::import(new LRAImport, $path);
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $allData = [];
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $allData = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, true, true, true);
            $allData = collect($allData);
            $allData = $allData->where('A', '!=', null)
                ->where('A', '!=', '1')
                ->where('A', '!=', 'Kode Rekening')
                ->where('B', '!=', null)
                ->where('C', '!=', null)
                ->where('D', '!=', null)
                ->where('E', '!=', null)
                ->where('F', '!=', null)
                ->values();

            foreach ($allData as $key => $input) {

                $anggaran = $input['C'];
                $anggaran = str_replace('.', '', $anggaran);
                $anggaran = str_replace(',00', '', $anggaran);
                $anggaran = (float) $anggaran;

                $realisasi = $input['D'];
                $realisasi = str_replace('.', '', $realisasi);
                $realisasi = str_replace(',00', '', $realisasi);
                $realisasi = (float) $realisasi;

                $realisasiLastYear = $input['F'];
                $realisasiLastYear = str_replace('.', '', $realisasiLastYear);
                $realisasiLastYear = str_replace(',00', '', $realisasiLastYear);
                $realisasiLastYear = (float) $realisasiLastYear;

                $realisasiPercentage = $input['E'];
                $realisasiPercentage = str_replace(',', '.', $realisasiPercentage);
                $realisasiPercentage = (float) $realisasiPercentage;

                // return [$anggaran, $realisasi, $realisasiLastYear, $realisasiPercentage];

                DB::table('acc_lra')->updateOrInsert([
                    'periode_id' => $request->periode,
                    'year' => $request->year,
                    'instance_id' => $request->instance,
                    'kode_rekening' => $input['A'],
                ], [
                    'periode_id' => $request->periode,
                    'year' => $request->year,
                    'instance_id' => $request->instance,
                    'kode_rekening' => $input['A'],
                    'uraian' => $input['B'],
                    'anggaran' => $anggaran,
                    'realisasi' => $realisasi,
                    'realisasi_last_year' => $realisasiLastYear,
                    'realisasi_percentage' => $realisasiPercentage,
                    'updated_by' => auth()->user()->id,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();
            return $this->successResponse($allData, 'Data LRA berhasil diimport');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function resetLRA(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'periode' => 'Periode',
            'year' => 'Tahun',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_lra')
                ->where('instance_id', $request->instance)
                ->where('year', $request->year)
                ->where('periode_id', $request->periode)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data LRA berhasil direset');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
