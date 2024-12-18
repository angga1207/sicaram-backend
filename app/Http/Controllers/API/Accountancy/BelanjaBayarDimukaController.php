<?php

namespace App\Http\Controllers\Api\Accountancy;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Accountancy\BelanjaBayarDimuka;

class BelanjaBayarDimukaController extends Controller
{
    use JsonReturner;

    function getBelanjaBayarDimuka(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|numeric',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = BelanjaBayarDimuka::when($request->instance, function ($query) use ($request) {
                return $query->where('instance_id', $request->instance);
            })
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->orderBy('instance_id')
                ->get();
            return $this->successResponse($datas, 'Penyesuai Aset dan Beban berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeBelanjaBayarDimuka(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|exists:ref_periode,id',
            'year' => 'nullable|numeric',
        ], [], [
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $dateYear = $request->year ?? date('Y');
            foreach ($request->data as $input) {
                if ($input['instance_id']) {
                    if (!$input['id']) {
                        $data = new BelanjaBayarDimuka();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = BelanjaBayarDimuka::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];
                    $data->kode_rekening_id = $input['kode_rekening_id'];
                    $data->uraian = $input['uraian'];
                    $data->nomor_perjanjian = $input['nomor_perjanjian'];
                    $data->tanggal_perjanjian = $input['tanggal_perjanjian'];
                    $data->rekanan = $input['rekanan'];
                    $data->jangka_waktu = $input['jangka_waktu'];
                    $data->kontrak_date_start = $input['kontrak_date_start'];
                    $data->kontrak_date_end = $input['kontrak_date_end'];
                    $data->kontrak_value = $input['kontrak_value'];
                    $data->belum_jatuh_tempo = $input['belum_jatuh_tempo'];
                    $data->sudah_jatuh_tempo = $input['sudah_jatuh_tempo'];
                    $data->save();
                }
            }
            DB::commit();
            return $this->successResponse(null, 'Penyesuai Aset dan Beban berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteBelanjaBayarDimuka(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_belanja_bayar_dimuka,id'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            BelanjaBayarDimuka::find($request->id)->delete();
            DB::commit();
            return $this->successResponse(null, 'Penyesuai Aset dan Beban berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
