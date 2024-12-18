<?php

namespace App\Http\Controllers\API\Accountancy;

use App\Imports\LRAImport;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Accountancy\PADB\Atribusi;
use Illuminate\Support\Facades\Validator;
use App\Models\Accountancy\PADB\BarjasKeAset;
use App\Models\Accountancy\PADB\ModalKeBeban;
use App\Models\Accountancy\PADB\PenyesuaianAset;
use App\Models\Accountancy\PADB\PenyesuaianBebanBarjas;
use App\Models\User;

class PenyesuaianAsetDanBebanController extends Controller
{
    use JsonReturner;

    // Penyesuaian Beban Barjas Start
    function getPenyesuaianBebanBarjas(Request $request)
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
            $datas = PenyesuaianBebanBarjas::when($request->instance, function ($query) use ($request) {
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

    function storePenyesuaianBebanBarjas(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
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
                if ($input['instance_id'] && $input['kode_rekening_id']) {
                    if (!$input['id']) {
                        $data = new PenyesuaianBebanBarjas();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = PenyesuaianBebanBarjas::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];
                    $data->kode_rekening_id = $input['kode_rekening_id'];
                    $data->nama_barang_pekerjaan = $input['nama_barang_pekerjaan'];
                    $data->nomor_kontrak = $input['nomor_kontrak'];
                    $data->nomor_sp2d = $input['nomor_sp2d'];

                    $data->plus_beban_pegawai = $input['plus_beban_pegawai'];
                    $data->plus_beban_persediaan = $input['plus_beban_persediaan'];
                    $data->plus_beban_jasa = $input['plus_beban_jasa'];
                    $data->plus_beban_pemeliharaan = $input['plus_beban_pemeliharaan'];
                    $data->plus_beban_perjalanan_dinas = $input['plus_beban_perjalanan_dinas'];
                    $data->plus_beban_hibah = $input['plus_beban_hibah'];
                    $data->plus_beban_lain_lain = $input['plus_beban_lain_lain'];
                    $data->plus_jumlah_penyesuaian = $input['plus_jumlah_penyesuaian'];

                    $data->min_beban_pegawai = $input['min_beban_pegawai'];
                    $data->min_beban_persediaan = $input['min_beban_persediaan'];
                    $data->min_beban_jasa = $input['min_beban_jasa'];
                    $data->min_beban_pemeliharaan = $input['min_beban_pemeliharaan'];
                    $data->min_beban_perjalanan_dinas = $input['min_beban_perjalanan_dinas'];
                    $data->min_beban_hibah = $input['min_beban_hibah'];
                    $data->min_beban_lain_lain = $input['min_beban_lain_lain'];
                    $data->min_jumlah_penyesuaian = $input['min_jumlah_penyesuaian'];

                    $data->save();
                }
            }
            DB::commit();

            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->successResponse($request->data, 'test');
    }

    function deletePenyesuaianBebanBarjas(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'nullable|exists:acc_padb_penyesuaian_beban_barjas,id',
        ], [], [
            'id' => 'Data Id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = PenyesuaianBebanBarjas::find($request->id);
            $data->deleted_by = auth()->user()->id;
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Penyesuaian Beban Barjas End


    // Modal Ke Beban Start
    function getModalKeBeban(Request $request)
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
            $arrDatas = ModalKeBeban::when($request->instance, function ($query) use ($request) {
                return $query->where('instance_id', $request->instance);
            })
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->orderBy('instance_id')
                ->get();
            $datas = [];
            foreach ($arrDatas as $data) {
                $minJumlahPenyesuaian = ($data->min_aset_tetap_tanah ?? 0) + ($data->min_aset_tetap_peralatan_mesin ?? 0) + ($data->min_aset_tetap_gedung_bangunan ?? 0) + ($data->min_aset_tetap_jalan_jaringan_irigasi ?? 0) + ($data->min_aset_tetap_lainnya ?? 0) + ($data->min_konstruksi_dalam_pekerjaan ?? 0) + ($data->min_aset_lain_lain ?? 0);
                $datas[] = [
                    'id' => $data->id,
                    'instance_id' => $data->instance_id,
                    'instance_name' => $data->instance->name ?? '',
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'nama_barang_pekerjaan' => $data->nama_barang_pekerjaan,
                    'nomor_kontrak' => $data->nomor_kontrak,
                    'nomor_sp2d' => $data->nomor_sp2d,
                    'plus_beban_pegawai' => $data->plus_beban_pegawai,
                    'plus_beban_persediaan' => $data->plus_beban_persediaan,
                    'plus_beban_jasa' => $data->plus_beban_jasa,
                    'plus_beban_pemeliharaan' => $data->plus_beban_pemeliharaan,
                    'plus_beban_perjalanan_dinas' => $data->plus_beban_perjalanan_dinas,
                    'plus_beban_hibah' => $data->plus_beban_hibah,
                    'plus_beban_lain_lain' => $data->plus_beban_lain_lain,
                    'plus_jumlah_penyesuaian' => $data->plus_jumlah_penyesuaian,
                    'min_aset_tetap_tanah' => $data->min_aset_tetap_tanah,
                    'min_aset_tetap_peralatan_mesin' => $data->min_aset_tetap_peralatan_mesin,
                    'min_aset_tetap_gedung_bangunan' => $data->min_aset_tetap_gedung_bangunan,
                    'min_aset_tetap_jalan_jaringan_irigasi' => $data->min_aset_tetap_jalan_jaringan_irigasi,
                    'min_aset_tetap_lainnya' => $data->min_aset_tetap_lainnya,
                    'min_konstruksi_dalam_pekerjaan' => $data->min_konstruksi_dalam_pekerjaan,
                    'min_aset_lain_lain' => $data->min_aset_lain_lain,
                    // 'min_jumlah_penyesuaian' => $data->min_jumlah_penyesuaian,
                    'min_jumlah_penyesuaian' => $minJumlahPenyesuaian ?? 0,

                    'created_by' => User::find($data->created_by)->fullname ?? '',
                    'updated_by' => User::find($data->updated_by)->fullname ?? '',
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeModalKeBeban(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|exists:ref_periode,id',
            'year' => 'nullable|integer',
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
                if ($input['instance_id'] && $input['kode_rekening_id']) {
                    if (!$input['id']) {
                        $data = new ModalKeBeban();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = ModalKeBeban::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];
                    $data->kode_rekening_id = $input['kode_rekening_id'];
                    $data->nama_barang_pekerjaan = $input['nama_barang_pekerjaan'];
                    $data->nomor_kontrak = $input['nomor_kontrak'];
                    $data->nomor_sp2d = $input['nomor_sp2d'];

                    $data->plus_beban_pegawai = $input['plus_beban_pegawai'];
                    $data->plus_beban_persediaan = $input['plus_beban_persediaan'];
                    $data->plus_beban_jasa = $input['plus_beban_jasa'];
                    $data->plus_beban_pemeliharaan = $input['plus_beban_pemeliharaan'];
                    $data->plus_beban_perjalanan_dinas = $input['plus_beban_perjalanan_dinas'];
                    $data->plus_beban_hibah = $input['plus_beban_hibah'];
                    $data->plus_beban_lain_lain = $input['plus_beban_lain_lain'];
                    $data->plus_jumlah_penyesuaian = $input['plus_jumlah_penyesuaian'];

                    $data->min_aset_tetap_tanah = $input['min_aset_tetap_tanah'];
                    $data->min_aset_tetap_peralatan_mesin = $input['min_aset_tetap_peralatan_mesin'];
                    $data->min_aset_tetap_gedung_bangunan = $input['min_aset_tetap_gedung_bangunan'];
                    $data->min_aset_tetap_jalan_jaringan_irigasi = $input['min_aset_tetap_jalan_jaringan_irigasi'];
                    $data->min_aset_tetap_lainnya = $input['min_aset_tetap_lainnya'];
                    $data->min_konstruksi_dalam_pekerjaan = $input['min_konstruksi_dalam_pekerjaan'];
                    $data->min_aset_lain_lain = $input['min_aset_lain_lain'];
                    $data->min_jumlah_penyesuaian = $input['min_jumlah_penyesuaian'];

                    $data->save();
                }
            }
            DB::commit();

            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteModalKeBeban(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'nullable|exists:acc_padb_modal_ke_beban,id',
        ], [], [
            'id' => 'Data Id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = ModalKeBeban::find($request->id);
            $data->deleted_by = auth()->user()->id;
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Modal Ke Beban End


    // Barjas Ke Aset Start
    function getBarjasKeAset(Request $request)
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
            $datas = BarjasKeAset::when($request->instance, function ($query) use ($request) {
                return $query->where('instance_id', $request->instance);
            })
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->orderBy('instance_id')
                ->get();
            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeBarjasKeAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|exists:ref_periode,id',
            'year' => 'nullable|integer',
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
                if ($input['instance_id'] && $input['kode_rekening_id']) {
                    if (!$input['id']) {
                        $data = new BarjasKeAset();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = BarjasKeAset::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];
                    $data->kode_rekening_id = $input['kode_rekening_id'];
                    $data->nama_barang_pekerjaan = $input['nama_barang_pekerjaan'];
                    $data->nomor_kontrak = $input['nomor_kontrak'];
                    $data->nomor_sp2d = $input['nomor_sp2d'];

                    $data->plus_aset_tetap_tanah = $input['plus_aset_tetap_tanah'];
                    $data->plus_aset_tetap_peralatan_mesin = $input['plus_aset_tetap_peralatan_mesin'];
                    $data->plus_aset_tetap_gedung_bangunan = $input['plus_aset_tetap_gedung_bangunan'];
                    $data->plus_aset_tetap_jalan_jaringan_irigasi = $input['plus_aset_tetap_jalan_jaringan_irigasi'];
                    $data->plus_aset_tetap_lainnya = $input['plus_aset_tetap_lainnya'];
                    $data->plus_konstruksi_dalam_pekerjaan = $input['plus_konstruksi_dalam_pekerjaan'];
                    $data->plus_aset_lain_lain = $input['plus_aset_lain_lain'];
                    $data->plus_jumlah_penyesuaian = $input['plus_jumlah_penyesuaian'];

                    $data->min_beban_pegawai = $input['min_beban_pegawai'];
                    $data->min_beban_persediaan = $input['min_beban_persediaan'];
                    $data->min_beban_jasa = $input['min_beban_jasa'];
                    $data->min_beban_pemeliharaan = $input['min_beban_pemeliharaan'];
                    $data->min_beban_perjalanan_dinas = $input['min_beban_perjalanan_dinas'];
                    $data->min_beban_hibah = $input['min_beban_hibah'];
                    $data->min_beban_lain_lain = $input['min_beban_lain_lain'];
                    $data->min_jumlah_penyesuaian = $input['min_jumlah_penyesuaian'];

                    $data->save();
                }
            }
            DB::commit();

            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteBarjasKeAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'nullable|exists:acc_padb_barjas_ke_aset,id',
        ], [], [
            'id' => 'Data Id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = BarjasKeAset::find($request->id);
            $data->deleted_by = auth()->user()->id;
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Barjas Ke Aset End

    // Penyesuaian Aset Start
    function getPenyesuaianAset(Request $request)
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
            $datas = PenyesuaianAset::when($request->instance, function ($query) use ($request) {
                return $query->where('instance_id', $request->instance);
            })
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->orderBy('instance_id')
                ->get();
            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storePenyesuaianAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|exists:ref_periode,id',
            'year' => 'nullable|integer',
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
                if ($input['instance_id'] && $input['kode_rekening_id']) {
                    if (!$input['id']) {
                        $data = new PenyesuaianAset();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = PenyesuaianAset::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];
                    $data->kode_rekening_id = $input['kode_rekening_id'];
                    $data->nama_barang_pekerjaan = $input['nama_barang_pekerjaan'];
                    $data->nomor_kontrak = $input['nomor_kontrak'];
                    $data->nomor_sp2d = $input['nomor_sp2d'];

                    $data->plus_aset_tetap_tanah = $input['plus_aset_tetap_tanah'];
                    $data->plus_aset_tetap_peralatan_mesin = $input['plus_aset_tetap_peralatan_mesin'];
                    $data->plus_aset_tetap_gedung_bangunan = $input['plus_aset_tetap_gedung_bangunan'];
                    $data->plus_aset_tetap_jalan_jaringan_irigasi = $input['plus_aset_tetap_jalan_jaringan_irigasi'];
                    $data->plus_aset_tetap_lainnya = $input['plus_aset_tetap_lainnya'];
                    $data->plus_konstruksi_dalam_pekerjaan = $input['plus_konstruksi_dalam_pekerjaan'];
                    $data->plus_aset_lain_lain = $input['plus_aset_lain_lain'];
                    $data->plus_jumlah_penyesuaian = $input['plus_jumlah_penyesuaian'];

                    $data->min_aset_tetap_tanah = $input['min_aset_tetap_tanah'];
                    $data->min_aset_tetap_peralatan_mesin = $input['min_aset_tetap_peralatan_mesin'];
                    $data->min_aset_tetap_gedung_bangunan = $input['min_aset_tetap_gedung_bangunan'];
                    $data->min_aset_tetap_jalan_jaringan_irigasi = $input['min_aset_tetap_jalan_jaringan_irigasi'];
                    $data->min_aset_tetap_lainnya = $input['min_aset_tetap_lainnya'];
                    $data->min_konstruksi_dalam_pekerjaan = $input['min_konstruksi_dalam_pekerjaan'];
                    $data->min_aset_lain_lain = $input['min_aset_lain_lain'];
                    $data->min_jumlah_penyesuaian = $input['min_jumlah_penyesuaian'];

                    $data->save();
                }
            }
            DB::commit();

            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deletePenyesuaianAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'nullable|exists:acc_padb_penyesuaian_aset,id',
        ], [], [
            'id' => 'Data Id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = PenyesuaianAset::find($request->id);
            $data->deleted_by = auth()->user()->id;
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Penyesuaian Aset End

    // Atribusi Start
    function getAtribusi(Request $request)
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
            $datas = Atribusi::when($request->instance, function ($query) use ($request) {
                return $query->where('instance_id', $request->instance);
            })
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->orderBy('instance_id')
                ->get();
            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeAtribusi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|exists:ref_periode,id',
            'year' => 'nullable|integer',
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
                if ($input['instance_id'] && ($input['bel_peg_kode_rekening_id'] || $input['bel_barjas_kode_rekening_id'] || $input['bel_modal_kode_rekening_id'])) {
                    if (!$input['id']) {
                        $data = new Atribusi();
                        $data->created_by = auth()->user()->id;
                    } else {
                        $data = Atribusi::find($input['id']);
                        $data->updated_by = auth()->user()->id;
                    }
                    $data->periode_id = $request->periode;
                    $data->year = $dateYear;
                    $data->instance_id = $input['instance_id'];

                    $data->bel_peg_kode_rekening_id = $input['bel_peg_kode_rekening_id'];
                    $data->bel_peg_nama_rekening = $input['bel_peg_nama_rekening'];
                    $data->bel_peg_belanja_last_year = $input['bel_peg_belanja_last_year'];
                    $data->bel_peg_hutang_last_year = $input['bel_peg_hutang_last_year'];
                    $data->bel_peg_jumlah = $input['bel_peg_jumlah'];

                    $data->bel_barjas_kode_rekening_id = $input['bel_barjas_kode_rekening_id'];
                    $data->bel_barjas_nama_rekening_rincian_paket = $input['bel_barjas_nama_rekening_rincian_paket'];
                    $data->bel_barjas_belanja = $input['bel_barjas_belanja'];
                    $data->bel_barjas_hutang = $input['bel_barjas_hutang'];
                    $data->bel_barjas_jumlah = $input['bel_barjas_jumlah'];

                    $data->bel_modal_kode_rekening_id = $input['bel_modal_kode_rekening_id'];
                    $data->bel_modal_nama_rekening_rincian_paket = $input['bel_modal_nama_rekening_rincian_paket'];
                    $data->bel_modal_belanja = $input['bel_modal_belanja'];
                    $data->bel_modal_hutang = $input['bel_modal_hutang'];
                    $data->bel_modal_jumlah = $input['bel_modal_jumlah'];

                    $data->ket_no_kontrak_pegawai_barang_jasa = $input['ket_no_kontrak_pegawai_barang_jasa'];
                    $data->ket_no_sp2d_pegawai_barang_jasa = $input['ket_no_sp2d_pegawai_barang_jasa'];

                    $data->atri_aset_tetap_tanah = $input['atri_aset_tetap_tanah'];
                    $data->atri_aset_tetap_peralatan_mesin = $input['atri_aset_tetap_peralatan_mesin'];
                    $data->atri_aset_tetap_gedung_bangunan = $input['atri_aset_tetap_gedung_bangunan'];
                    $data->atri_aset_tetap_jalan_jaringan_irigasi = $input['atri_aset_tetap_jalan_jaringan_irigasi'];
                    $data->atri_aset_tetap_tetap_lainnya = $input['atri_aset_tetap_tetap_lainnya'];
                    $data->atri_konstruksi_dalam_pekerjaan = $input['atri_konstruksi_dalam_pekerjaan'];
                    $data->atri_aset_lain_lain = $input['atri_aset_lain_lain'];
                    $data->atri_ket_no_kontrak_sp2d = $input['atri_ket_no_kontrak_sp2d'];

                    $data->save();
                }
            }
            DB::commit();

            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteAtribusi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'nullable|exists:acc_padb_atribusi,id',
        ], [], [
            'id' => 'Data Id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = Atribusi::find($request->id);
            $data->deleted_by = auth()->user()->id;
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Atribusi End


    // Mutasi Aset Start
    function getMutasiAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_tambahan_mutasi_aset')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                // ->when($request->year, function ($query) use ($request) {
                //     return $query->where('tahun_perolehan', $request->year);
                // })
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('from_instance_id', $request->instance)
                        ->orWhere('to_instance_id', $request->instance);
                })
                ->orderBy('instance_id')
                ->get();
            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'from_instance_id' => $data->from_instance_id,
                    'to_instance_id' => $data->to_instance_id,
                    'kelompok_aset' => $data->kelompok_aset,
                    'nama_barang' => $data->nama_barang,
                    'tahun_perolehan' => $data->tahun_perolehan,
                    'nilai_perolehan' => $data->nilai_perolehan,
                    'akumulasi_penyusutan' => $data->akumulasi_penyusutan,
                    'bast_number' => $data->bast_number,
                    'bast_date' => $data->bast_date,

                    'plus_aset_tetap_tanah' => $data->plus_aset_tetap_tanah,
                    'plus_aset_tetap_perlatan_mesin' => $data->plus_aset_tetap_perlatan_mesin,
                    'plus_aset_tetap_gedung_bangunan' => $data->plus_aset_tetap_gedung_bangunan,
                    'plus_aset_tetap_jalan_jaringan_irigasi' => $data->plus_aset_tetap_jalan_jaringan_irigasi,
                    'plus_aset_tetap_lainnya' => $data->plus_aset_tetap_lainnya,
                    'plus_kdp' => $data->plus_kdp,
                    'plus_aset_lainnya' => $data->plus_aset_lainnya,

                    'min_aset_tetap_tanah' => $data->min_aset_tetap_tanah,
                    'min_aset_tetap_perlatan_mesin' => $data->min_aset_tetap_perlatan_mesin,
                    'min_aset_tetap_gedung_bangunan' => $data->min_aset_tetap_gedung_bangunan,
                    'min_aset_tetap_jalan_jaringan_irigasi' => $data->min_aset_tetap_jalan_jaringan_irigasi,
                    'min_aset_tetap_lainnya' => $data->min_aset_tetap_lainnya,
                    'min_kdp' => $data->min_kdp,
                    'min_aset_lainnya' => $data->min_aset_lainnya,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeMutasiAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $now = date('Y-m-d H:i:s');

            $inputs = $request->data;
            foreach ($inputs as $key => $input) {
                if (!$input['id']) {
                    DB::table('acc_padb_tambahan_mutasi_aset')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'from_instance_id' => $input['from_instance_id'],
                            'to_instance_id' => $input['to_instance_id'],
                            'kelompok_aset' => $input['kelompok_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'nilai_perolehan' => $input['nilai_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'bast_number' => $input['bast_number'],
                            'bast_date' => $input['bast_date'],

                            'plus_aset_tetap_tanah' => $input['plus_aset_tetap_tanah'],
                            'plus_aset_tetap_perlatan_mesin' => $input['plus_aset_tetap_perlatan_mesin'],
                            'plus_aset_tetap_gedung_bangunan' => $input['plus_aset_tetap_gedung_bangunan'],
                            'plus_aset_tetap_jalan_jaringan_irigasi' => $input['plus_aset_tetap_jalan_jaringan_irigasi'],
                            'plus_aset_tetap_lainnya' => $input['plus_aset_tetap_lainnya'],
                            'plus_kdp' => $input['plus_kdp'],
                            'plus_aset_lainnya' => $input['plus_aset_lainnya'],

                            'min_aset_tetap_tanah' => $input['min_aset_tetap_tanah'],
                            'min_aset_tetap_perlatan_mesin' => $input['min_aset_tetap_perlatan_mesin'],
                            'min_aset_tetap_gedung_bangunan' => $input['min_aset_tetap_gedung_bangunan'],
                            'min_aset_tetap_jalan_jaringan_irigasi' => $input['min_aset_tetap_jalan_jaringan_irigasi'],
                            'min_aset_tetap_lainnya' => $input['min_aset_tetap_lainnya'],
                            'min_kdp' => $input['min_kdp'],
                            'min_aset_lainnya' => $input['min_aset_lainnya'],

                            'created_by' => $auth->id,
                            'created_at' => $now
                        ]);
                } else if ($input['id']) {
                    DB::table('acc_padb_tambahan_mutasi_aset')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'from_instance_id' => $input['from_instance_id'],
                            'to_instance_id' => $input['to_instance_id'],
                            'kelompok_aset' => $input['kelompok_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'nilai_perolehan' => $input['nilai_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'bast_number' => $input['bast_number'],
                            'bast_date' => $input['bast_date'],

                            'plus_aset_tetap_tanah' => $input['plus_aset_tetap_tanah'],
                            'plus_aset_tetap_perlatan_mesin' => $input['plus_aset_tetap_perlatan_mesin'],
                            'plus_aset_tetap_gedung_bangunan' => $input['plus_aset_tetap_gedung_bangunan'],
                            'plus_aset_tetap_jalan_jaringan_irigasi' => $input['plus_aset_tetap_jalan_jaringan_irigasi'],
                            'plus_aset_tetap_lainnya' => $input['plus_aset_tetap_lainnya'],
                            'plus_kdp' => $input['plus_kdp'],
                            'plus_aset_lainnya' => $input['plus_aset_lainnya'],

                            'min_aset_tetap_tanah' => $input['min_aset_tetap_tanah'],
                            'min_aset_tetap_perlatan_mesin' => $input['min_aset_tetap_perlatan_mesin'],
                            'min_aset_tetap_gedung_bangunan' => $input['min_aset_tetap_gedung_bangunan'],
                            'min_aset_tetap_jalan_jaringan_irigasi' => $input['min_aset_tetap_jalan_jaringan_irigasi'],
                            'min_aset_tetap_lainnya' => $input['min_aset_tetap_lainnya'],
                            'min_kdp' => $input['min_kdp'],
                            'min_aset_lainnya' => $input['min_aset_lainnya'],

                            'updated_by' => $auth->id,
                            'updated_at' => $now
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

    function deleteMutasiAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_tambahan_mutasi_aset,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_tambahan_mutasi_aset')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // Mutasi Aset End

    // DaftarPekerjaan Start
    function getDaftarPekerjaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_tambahan_daftar_pekerjaan')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                ->orderBy('instance_id')
                ->get();

            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'kode_rekening_name' => $data->kode_rekening_name,
                    'nama_kegiatan_paket' => $data->nama_kegiatan_paket,
                    'pelaksana_pekerjaan' => $data->pelaksana_pekerjaan,
                    'no_kontrak' => $data->no_kontrak,
                    'periode_kontrak' => $data->periode_kontrak,
                    'nilai_belanja_kontrak' => $data->nilai_belanja_kontrak,
                    'payment_1_sp2d' => $data->payment_1_sp2d,
                    'payment_1_tanggal' => $data->payment_1_tanggal,
                    'payment_1_jumlah' => $data->payment_1_jumlah,
                    'payment_2_sp2d' => $data->payment_2_sp2d,
                    'payment_2_tanggal' => $data->payment_2_tanggal,
                    'payment_2_jumlah' => $data->payment_2_jumlah,
                    'payment_3_sp2d' => $data->payment_3_sp2d,
                    'payment_3_tanggal' => $data->payment_3_tanggal,
                    'payment_3_jumlah' => $data->payment_3_jumlah,
                    'payment_4_sp2d' => $data->payment_4_sp2d,
                    'payment_4_tanggal' => $data->payment_4_tanggal,
                    'payment_4_jumlah' => $data->payment_4_jumlah,
                    'jumlah_pembayaran_sd_desember' => $data->jumlah_pembayaran_sd_desember,
                    'kewajiban_tidak_terbayar_sd_desember' => $data->kewajiban_tidak_terbayar_sd_desember,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,
                    'tanggal_surat_pengakuan_hutang' => $data->tanggal_surat_pengakuan_hutang,
                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeDaftarPekerjaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Instance ID'
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
                    DB::table('acc_padb_tambahan_daftar_pekerjaan')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'kode_rekening_name' => $input['kode_rekening_name'],
                            'nama_kegiatan_paket' => $input['nama_kegiatan_paket'],
                            'pelaksana_pekerjaan' => $input['pelaksana_pekerjaan'],
                            'no_kontrak' => $input['no_kontrak'],
                            'periode_kontrak' => $input['periode_kontrak'],
                            'nilai_belanja_kontrak' => $input['nilai_belanja_kontrak'],
                            'payment_1_sp2d' => $input['payment_1_sp2d'],
                            'payment_1_tanggal' => $input['payment_1_tanggal'],
                            'payment_1_jumlah' => $input['payment_1_jumlah'],
                            'payment_2_sp2d' => $input['payment_2_sp2d'],
                            'payment_2_tanggal' => $input['payment_2_tanggal'],
                            'payment_2_jumlah' => $input['payment_2_jumlah'],
                            'payment_3_sp2d' => $input['payment_3_sp2d'],
                            'payment_3_tanggal' => $input['payment_3_tanggal'],
                            'payment_3_jumlah' => $input['payment_3_jumlah'],
                            'payment_4_sp2d' => $input['payment_4_sp2d'],
                            'payment_4_tanggal' => $input['payment_4_tanggal'],
                            'payment_4_jumlah' => $input['payment_4_jumlah'],
                            'jumlah_pembayaran_sd_desember' => $input['jumlah_pembayaran_sd_desember'],
                            'kewajiban_tidak_terbayar_sd_desember' => $input['kewajiban_tidak_terbayar_sd_desember'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'tanggal_surat_pengakuan_hutang' => $input['tanggal_surat_pengakuan_hutang'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_tambahan_daftar_pekerjaan')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'kode_rekening_name' => $input['kode_rekening_name'],
                            'nama_kegiatan_paket' => $input['nama_kegiatan_paket'],
                            'pelaksana_pekerjaan' => $input['pelaksana_pekerjaan'],
                            'no_kontrak' => $input['no_kontrak'],
                            'periode_kontrak' => $input['periode_kontrak'],
                            'nilai_belanja_kontrak' => $input['nilai_belanja_kontrak'],
                            'payment_1_sp2d' => $input['payment_1_sp2d'],
                            'payment_1_tanggal' => $input['payment_1_tanggal'],
                            'payment_1_jumlah' => $input['payment_1_jumlah'],
                            'payment_2_sp2d' => $input['payment_2_sp2d'],
                            'payment_2_tanggal' => $input['payment_2_tanggal'],
                            'payment_2_jumlah' => $input['payment_2_jumlah'],
                            'payment_3_sp2d' => $input['payment_3_sp2d'],
                            'payment_3_tanggal' => $input['payment_3_tanggal'],
                            'payment_3_jumlah' => $input['payment_3_jumlah'],
                            'payment_4_sp2d' => $input['payment_4_sp2d'],
                            'payment_4_tanggal' => $input['payment_4_tanggal'],
                            'payment_4_jumlah' => $input['payment_4_jumlah'],
                            'jumlah_pembayaran_sd_desember' => $input['jumlah_pembayaran_sd_desember'],
                            'kewajiban_tidak_terbayar_sd_desember' => $input['kewajiban_tidak_terbayar_sd_desember'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'tanggal_surat_pengakuan_hutang' => $input['tanggal_surat_pengakuan_hutang'],
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

    function deleteDaftarPekerjaan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_tambahan_daftar_pekerjaan,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_tambahan_daftar_pekerjaan')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // DaftarPekerjaan End

    // HibahMasuk Start
    function getHibahMasuk(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_tambahan_hibah_masuk')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                ->orderBy('instance_id')
                ->get();

            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,
                    'penerima_hibah' => $data->penerima_hibah,
                    'pemberi_hibah' => $data->pemberi_hibah,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'nama_barang' => $data->nama_barang,
                    'nilai' => $data->nilai,
                    'nomor_berita_acara' => $data->nomor_berita_acara,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,
                    'persediaan' => $data->persediaan,
                    'aset_tetap_tanah' => $data->aset_tetap_tanah,
                    'aset_tetap_peralatan_mesin' => $data->aset_tetap_peralatan_mesin,
                    'aset_tetap_gedung_bangunan' => $data->aset_tetap_gedung_bangunan,
                    'aset_tetap_jalan_jaringan_irigasi' => $data->aset_tetap_jalan_jaringan_irigasi,
                    'aset_tetap_lainnya' => $data->aset_tetap_lainnya,
                    'konstruksi_dalam_pekerjaan' => $data->konstruksi_dalam_pekerjaan,
                    'aset_lainnya' => $data->aset_lainnya,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeHibahMasuk(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.penerima_hibah' => 'required|string',
            'data.*.pemberi_hibah' => 'required|string',
            'data.*.nama_barang' => 'required|string',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.penerima_hibah' => 'Penerima Hibah',
            'data.*.pemberi_hibah' => 'Pemberi Hibah',
            'data.*.nama_barang' => 'Nama Barang',
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
                    DB::table('acc_padb_tambahan_hibah_masuk')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'penerima_hibah' => $input['penerima_hibah'],
                            'pemberi_hibah' => $input['pemberi_hibah'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'nama_barang' => $input['nama_barang'],
                            'nilai' => $input['nilai'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_tambahan_hibah_masuk')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'penerima_hibah' => $input['penerima_hibah'],
                            'pemberi_hibah' => $input['pemberi_hibah'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'nama_barang' => $input['nama_barang'],
                            'nilai' => $input['nilai'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
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

    function deleteHibahMasuk(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_tambahan_hibah_masuk,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_tambahan_hibah_masuk')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // HibahMasuk End

    // HibahKeluar Start
    function getHibahKeluar(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_tambahan_hibah_keluar')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                ->orderBy('instance_id')
                ->get();

            foreach ($arrDatas as $data) {
                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,
                    'penerima_hibah' => $data->penerima_hibah,
                    'pemberi_hibah' => $data->pemberi_hibah,
                    'kode_rekening_id' => $data->kode_rekening_id,
                    'nama_barang' => $data->nama_barang,
                    'nilai' => $data->nilai,
                    'nomor_berita_acara' => $data->nomor_berita_acara,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,
                    'persediaan' => $data->persediaan,
                    'aset_tetap_tanah' => $data->aset_tetap_tanah,
                    'aset_tetap_peralatan_mesin' => $data->aset_tetap_peralatan_mesin,
                    'aset_tetap_gedung_bangunan' => $data->aset_tetap_gedung_bangunan,
                    'aset_tetap_jalan_jaringan_irigasi' => $data->aset_tetap_jalan_jaringan_irigasi,
                    'aset_tetap_lainnya' => $data->aset_tetap_lainnya,
                    'konstruksi_dalam_pekerjaan' => $data->konstruksi_dalam_pekerjaan,
                    'aset_lainnya' => $data->aset_lainnya,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storeHibahKeluar(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.penerima_hibah' => 'required|string',
            'data.*.pemberi_hibah' => 'required|string',
            'data.*.nama_barang' => 'required|string',
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.penerima_hibah' => 'Penerima Hibah',
            'data.*.pemberi_hibah' => 'Pemberi Hibah',
            'data.*.nama_barang' => 'Nama Barang',
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
                    DB::table('acc_padb_tambahan_hibah_keluar')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'penerima_hibah' => $input['penerima_hibah'],
                            'pemberi_hibah' => $input['pemberi_hibah'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'nama_barang' => $input['nama_barang'],
                            'nilai' => $input['nilai'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_tambahan_hibah_keluar')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'penerima_hibah' => $input['penerima_hibah'],
                            'pemberi_hibah' => $input['pemberi_hibah'],
                            'kode_rekening_id' => $input['kode_rekening_id'],
                            'nama_barang' => $input['nama_barang'],
                            'nilai' => $input['nilai'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
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

    function deleteHibahKeluar(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_tambahan_hibah_keluar,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_tambahan_hibah_keluar')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // HibahKeluar End

    // PenilaianAset Start
    function getPenilaianAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_penilaian_aset')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->latest('created_at')
                ->get();

            foreach ($arrDatas as $data) {
                $jumlahPenyesuaian = 0;
                $jumlahPenyesuaian = $data->persediaan + $data->aset_tetap_tanah + $data->aset_tetap_peralatan_mesin + $data->aset_tetap_gedung_bangunan + $data->aset_tetap_jalan_jaringan_irigasi + $data->aset_tetap_lainnya + $data->konstruksi_dalam_pekerjaan + $data->aset_lainnya;

                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'kelompok_barang_aset' => $data->kelompok_barang_aset,
                    'nama_barang' => $data->nama_barang,
                    'tahun_perolehan' => $data->tahun_perolehan,
                    'metode_perolehan' => $data->metode_perolehan,
                    'nilai_awal_aset' => $data->nilai_awal_aset,
                    'hasil_penilaian' => $data->hasil_penilaian,
                    'nomor_berita_acara' => $data->nomor_berita_acara,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,

                    'persediaan' => $data->persediaan,
                    'aset_tetap_tanah' => $data->aset_tetap_tanah,
                    'aset_tetap_peralatan_mesin' => $data->aset_tetap_peralatan_mesin,
                    'aset_tetap_gedung_bangunan' => $data->aset_tetap_gedung_bangunan,
                    'aset_tetap_jalan_jaringan_irigasi' => $data->aset_tetap_jalan_jaringan_irigasi,
                    'aset_tetap_lainnya' => $data->aset_tetap_lainnya,
                    'konstruksi_dalam_pekerjaan' => $data->konstruksi_dalam_pekerjaan,
                    'aset_lainnya' => $data->aset_lainnya,
                    'jumlah_penyesuaian' => $jumlahPenyesuaian,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storePenilaianAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.tahun_perolehan' => 'nullable|integer|min:2000|max:' . date('Y'),
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.tahun_perolehan' => 'Tahun Perolehan'
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
                    DB::table('acc_padb_penilaian_aset')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'metode_perolehan' => $input['metode_perolehan'],
                            'nilai_awal_aset' => $input['nilai_awal_aset'],
                            'hasil_penilaian' => $input['hasil_penilaian'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_penilaian_aset')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'metode_perolehan' => $input['metode_perolehan'],
                            'nilai_awal_aset' => $input['nilai_awal_aset'],
                            'hasil_penilaian' => $input['hasil_penilaian'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
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

    function deletePenilaianAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_penilaian_aset,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_penilaian_aset')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // PenilaianAset End

    // PenghapusanAset Start
    function getPenghapusanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_penghapusan_aset')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->latest('created_at')
                ->get();

            foreach ($arrDatas as $data) {
                $jumlahPenyesuaian = 0;
                $jumlahPenyesuaian = $data->persediaan + $data->aset_tetap_tanah + $data->aset_tetap_peralatan_mesin + $data->aset_tetap_gedung_bangunan + $data->aset_tetap_jalan_jaringan_irigasi + $data->aset_tetap_lainnya + $data->konstruksi_dalam_pekerjaan + $data->aset_lainnya;

                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'kelompok_barang_aset' => $data->kelompok_barang_aset,
                    'nama_barang' => $data->nama_barang,
                    'tahun_perolehan' => $data->tahun_perolehan,
                    'nilai_perolehan' => $data->nilai_perolehan,
                    'akumulasi_penyusutan' => $data->akumulasi_penyusutan,
                    'nomor_berita_acara' => $data->nomor_berita_acara,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,

                    'persediaan' => $data->persediaan,
                    'aset_tetap_tanah' => $data->aset_tetap_tanah,
                    'aset_tetap_peralatan_mesin' => $data->aset_tetap_peralatan_mesin,
                    'aset_tetap_gedung_bangunan' => $data->aset_tetap_gedung_bangunan,
                    'aset_tetap_jalan_jaringan_irigasi' => $data->aset_tetap_jalan_jaringan_irigasi,
                    'aset_tetap_lainnya' => $data->aset_tetap_lainnya,
                    'konstruksi_dalam_pekerjaan' => $data->konstruksi_dalam_pekerjaan,
                    'aset_lainnya' => $data->aset_lainnya,
                    'jumlah_penyesuaian' => $jumlahPenyesuaian,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storePenghapusanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.tahun_perolehan' => 'nullable|integer|min:2000|max:' . date('Y'),
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.tahun_perolehan' => 'Tahun Perolehan'
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
                    DB::table('acc_padb_penghapusan_aset')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'nilai_perolehan' => $input['nilai_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_penghapusan_aset')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'nilai_perolehan' => $input['nilai_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
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

    function deletePenghapusanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_penghapusan_aset,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_penghapusan_aset')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // PenghapusanAset End

    // PenjualanAset Start
    function getPenjualanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
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
            $arrDatas = DB::table('acc_padb_penjualan_aset')
                ->where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                // ->orderBy('instance_id')
                ->latest('created_at')
                ->get();

            foreach ($arrDatas as $data) {
                $jumlahPenyesuaian = 0;
                $jumlahPenyesuaian = $data->persediaan + $data->aset_tetap_tanah + $data->aset_tetap_peralatan_mesin + $data->aset_tetap_gedung_bangunan + $data->aset_tetap_jalan_jaringan_irigasi + $data->aset_tetap_lainnya + $data->konstruksi_dalam_pekerjaan + $data->aset_lainnya;

                $datas[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'instance_id' => $data->instance_id,

                    'kelompok_barang_aset' => $data->kelompok_barang_aset,
                    'nama_barang' => $data->nama_barang,
                    'tahun_perolehan' => $data->tahun_perolehan,
                    'harga_perolehan' => $data->harga_perolehan,
                    'akumulasi_penyusutan' => $data->akumulasi_penyusutan,
                    'harga_jual' => $data->harga_jual,
                    'surplus' => $data->surplus,
                    'nomor_berita_acara' => $data->nomor_berita_acara,
                    'tanggal_berita_acara' => $data->tanggal_berita_acara,

                    'persediaan' => $data->persediaan,
                    'aset_tetap_tanah' => $data->aset_tetap_tanah,
                    'aset_tetap_peralatan_mesin' => $data->aset_tetap_peralatan_mesin,
                    'aset_tetap_gedung_bangunan' => $data->aset_tetap_gedung_bangunan,
                    'aset_tetap_jalan_jaringan_irigasi' => $data->aset_tetap_jalan_jaringan_irigasi,
                    'aset_tetap_lainnya' => $data->aset_tetap_lainnya,
                    'konstruksi_dalam_pekerjaan' => $data->konstruksi_dalam_pekerjaan,
                    'aset_lainnya' => $data->aset_lainnya,
                    'jumlah_penyesuaian' => $jumlahPenyesuaian,

                    'created_by' => User::find($data->created_by)->fullname ?? '-',
                    'created_at' => $data->created_at,
                    'updated_by' => User::find($data->updated_by)->fullname ?? '-',
                    'updated_at' => $data->updated_at,
                ];
            }

            return $this->successResponse($datas, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function storePenjualanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'required|integer',
            'periode' => 'required|exists:ref_periode,id',
            'data.*.instance_id' => 'required|exists:instances,id',
            'data.*.tahun_perolehan' => 'nullable|integer|min:2000|max:' . date('Y'),
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode',
            'data.*.instance_id' => 'Perangkat Daerah',
            'data.*.tahun_perolehan' => 'Tahun Perolehan'
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
                    DB::table('acc_padb_penjualan_aset')
                        ->insert([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'harga_perolehan' => $input['harga_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'harga_jual' => $input['harga_jual'],
                            'surplus' => $input['surplus'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
                            'created_by' => $auth->id,
                            'created_at' => $now,
                        ]);
                } elseif ($input['id']) {
                    DB::table('acc_padb_penjualan_aset')
                        ->where('id', $input['id'])
                        ->update([
                            'periode_id' => $request->periode,
                            'year' => $request->year,
                            'instance_id' => $input['instance_id'],
                            'kelompok_barang_aset' => $input['kelompok_barang_aset'],
                            'nama_barang' => $input['nama_barang'],
                            'tahun_perolehan' => $input['tahun_perolehan'],
                            'harga_perolehan' => $input['harga_perolehan'],
                            'akumulasi_penyusutan' => $input['akumulasi_penyusutan'],
                            'harga_jual' => $input['harga_jual'],
                            'surplus' => $input['surplus'],
                            'nomor_berita_acara' => $input['nomor_berita_acara'],
                            'tanggal_berita_acara' => $input['tanggal_berita_acara'],
                            'persediaan' => $input['persediaan'],
                            'aset_tetap_tanah' => $input['aset_tetap_tanah'],
                            'aset_tetap_peralatan_mesin' => $input['aset_tetap_peralatan_mesin'],
                            'aset_tetap_gedung_bangunan' => $input['aset_tetap_gedung_bangunan'],
                            'aset_tetap_jalan_jaringan_irigasi' => $input['aset_tetap_jalan_jaringan_irigasi'],
                            'aset_tetap_lainnya' => $input['aset_tetap_lainnya'],
                            'konstruksi_dalam_pekerjaan' => $input['konstruksi_dalam_pekerjaan'],
                            'aset_lainnya' => $input['aset_lainnya'],
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

    function deletePenjualanAset(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|exists:acc_padb_penjualan_aset,id',
        ], [], [
            'id' => 'Data ID'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('acc_padb_penjualan_aset')
                ->where('id', $request->id)
                ->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
    // PenjualanAset End
}
