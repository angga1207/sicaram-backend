<?php

namespace App\Http\Controllers\API;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Riskihajar\Terbilang\Facades\Terbilang;

class LPSEController extends Controller
{
    use JsonReturner;

    function PaketPenyediaTerumumkan($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/2f8038be-f700-43be-a66e-a62ad1b35636/json/1916/RUP-PaketPenyedia-Terumumkan/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }

    function PaketPenyediaAnggaran($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/32c0b54e-b782-4c96-a4e2-9f29d8240f14/json/1905/RUP-PaketAnggaranPenyedia/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }

    function PaketPenyediaLokasi($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/7a4eb256-3bce-4c1f-a6d3-5cbe6164cd2d/json/1907/RUP-PaketPenyediaLokasi/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }

    function PaketSwakelolaTerumumkan($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/7d4249c8-85f0-4e17-9bde-91aa5ccc9021/json/1915/RUP-PaketSwakelola-Terumumkan/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }

    function PaketSwakelolaAnggaran($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/2f1ab1a2-4516-410a-8f0c-109152e619da/json/1913/RUP-PaketAnggaranSwakelola/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }

    function PaketSwakelolaLokasi($year)
    {
        $year = $year ?? date('Y');

        $uri = "https://isb.lkpp.go.id/isb-2/api/5a1ad09f-10a9-4274-b664-d63a8eb6966e/json/1912/RUP-PaketSwakelolaLokasi/tipe/4:12/parameter/" . $year . ":D496";

        $data = Http::get($uri);
        if ($data->status() != 200) {
            return [
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ];
        }
        $data = collect(json_decode($data, true));
        return $data ?? [];
    }


    function index(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->jsonError($validate->errors()->first());
        }

        $year = $request->year;

        $return = [];
        $PenyediaTerumumkan = collect($this->PaketPenyediaTerumumkan($year));
        $SwakelolaTerumumkan = collect($this->PaketSwakelolaTerumumkan($year));

        $countPaketPenyedia = $PenyediaTerumumkan->where('status_umumkan_rup', 'Terumumkan')->count();
        $countPaketSwakelola = $SwakelolaTerumumkan->where('status_umumkan_rup', 'Terumumkan')->count();

        $countPaketPenyediaBelum = $PenyediaTerumumkan->where('status_umumkan_rup', '!=', 'Terumumkan')->count();
        $countPaketSwakelolaBelum = $SwakelolaTerumumkan->where('status_umumkan_rup', '!=', 'Terumumkan')->count();

        $countPaketPenyediaDikerjakan = $PenyediaTerumumkan->where('tgl_akhir_kontrak', '>=', now())->count();
        $countPaketSwakelolaDikerjakan = $SwakelolaTerumumkan->where('tgl_akhir_kontrak', '>=', now())->count();

        $countPaketPenyediaTerlambat = $PenyediaTerumumkan->where('tgl_akhir_kontrak', '<', now())->count();
        $countPaketSwakelolaTerlambat = $SwakelolaTerumumkan->where('tgl_akhir_kontrak', '<', now())->count();

        $countPaketPenyediaBelumDikerjakan = $PenyediaTerumumkan->where('tgl_awal_kontrak', '>', null)->count();
        $countPaketSwakelolaBelumDikerjakan = $SwakelolaTerumumkan->where('tgl_awal_kontrak', '>', null)->count();

        $return['terumumkan'] = $countPaketPenyedia + $countPaketSwakelola;
        $return['terumumkan_terbilang'] = Terbilang::make($return['terumumkan'], ' paket');

        $return['belum_diumumkan'] = $countPaketPenyediaBelum + $countPaketSwakelolaBelum;
        $return['belum_diumumkan_terbilang'] = Terbilang::make($return['belum_diumumkan'], ' paket');

        $return['dikerjakan'] = $countPaketPenyediaDikerjakan + $countPaketSwakelolaDikerjakan;
        $return['dikerjakan_terbilang'] = Terbilang::make($return['dikerjakan'], ' paket');

        $return['terlambat'] = $countPaketPenyediaTerlambat + $countPaketSwakelolaTerlambat;
        $return['terlambat_terbilang'] = Terbilang::make($return['terlambat'], ' paket');

        $return['belum_dikerjakan'] = $countPaketPenyediaBelumDikerjakan + $countPaketSwakelolaBelumDikerjakan;
        $return['belum_dikerjakan_terbilang'] = Terbilang::make($return['belum_dikerjakan'], ' paket');

        return $this->successResponse($return);
    }

    function getPenyediaTerumumkan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {
            $year = $request->year;

            $data = $this->PaketPenyediaTerumumkan($year);
            $data = collect($data);

            $data = $data->groupBy('nama_satker');
            $data = $data->sortKeys();
            $return = [];

            foreach ($data as $key => $value) {
                $return[] = [
                    'kd_satker_str' => $value[0]['kd_satker_str'],
                    'nama_satker' => $key,
                    'paket' => $value->count(),
                    'pagu' => $value->sum('pagu'),
                ];
            }

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function getPenyediaTerumumkanPD($kd_satker, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {

            $year = $request->year;

            $data = $this->PaketPenyediaTerumumkan($year);
            $data = collect($data);

            $data = $data->where('kd_satker_str', $kd_satker);
            $data = $data->values();
            $return = [];

            foreach ($data as $value) {
                // $return['data'][] = $value;
                $return['data'][] = [
                    'kd_rup' => $value['kd_rup'],
                    'nama_paket' => $value['nama_paket'],
                    'pagu' => $value['pagu'],
                    'metode_pengadaan' => $value['metode_pengadaan'],
                    'tgl_buat_paket' => $value['tgl_buat_paket'],
                ];
            }
            if (count($data) > 0) {
                // $return['perangkat_daerah'] = $data[0];
                $return['perangkat_daerah'] = [
                    'nama_satker' => $data[0]['nama_satker'],
                    'kd_satker_str' => $data[0]['kd_satker_str'],
                ];
            }

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function getPenyediaTerumumkanPDDetail($kd_satker, $kd_rup, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {
            $penyedia = $this->PaketPenyediaTerumumkan($request->year);
            $penyedia = collect($penyedia);
            $penyedia = $penyedia->where('kd_rup', $kd_rup)->first();

            $lokasi = $this->PaketPenyediaLokasi($request->year);
            $lokasi = collect($lokasi);
            $lokasi = $lokasi->where('kd_rup', $kd_rup);
            $lokasi = $lokasi->values();

            $anggaran = $this->PaketPenyediaAnggaran($request->year);
            $anggaran = collect($anggaran);
            $anggaran = $anggaran->where('kd_rup', $kd_rup);
            $anggaran = $anggaran->values();

            $return = [];

            $return['penyedia'] = $penyedia;
            $return['lokasi'] = $lokasi;
            $return['anggaran'] = $anggaran;

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function getSwakelolaTerumumkan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {
            $year = $request->year;

            $data = $this->PaketSwakelolaTerumumkan($year);
            $data = collect($data);

            $data = $data->groupBy('nama_satker');
            $data = $data->sortKeys();
            $return = [];

            foreach ($data as $key => $value) {
                $return[] = [
                    'kd_satker_str' => $value[0]['kd_satker_str'],
                    'nama_satker' => $key,
                    'paket' => $value->count(),
                    'pagu' => $value->sum('pagu'),
                ];
            }

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function getSwakelolaTerumumkanPD($kd_satker, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {

            $year = $request->year;

            $data = $this->PaketSwakelolaTerumumkan($year);
            $data = collect($data);

            $data = $data->where('kd_satker_str', $kd_satker);
            $data = $data->values();
            $return = [];

            foreach ($data as $value) {
                // $return['data'][] = $value;
                $return['data'][] = [
                    'kd_rup' => $value['kd_rup'],
                    'nama_paket' => $value['nama_paket'],
                    'pagu' => $value['pagu'],
                    'tipe_swakelola' => $value['tipe_swakelola'],
                    'tgl_buat_paket' => $value['tgl_buat_paket'],
                ];
            }
            if (count($data) > 0) {
                // $return['perangkat_daerah'] = $data[0];
                $return['perangkat_daerah'] = [
                    'nama_satker' => $data[0]['nama_satker'],
                    'kd_satker_str' => $data[0]['kd_satker_str'],
                ];
            }

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function getSwakelolaTerumumkanPDDetail($kd_satker, $kd_rup, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'year' => 'required|numeric'
        ], [], [
            'year' => 'Tahun'
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors()->first());
        }

        try {
            $penyedia = $this->PaketSwakelolaTerumumkan($request->year);
            $penyedia = collect($penyedia);
            $penyedia = $penyedia->where('kd_rup', $kd_rup)->first();

            $lokasi = $this->PaketSwakelolaLokasi($request->year);
            $lokasi = collect($lokasi);
            $lokasi = $lokasi->where('kd_rup', $kd_rup);
            $lokasi = $lokasi->values();

            $anggaran = $this->PaketSwakelolaAnggaran($request->year);
            $anggaran = collect($anggaran);
            $anggaran = $anggaran->where('kd_rup', $kd_rup);
            $anggaran = $anggaran->values();

            $return = [];

            $return['penyedia'] = $penyedia;
            $return['lokasi'] = $lokasi;
            $return['anggaran'] = $anggaran;

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }
}
