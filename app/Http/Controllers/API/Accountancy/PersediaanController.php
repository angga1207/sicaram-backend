<?php

namespace App\Http\Controllers\API\Accountancy;

use App\Models\User;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PersediaanController extends Controller
{
    use JsonReturner;

    // Rekap Start
    function getRekap(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            $arrUraian = [
                'A. Barang Habis Pakai',
                'B. Belanja Persediaan Untuk Dijual',
                'C. Nilai Persediaan Neraca',
            ];

            foreach ($arrUraian as $key => $uraian) {
                $saldoAwal = 0;
                $realisasiLra = 0;
                $hutangBelanja = 0;
                $perolehanHibah = 0;
                $saldoAkhir = 0;
                $beban = 0;

                if ($key == 0) {
                    $arrDatas = DB::table('acc_persediaan_barang_habis_pakai')
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->when($request->instance, function ($query) use ($request) {
                            return $query->where('instance_id', $request->instance);
                        })
                        // ->orderBy('instance_id')
                        ->oldest('created_at')
                        ->get();

                    $saldoAwal = $arrDatas->sum('saldo_awal');
                    $realisasiLra = $arrDatas->sum('realisasi_lra');
                    $hutangBelanja = $arrDatas->sum('hutang_belanja');
                    $perolehanHibah = $arrDatas->sum('perolehan_hibah');
                    $saldoAkhir = $arrDatas->sum('saldo_akhir');
                    $beban = $arrDatas->sum('beban_persediaan');
                } else if ($key == 1) {
                    $arrDatas = DB::table('acc_persediaan_belanja_persediaan_untuk_dijual')
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->when($request->instance, function ($query) use ($request) {
                            return $query->where('instance_id', $request->instance);
                        })
                        // ->orderBy('instance_id')
                        ->oldest('created_at')
                        ->get();

                    $saldoAwal = $arrDatas->sum('saldo_awal');
                    $realisasiLra = $arrDatas->sum('realisasi_lra');
                    $hutangBelanja = $arrDatas->sum('hutang_belanja');
                    $perolehanHibah = $arrDatas->sum('perolehan_hibah');
                    $saldoAkhir = $arrDatas->sum('saldo_akhir');
                    $beban = $arrDatas->sum('beban_hibah');
                } else if ($key == 2) {
                    $arrDatas = DB::table('acc_persediaan_nilai_persediaan_neraca')
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->when($request->instance, function ($query) use ($request) {
                            return $query->where('instance_id', $request->instance);
                        })
                        // ->orderBy('instance_id')
                        ->oldest('created_at')
                        ->get();

                    $saldoAwal = $arrDatas->sum('saldo_awal');
                    $realisasiLra = $arrDatas->sum('realisasi_lra');
                    $hutangBelanja = $arrDatas->sum('hutang_belanja');
                    $perolehanHibah = $arrDatas->sum('perolehan_hibah');
                    $saldoAkhir = $arrDatas->sum('saldo_akhir');
                    $beban = $arrDatas->sum('beban_hibah');
                }

                $datas[] = [
                    'uraian' => $uraian,
                    'saldo_awal' => $saldoAwal,
                    'realisasi_lra' => $realisasiLra,
                    'hutang_belanja' => $hutangBelanja,
                    'perolehan_hibah' => $perolehanHibah,
                    'saldo_akhir' => $saldoAkhir,
                    'beban' => $beban,
                ];
            }
            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    // Rekap End

    // BarangHabisPakai Start
    function getBarangHabisPakai(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            $arrDatas = DB::table('acc_persediaan_barang_habis_pakai')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->oldest('created_at')
                ->get();
            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'nama_persediaan' => $data->nama_persediaan,
                    'saldo_awal' => $data->saldo_awal,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'realisasi_lra' => $data->realisasi_lra,
                    'hutang_belanja' => $data->hutang_belanja,
                    'perolehan_hibah' => $data->perolehan_hibah,
                    'saldo_akhir' => $data->saldo_akhir,
                    'beban_persediaan' => $data->beban_persediaan,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeBarangHabisPakai(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.nama_persediaan' => 'required|string',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.nama_persediaan' => 'Nama Persediaan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $now = date('Y-m-d H:i:s');
            $inputs = $request->data;
            foreach ($inputs as $input) {
                if (!$input['id']) {
                    DB::table('acc_persediaan_barang_habis_pakai')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_persediaan' => $input['beban_persediaan'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_persediaan_barang_habis_pakai')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_persediaan' => $input['beban_persediaan'],
                            'updated_by' => $auth->id,
                            'updated_at' => $now,
                        ]);
                }
            }

            DB::commit();
            return $this->successResponse($request->all(), 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteBarangHabisPakai(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_persediaan_barang_habis_pakai,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_persediaan_barang_habis_pakai')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // BarangHabisPakai End

    // BelanjaPersediaan Start
    function getBelanjaPersediaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            $arrDatas = DB::table('acc_persediaan_belanja_persediaan_untuk_dijual')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->oldest('created_at')
                ->get();
            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'nama_persediaan' => $data->nama_persediaan,
                    'saldo_awal' => $data->saldo_awal,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'realisasi_lra' => $data->realisasi_lra,
                    'hutang_belanja' => $data->hutang_belanja,
                    'perolehan_hibah' => $data->perolehan_hibah,
                    'saldo_akhir' => $data->saldo_akhir,
                    'beban_hibah' => $data->beban_hibah,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeBelanjaPersediaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.nama_persediaan' => 'required|string',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.nama_persediaan' => 'Nama Persediaan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $now = date('Y-m-d H:i:s');
            $inputs = $request->data;
            foreach ($inputs as $input) {
                if (!$input['id']) {
                    DB::table('acc_persediaan_belanja_persediaan_untuk_dijual')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_hibah' => $input['beban_hibah'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_persediaan_belanja_persediaan_untuk_dijual')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_hibah' => $input['beban_hibah'],
                            'updated_by' => $auth->id,
                            'updated_at' => $now,
                        ]);
                }
            }

            DB::commit();
            return $this->successResponse($request->all(), 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteBelanjaPersediaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_persediaan_belanja_persediaan_untuk_dijual,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_persediaan_belanja_persediaan_untuk_dijual')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // BelanjaPersediaan End

    // NilaiPersediaanNeraca Start
    function getNilaiPersediaanNeraca(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            $arrDatas = DB::table('acc_persediaan_nilai_persediaan_neraca')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->oldest('created_at')
                ->get();
            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'nama_persediaan' => $data->nama_persediaan,
                    'saldo_awal' => $data->saldo_awal,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'realisasi_lra' => $data->realisasi_lra,
                    'hutang_belanja' => $data->hutang_belanja,
                    'perolehan_hibah' => $data->perolehan_hibah,
                    'saldo_akhir' => $data->saldo_akhir,
                    'beban_hibah' => $data->beban_hibah,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeNilaiPersediaanNeraca(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.nama_persediaan' => 'required|string',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.nama_persediaan' => 'Nama Persediaan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $now = date('Y-m-d H:i:s');
            $inputs = $request->data;
            foreach ($inputs as $input) {
                if (!$input['id']) {
                    DB::table('acc_persediaan_nilai_persediaan_neraca')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_hibah' => $input['beban_hibah'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_persediaan_nilai_persediaan_neraca')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'nama_persediaan' => $input['nama_persediaan'],
                            'saldo_awal' => $input['saldo_awal'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'realisasi_lra' => $input['realisasi_lra'],
                            'hutang_belanja' => $input['hutang_belanja'],
                            'perolehan_hibah' => $input['perolehan_hibah'],
                            'saldo_akhir' => $input['saldo_akhir'],
                            'beban_hibah' => $input['beban_hibah'],
                            'updated_by' => $auth->id,
                            'updated_at' => $now,
                        ]);
                }
            }

            DB::commit();
            return $this->successResponse($request->all(), 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteNilaiPersediaanNeraca(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_persediaan_nilai_persediaan_neraca,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_persediaan_nilai_persediaan_neraca')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // NilaiPersediaanNeraca End
}
