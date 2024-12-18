<?php

namespace App\Http\Controllers\API\Accountancy;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Instance;
use Illuminate\Support\Facades\Validator;

class AdminOnlyController extends Controller
{
    use JsonReturner;

    function postSaldoAwal(Request $request)
    {
        $validate = Validator::make($request->all(), [
            // 'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'file' => 'required|file|mimes:xls',
        ], [], [
            // 'instance' => 'Perangkat Daerah',
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
            $fileName = 'SaldoAwal-' . $request->year . '-' . $request->periode . '.' . $file->getClientOriginalExtension();
            $file->move('uploads', $fileName);

            $path = public_path('uploads/' . $fileName);
            // $data = Excel::import(new LRAImport, $path);
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $allData = [];
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $allData = $sheet->rangeToArray('A1:' . $highestColumn . $highestRow, null, true, true, true);
            $allData = collect($allData);
            $allData = $allData->where('A', '!=', null)
                ->where('A', '>=', '1')
                ->where('A', '!=', 'REKAPITULASI SALDO AWAL ASET 2024')
                ->where('A', '!=', 'No')
                ->where('B', '!=', null)
                ->where('C', '!=', null)
                ->where('D', '!=', null)
                ->where('E', '!=', null)
                ->where('F', '!=', null)
                ->where('G', '!=', null)
                ->where('H', '!=', null)
                ->where('I', '!=', null)
                ->where('J', '!=', null)
                ->where('K', '!=', null)
                ->values();

            foreach ($allData as $key => $input) {

                $KibA = $input['C'];
                $KibA = str_replace(',', '', $KibA);
                $KibA = str_replace('.00', '', $KibA);
                $KibA = str_replace('-0', '', $KibA);
                $KibA = (float) $KibA;

                $KibB = $input['D'];
                $KibB = str_replace(',', '', $KibB);
                $KibB = str_replace('.00', '', $KibB);
                $KibB = str_replace('-0', '', $KibB);
                $KibB = (float) $KibB;

                $KibC = $input['E'];
                $KibC = str_replace(',', '', $KibC);
                $KibC = str_replace('.00', '', $KibC);
                $KibC = str_replace('-0', '', $KibC);
                $KibC = (float) $KibC;

                $KibD = $input['F'];
                $KibD = str_replace(',', '', $KibD);
                $KibD = str_replace('.00', '', $KibD);
                $KibD = str_replace('-0', '', $KibD);
                $KibD = (float) $KibD;

                $KibE = $input['G'];
                $KibE = str_replace(',', '', $KibE);
                $KibE = str_replace('.00', '', $KibE);
                $KibE = str_replace('-0', '', $KibE);
                $KibE = (float) $KibE;

                $KDP = $input['H'];
                $KDP = str_replace(',', '', $KDP);
                $KDP = str_replace('.00', '', $KDP);
                $KDP = str_replace('-0', '', $KDP);
                $KDP = (float) $KDP;

                $ATB = $input['I'];
                $ATB = str_replace(',', '', $ATB);
                $ATB = str_replace('.00', '', $ATB);
                $ATB = str_replace('-0', '', $ATB);
                $ATB = (float) $ATB;

                $LAIN = $input['J'];
                $LAIN = str_replace(',', '', $LAIN);
                $LAIN = str_replace('.00', '', $LAIN);
                $LAIN = str_replace('-0', '', $LAIN);
                $LAIN = (float) $LAIN;

                $AsetLainnya = $input['K'];
                $AsetLainnya = str_replace(',', '', $AsetLainnya);
                $AsetLainnya = str_replace('.00', '', $AsetLainnya);
                $AsetLainnya = str_replace('-0', '', $AsetLainnya);
                $AsetLainnya = (float) $AsetLainnya;

                $instance = Instance::where('name', str()->upper($input['B']))->first();
                if ($instance) {
                    // Kib A Start
                    DB::table('acc_rek_as_kib_a')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KibA ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Kib A End
                    // Kib B Start
                    DB::table('acc_rek_as_kib_b')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KibB ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Kib B End
                    // Kib C Start
                    DB::table('acc_rek_as_kib_c')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KibC ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Kib C End
                    // Kib D Start
                    DB::table('acc_rek_as_kib_d')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KibD ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Kib D End
                    // Kib E Start
                    DB::table('acc_rek_as_kib_e')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KibE ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Kib E End
                    // KDP Start
                    DB::table('acc_rek_as_kdp')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $KDP ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // KDP End
                    // Aset Tak Berwujud Start
                    DB::table('acc_rek_as_aset_tak_berwujud')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $ATB ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Aset Tak Berwujud End
                    // Aset Lain Lain Start
                    DB::table('acc_rek_as_aset_lain_lain')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $LAIN ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Aset Lain Lain End
                    // Aset Lainnya Start
                    DB::table('acc_rek_as_aset_lainnya')->updateOrInsert([
                        'periode_id' => $request->periode,
                        'year' => $request->year,
                        'instance_id' => $instance->id,
                    ], [
                        'saldo_awal' => $AsetLainnya ?? 0,
                        'created_by' => auth()->user()->id,
                        'created_at' => $now,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => $now,
                    ]);
                    // Aset Lainnya End
                }
            }

            DB::commit();
            return $this->successResponse($allData, 'Data Saldo Awal berhasil diimport');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
