<?php

namespace App\Http\Controllers\API\Local;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Instance;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Data\TargetKinerja;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Caram\ApbdSubKegiatan;
use App\Models\Data\RealisasiRincian;
use App\Models\Data\TaggingSumberDana;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\RealisasiSubKegiatan;
use App\Models\Data\TargetKinerjaRincian;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetKinerjaKeterangan;
use App\Models\Data\RealisasiSubKegiatanFiles;
use App\Models\Data\RealisasiSubKegiatanKontrak;
use App\Models\Data\RealisasiSubKegiatanKeterangan;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use JsonReturner;

    function chartRealisasi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
            'instances' => 'nullable',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->view == 1) {
            $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        }

        if ($request->view == 2) {
            $rangeMonths = [1, 2, 3];
        }

        if ($request->view == 3) {
            $rangeMonths = [4, 5, 6];
        }

        if ($request->view == 4) {
            $rangeMonths = [7, 8, 9];
        }

        if ($request->view == 5) {
            $rangeMonths = [10, 11, 12];
        }

        if ($request->view > 5 || $request->view < 1) {
            return $this->errorResponse('View tidak valid');
        }

        $dataTarget = [];
        $dataRealisasi = [];

        $cachedDatadataTarget = Cache::get('DashOI-ChartRealisasi-dataTarget' . $request->year . '-' . $request->periode . '-' . $request->view);
        $cachedDatadataRealisasi = Cache::get('DashOI-ChartRealisasi-dataRealisasi' . $request->year . '-' . $request->periode . '-' . $request->view);
        if ($cachedDatadataTarget && $cachedDatadataRealisasi) {
            $dataTarget = $cachedDatadataTarget;
            $dataRealisasi = $cachedDatadataRealisasi;
        } else {
            $arrInstances = DB::table('instances')
                ->when($request->instances, function ($q) use ($request) {
                    return $q->whereIn('id', is_array($request->instances) ? $request->instances : collect($request->instances));
                })
                ->where('status', 'active')->get();

            foreach ($rangeMonths as $month) {
                // $sumTarget = TargetKinerja::where('periode_id', $request->periode)
                //     ->where('year', $request->year)
                //     ->where('month', $month)
                //     // ->where('status', 'verified')
                //     ->where('is_detail', true)
                //     ->sum('pagu_sipd');

                // MODEL LAMA
                // $sumTarget = ApbdSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->whereIn('instance_id', $arrInstances->pluck('id')->toArray())
                //     ->get()
                //     ->sum('total_anggaran');

                // MODEL BARU
                // $sumTarget = DB::table('data_target_kinerja')
                //     ->whereIn('instance_id', $arrInstances->pluck('id')->toArray())
                //     ->where('year', $request->year)
                //     ->where('month', $month)
                //     // ->groupBy('program_id')
                //     // ->groupBy('kode_rekening_id')
                //     ->sum('pagu_sipd');

                $sumTarget = DB::table('instance_summary')
                    ->whereIn('instance_id', $arrInstances->pluck('id')->toArray())
                    ->where('periode_id', $request->periode)
                    ->where('year', $request->year)
                    ->where('month', $month)
                    ->sum('pagu_anggaran');

                $dataTarget[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => $sumTarget ?? 0,
                ];

                // $sumRealisasi = Realisasi::where('periode_id', $request->periode)
                //     ->where('year', $request->year)
                //     ->where('month', $month)
                //     // ->where('status', 'verified')
                //     ->sum('anggaran');

                // $sumRealisasi = RealisasiSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->where('status', 'verified')
                //     ->sum('realisasi_anggaran');

                $sumRealisasi = DB::table('instance_summary')
                    ->whereIn('instance_id', $arrInstances->pluck('id')->toArray())
                    ->where('periode_id', $request->periode)
                    ->where('year', $request->year)
                    ->where('month', $month)
                    ->sum('realisasi_anggaran');

                $dataRealisasi[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasi ?? 0,
                ];
            }
        }

        return $this->successResponse([
            'target' => $dataTarget,
            'realisasi' => $dataRealisasi,
        ], 'Data berhasil diambil');
    }

    function chartKinerja(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
            'instances' => 'nullable',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {

            $dataTarget = [];
            $dataRealisasi = [];

            if ($request->view == 1) {
                $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }

            if ($request->view == 2) {
                $rangeMonths = [1, 2, 3];
            }

            if ($request->view == 3) {
                $rangeMonths = [4, 5, 6];
            }

            if ($request->view == 4) {
                $rangeMonths = [7, 8, 9];
            }

            if ($request->view == 5) {
                $rangeMonths = [10, 11, 12];
            }

            if ($request->view > 5 || $request->view < 1) {
                return $this->errorResponse('View tidak valid');
            }

            $cachedDatadataTarget = Cache::get('DashOI-ChartKinerja-dataTarget' . $request->year . '-' . $request->periode . '-' . $request->view);
            $cachedDatadataRealisasi = Cache::get('DashOI-ChartKinerja-dataRealisasi' . $request->year . '-' . $request->periode . '-' . $request->view);
            if ($cachedDatadataTarget && $cachedDatadataRealisasi) {
                $dataTarget = $cachedDatadataTarget;
                $dataRealisasi = $cachedDatadataRealisasi;
            } else {
                foreach ($rangeMonths as $month) {
                    $dataTarget[] = [
                        'month' => $month,
                        'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                        'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                        'target' => 100,
                    ];

                    $sumRealisasi = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $month)
                        ->when($request->instances, function ($q) use ($request) {
                            return $q->whereIn('instance_id', is_array($request->instances) ? $request->instances : collect($request->instances));
                        })
                        ->where('status', 'verified')
                        ->sum('persentase_realisasi_kinerja');

                    $subKegiatanCount = SubKegiatan::where('status', 'active')
                        ->when($request->instances, function ($q) use ($request) {
                            return $q->whereIn('instance_id', is_array($request->instances) ? $request->instances : collect($request->instances));
                        })
                        ->count();

                    $dataRealisasi[] = [
                        'month' => $month,
                        'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                        'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                        'realisasi' => $sumRealisasi ? $sumRealisasi / $subKegiatanCount : 0,
                    ];
                }
            }

            return $this->successResponse([
                'target' => $dataTarget,
                'realisasi' => $dataRealisasi,
            ], 'Data berhasil diambil');
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }


    function rankInstance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
            'instances' => 'nullable',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $sortBy = $request->sortBy ?? 'keuangan';
            $cachedData = Cache::get('DashOI-RankInstance-' . $request->year . '-' . $request->periode);
            if ($cachedData) {
                $data = $cachedData;
            } else {
                $data = [];
                $arrInstances = Instance::where('status', 'active')
                    // ->when($request->instances, function ($q) use ($request) {
                    //     return $q->whereIn('id', is_array($request->instances) ? $request->instances : collect($request->instances));
                    // })
                    ->get();
                foreach ($arrInstances as $instance) {
                    // MODEL LAMA
                    // $dataTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                    //     // ->where('month', $request->month)
                    //     ->where('month', date('m'))
                    //     ->where('instance_id', $instance->id)
                    //     ->get()
                    //     ->sum('total_anggaran');

                    // MODEL BARU
                    // $dataTargetAnggaran = DB::table('data_target_kinerja')
                    //     ->where('instance_id', $instance->id)
                    //     ->where('year', $request->year)
                    //     ->where('month', 12)
                    //     // ->groupBy('program_id')
                    //     // ->groupBy('kode_rekening_id')
                    //     ->sum('pagu_sipd');

                    $dataTargetAnggaran = DB::table('instance_summary')
                        ->where('instance_id', $instance->id)
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->where('month', date('m'))
                        ->sum('pagu_anggaran');

                    $dataRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        // ->where('month', $request->month)
                        ->where('month', date('m'))
                        ->latest('month')
                        ->where('instance_id', $instance->id)
                        ->where('status', 'verified')
                        ->get()
                        ->sum('realisasi_anggaran') ?? 0;

                    $dataRealisasiAnggaran = DB::table('instance_summary')
                        ->where('instance_id', $instance->id)
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->where('month', date('m'))
                        ->sum('realisasi_anggaran');

                    if ($dataRealisasiAnggaran === 0) {
                        $latest = DB::table('instance_summary')
                            ->where('instance_id', $instance->id)
                            ->where('periode_id', $request->periode)
                            ->where('year', $request->year)
                            ->where('realisasi_anggaran', '>', 0)
                            ->latest('month')
                            ->first();
                        if ($latest) {
                            $dataRealisasiAnggaran = DB::table('instance_summary')
                                ->where('instance_id', $instance->id)
                                ->where('periode_id', $request->periode)
                                ->where('year', $request->year)
                                ->where('month', $latest->month)
                                ->sum('realisasi_anggaran');
                        }

                        // $latest = RealisasiSubKegiatan::where('year', $request->year)
                        //     ->where('instance_id', $instance->id)
                        //     ->where('realisasi_anggaran', '>', 0)
                        //     ->latest('month')
                        //     ->where('status', 'verified')
                        //     ->first();
                        // if ($latest) {
                        //     $dataRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        //         ->where('month', $latest->month)
                        //         ->where('instance_id', $instance->id)
                        //         ->where('status', 'verified')
                        //         ->get()
                        //         ->sum('realisasi_anggaran') ?? 0;
                        // }
                    }

                    $dataPersentaseRealisasiKeuangan = ($dataRealisasiAnggaran && $dataTargetAnggaran) ? (floatval($dataRealisasiAnggaran) / floatval($dataTargetAnggaran)) * 100 : 0;

                    // $dataRealisasiKinerja = 0;

                    // $dataRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    //     ->where('month', date('m'))
                    //     ->where('instance_id', $instance->id)
                    //     ->where('status', 'verified')
                    //     ->sum('persentase_realisasi_kinerja') ?? 0;

                    $dataRealisasiKinerja = DB::table('instance_summary')
                        ->where('instance_id', $instance->id)
                        ->where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->where('month', date('m'))
                        ->sum('persentase_kinerja');

                    // $subKegiatanCount = SubKegiatan::where('instance_id', $instance->id)
                    //     ->where('status', 'active')
                    //     ->count();
                    // if ($dataRealisasiKinerja === 0) {
                    //     $latest = RealisasiSubKegiatan::where('year', $request->year)
                    //         ->where('instance_id', $instance->id)
                    //         ->where('persentase_realisasi_kinerja', '>', 0)
                    //         ->where('status', 'verified')
                    //         ->latest('month')
                    //         ->first();
                    //     if ($latest) {
                    //         $dataRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    //             ->where('month', $latest->month)
                    //             ->where('instance_id', $instance->id)
                    //             ->where('status', 'verified')
                    //             ->sum('persentase_realisasi_kinerja') ?? 0;
                    //     }
                    // }
                    // $dataRealisasiKinerja = $dataRealisasiKinerja ? ($dataRealisasiKinerja / $subKegiatanCount) : 0;

                    // $dataRealisasi = RealisasiSubKegiatan::where('year', $request->year)
                    //     ->where('month', $request->month)
                    //     ->where('instance_id', $instance->id)
                    //     ->where('status', 'verified')
                    //     ->first();
                    // $dataRealisasiKinerja = 0;
                    // if ($dataRealisasi) {
                    //     $dataRealisasiKinerja = $dataRealisasi->persentase_realisasi_kinerja ?? 0;
                    // }
                    // if (!$dataRealisasi || $dataRealisasi->persentase_realisasi_kinerja === 0) {
                    //     $dataRealisasi = RealisasiSubKegiatan::where('year', $request->year)
                    //         ->latest('month')
                    //         ->where('instance_id', $instance->id)
                    //         ->where('status', 'verified')
                    //         ->first();
                    //     $dataRealisasiKinerja = $dataRealisasi->persentase_realisasi_kinerja ?? 0;
                    // }

                    $data[] = [
                        'instance_id' => $instance->id,
                        'instance_alias' => $instance->alias,
                        'instance_name' => $instance->name,
                        'instance_code' => $instance->code,
                        'instance_logo' => asset($instance->logo) ?? null,
                        'instance_programs_count' => $instance->Programs->where('periode_id', $request->periode)->count(),
                        'instance_kegiatans_count' => $instance->Kegiatans->where('periode_id', $request->periode)->count(),
                        'instance_sub_kegiatans_count' => $instance->SubKegiatans->where('periode_id', $request->periode)->count(),
                        'target_anggaran' => $dataTargetAnggaran ?? 0,
                        'realisasi_anggaran' => $dataRealisasiAnggaran ?? 0,
                        'persentase_realisasi_anggaran' => $dataPersentaseRealisasiKeuangan ?? 0,
                        'target_kinerja' => 100,
                        'realisasi_kinerja' => $dataRealisasiKinerja ?? 0,
                        'persentase_realisasi_kinerja' => $dataRealisasiKinerja ?? 0,
                    ];
                }
                // Cache data
                Cache::put('DashOI-RankInstance-' . $request->year . '-' . $request->periode, $data, now()->addHours(1));
            }

            $data = collect($data);

            if ($sortBy == 'keuangan') {
                $data = $data->sortByDesc('persentase_realisasi_anggaran')->values()->all();
            }
            if ($sortBy == 'kinerja') {
                $data = $data->sortByDesc('persentase_realisasi_kinerja')->values()->all();
            }
            // $data = $data->sortByDesc('persentase_realisasi_anggaran')->values()->all();
            $rank = 1;
            foreach ($data as $key => $value) {
                $data[$key]['rank'] = $rank;
                $rank++;
            }

            return $this->successResponse($data, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }


    function detailInstance($instance_id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $return = [];
            $instance = Instance::where('id', $instance_id)->first();
            if (!$instance) {
                return $this->errorResponse('Instance tidak ditemukan');
            }

            $website = $instance->website;
            if ($website) {
                if (str()->contains($website, 'http')) {
                    $website = $instance->website;
                } else {
                    $website = 'http://' . $instance->website;
                }
            }
            $facebook = $instance->facebook;
            $facebookName = null;
            if ($facebook) {
                if (str()->contains($facebook, 'http')) {
                    $facebook = $instance->facebook;
                    $facebookName = str()->afterLast($facebook, '/');
                } else {
                    $facebook = 'http://facebook.com/search/top/?q=' . $instance->facebook;
                    $facebookName = $instance->facebook;
                }
            }
            $instagram = $instance->instagram;
            $instagramName = null;
            if ($instagram) {
                if (str()->contains($instagram, 'http')) {
                    $instagram = $instance->instagram;
                    $instagramName = str()->afterLast($instagram, '/');
                } else {
                    $instagram = 'http://instagram.com/' . $instance->instagram;
                    $instagramName = $instance->instagram;
                }
            }
            $youtube = $instance->youtube;
            $youtubeName = null;
            if ($youtube) {
                if (str()->contains($youtube, 'http')) {
                    $youtube = $instance->youtube;
                    $youtubeName = str()->afterLast($youtube, '/');
                } else {
                    $youtube = 'http://youtube.com/results?search_query=' . $instance->youtube;
                    $youtubeName = $instance->youtube;
                }
            }

            $returnInstance = [
                'id' => $instance->id,
                'alias' => $instance->alias,
                'name' => $instance->name,
                'code' => $instance->code,
                'description' => $instance->description,
                'logo' => asset($instance->logo) ?? null,
                'address' => $instance->address,
                'phone' => $instance->phone,
                'email' => $instance->email,
                'website' => $website,
                'facebook' => $facebook,
                'facebookName' => $facebookName,
                'instagram' => $instagram,
                'instagramName' => $instagramName,
                'youtube' => $youtube,
                'youtubeName' => $youtubeName,
                'programs_count' => $instance->Programs->where('periode_id', $request->periode)->count(),
                'kegiatans_count' => $instance->Kegiatans->where('periode_id', $request->periode)->count(),
                'sub_kegiatans_count' => $instance->SubKegiatans->where('periode_id', $request->periode)->count(),
            ];

            if ($request->view == 1) {
                $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }

            if ($request->view == 2) {
                $rangeMonths = [1, 2, 3];
            }

            if ($request->view == 3) {
                $rangeMonths = [4, 5, 6];
            }

            if ($request->view == 4) {
                $rangeMonths = [7, 8, 9];
            }

            if ($request->view == 5) {
                $rangeMonths = [10, 11, 12];
            }

            if ($request->view > 5 || $request->view < 1) {
                return $this->errorResponse('View tidak valid');
            }

            $dataTargetAnggaranMain = [];
            $dataRealisasiAnggaranMain = [];

            $dataTargetKinerjaMain = [];
            $dataRealisasiKinerjaMain = [];
            $dataPersentaseRealisasiKinerjaMain = null;

            $arrSubKegiatan = SubKegiatan::where('instance_id', $instance->id)
                ->where('periode_id', $request->periode)
                // ->where('status', 'active')
                ->get();
            foreach ($rangeMonths as $month) {

                // Keuangan
                // MODEL LAMA
                // $sumTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                //     ->where('instance_id', $instance->id)
                //     ->get()
                //     ->sum('total_anggaran');

                // MODEL BARU
                $sumTargetAnggaran = DB::table('data_target_kinerja')
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('year', $request->year)
                    ->where('month', $month)
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');
                $dataTargetAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => $sumTargetAnggaran ?? 0,
                ];

                $sumRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('realisasi_anggaran');
                $dataRealisasiAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiAnggaran ?? 0,
                ];

                // Kinerja
                $dataTargetKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => 100,
                ];
                $sumRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('persentase_realisasi_kinerja');
                $dataRealisasiKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiKinerja ? ($sumRealisasiKinerja / $arrSubKegiatan->count()) : 0,
                ];
            }

            // MODEL LAMA
            // $summaryTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('month', date('m'))
            //     ->get()
            //     ->sum('total_anggaran');

            // MODEL BARU
            $summaryTargetAnggaran = DB::table('data_target_kinerja')
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('year', $request->year)
                ->where('month', date('m'))
                // ->groupBy('program_id')
                // ->groupBy('kode_rekening_id')
                ->sum('pagu_sipd');

            $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', $request->month)
                ->where('instance_id', $instance->id)
                // ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->get();
            if ($summaryRealisasiAnggaran->sum('realisasi_anggaran') === 0) {
                $latestRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    // ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('realisasi_anggaran', '>', 0)
                    ->latest('month')
                    ->where('status', 'verified')
                    ->first();
                if ($latestRealisasiAnggaran) {
                    $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('instance_id', $instance->id)
                        // ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('realisasi_anggaran', '>', 0)
                        ->where('month', $latestRealisasiAnggaran->month)
                        ->where('status', 'verified')
                        ->get();
                }
            }


            $dataPersentaseRealisasiKinerjaMain = RealisasiSubKegiatan::where('year', $request->year)
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('month', $request->month)
                ->where('status', 'verified')
                ->sum('persentase_realisasi_kinerja') ?? 0;

            $latestKinerja = null;
            if ($dataPersentaseRealisasiKinerjaMain === 0) {
                $latestKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('persentase_realisasi_kinerja', '>', 0)
                    ->latest('month')
                    ->where('status', 'verified')
                    ->first();
                if ($latestKinerja) {
                    $dataPersentaseRealisasiKinerjaMain = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('month', $latestKinerja->month)
                        ->where('status', 'verified')
                        ->sum('persentase_realisasi_kinerja');
                }
            }

            $dataPersentaseRealisasiKinerjaMain = $dataPersentaseRealisasiKinerjaMain ? ($dataPersentaseRealisasiKinerjaMain / $arrSubKegiatan->count()) : 0;

            $anggaranUpdatedAt = null;
            if ($summaryRealisasiAnggaran) {
                $anggaranUpdatedAt = $summaryRealisasiAnggaran->sortBy('month')->first()->updated_at ?? null;
                // $anggaranUpdatedAt = now();
            }
            $realisasiUpdatedAt = null;
            if ($latestKinerja) {
                $realisasiUpdatedAt = $latestKinerja->updated_at ?? null;
            }

            $persentaseSummaryAnggaran = 0;
            if ($summaryRealisasiAnggaran && $summaryTargetAnggaran) {
                $persentaseSummaryAnggaran = ($summaryRealisasiAnggaran->sum('realisasi_anggaran') / $summaryTargetAnggaran) * 100;
                $persentaseSummaryAnggaran = number_format($persentaseSummaryAnggaran, 4);
                $persentaseSummaryAnggaran = floatval($persentaseSummaryAnggaran);
            }

            // List Programs Start
            $arrPrograms = Program::where('instance_id', $instance->id)
                ->where('periode_id', $request->periode)
                ->orderBy('fullcode')
                ->where('status', 'active')
                ->get();
            $returnPrograms = [];
            foreach ($arrPrograms as $program) {
                // MODEL LAMA
                // $totalAnggaranApbd = ApbdSubKegiatan::where('instance_id', $instance->id)
                //     ->where('program_id', $program->id)
                //     ->where('year', $request->year)
                //     ->where('month', date('m'))
                //     ->where('status', 'active')
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $totalAnggaranApbd = DB::table('data_target_kinerja')
                    ->where('instance_id', $instance->id)
                    ->where('program_id', $program->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');

                $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('program_id', $program->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('realisasi_anggaran');
                if ($realisasiAnggaran === 0) {
                    $latestRealisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('program_id', $program->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('realisasi_anggaran', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiAnggaran) {
                        $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('program_id', $program->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiAnggaran->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('realisasi_anggaran');
                    }
                }
                $percentRealisasiAnggaran = 0;
                if ($totalAnggaranApbd) {
                    $percentRealisasiAnggaran = ($realisasiAnggaran / $totalAnggaranApbd) * 100;
                }

                $countSubKegiatans = SubKegiatan::where('instance_id', $instance->id)
                    ->where('program_id', $program->id)
                    ->where('status', 'active')
                    ->count();
                $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('program_id', $program->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('persentase_realisasi_kinerja');
                if ($realisasiKinerja === 0) {
                    $latestRealisasiSubKegiatanHasKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('program_id', $program->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('persentase_realisasi_kinerja', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiSubKegiatanHasKinerja) {
                        $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('program_id', $program->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiSubKegiatanHasKinerja->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('persentase_realisasi_kinerja');
                    }
                }
                $realisasiKinerja = $realisasiKinerja ? ($realisasiKinerja / $countSubKegiatans) : 0;

                $returnPrograms[] = [
                    'id' => $program->id,
                    'code' => $program->fullcode,
                    'name' => $program->name,
                    'instance_name' => $program->Instance->name ?? '',
                    'instance_code' => $program->Instance->code ?? '',
                    'instance_alias' => $program->Instance->alias ?? '',
                    'instance_sub_unit' => $program->SubUnit ?? [],
                    'anggaran' => $totalAnggaranApbd ?? 0,
                    'realisasi_anggaran' => $realisasiAnggaran ?? 0,
                    'persentase_realisasi_anggaran' => $percentRealisasiAnggaran ?? 0,
                    'persentase_realisasi_kinerja' => $realisasiKinerja ?? 0,
                ];
            }
            // List Programs End

            // List Users Start
            $arrUsers = User::where('instance_id', $instance->id)
                ->orderBy('instance_type')
                ->latest()
                ->get();
            $returnUsers = [];
            foreach ($arrUsers as $user) {
                $returnUsers[] = [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'photo' => asset($user->photo),
                    'role' => $user->Role->display_name ?? null,
                    'instance_id' => $user->instance_id,
                    'instance_name' => $user->Instance->name ?? null,
                    'instance_type' => $user->instance_type,
                    'last_activity' => $user->last_activity ?? null,
                ];
            }

            return $this->successResponse([
                'instance' => $returnInstance,
                'admins' => $returnUsers,
                'main_anggaran' => [
                    'target' => $dataTargetAnggaranMain,
                    'realisasi' => $dataRealisasiAnggaranMain,
                ],
                'main_kinerja' => [
                    'target' => $dataTargetKinerjaMain,
                    'realisasi' => $dataRealisasiKinerjaMain,
                ],
                'summary_anggaran' => [
                    'anggaran' => $summaryTargetAnggaran ?? 0,
                    'anggaran_updated_at' => $anggaranUpdatedAt,
                    'realisasi' => $summaryRealisasiAnggaran->sum('realisasi_anggaran') ?? 0,
                    'realisasi_updated_at' => $realisasiUpdatedAt ?? null,
                    'persentase' => $persentaseSummaryAnggaran ?? 0,
                ],
                'summary_kinerja' => [
                    'target' => 100,
                    'realisasi' => $dataPersentaseRealisasiKinerjaMain,
                    'realisasi_updated_at' => $realisasiUpdatedAt,
                ],
                'programs' => $returnPrograms,
            ], 'Data berhasil diambil');
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }


    function detailProgramInstance($instance_id, $id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $return = [];
            $instance = Instance::where('id', $instance_id)->first();
            if (!$instance) {
                return $this->errorResponse('Instance tidak ditemukan');
            }
            $program = Program::where('id', $id)
                ->where('instance_id', $instance->id)
                ->where('status', 'active')
                ->first();
            if (!$program) {
                return $this->errorResponse('Program tidak ditemukan');
            }

            if ($request->view == 1) {
                $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }

            if ($request->view == 2) {
                $rangeMonths = [1, 2, 3];
            }

            if ($request->view == 3) {
                $rangeMonths = [4, 5, 6];
            }

            if ($request->view == 4) {
                $rangeMonths = [7, 8, 9];
            }

            if ($request->view == 5) {
                $rangeMonths = [10, 11, 12];
            }

            if ($request->view > 5 || $request->view < 1) {
                return $this->errorResponse('View tidak valid');
            }

            $dataTargetAnggaranMain = [];
            $dataRealisasiAnggaranMain = [];

            $dataTargetKinerjaMain = [];
            $dataRealisasiKinerjaMain = [];
            $dataPersentaseRealisasiKinerjaMain = null;

            $arrSubKegiatan = SubKegiatan::where('instance_id', $instance->id)
                ->where('program_id', $program->id)
                ->where('status', 'active')
                ->get();

            foreach ($rangeMonths as $month) {
                // Keuangan
                // MODEL LAMA
                // $sumTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $sumTargetAnggaran = DB::table('data_target_kinerja')
                    ->where('year', $request->year)
                    ->where('month', $month)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');
                $dataTargetAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => $sumTargetAnggaran ?? 0,
                ];

                // $summaryTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                //     ->where('month', date('m'))
                //     ->get()
                //     ->sum('total_anggaran');

                $sumRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('realisasi_anggaran');

                $dataRealisasiAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiAnggaran ?? 0,
                ];
                // Kinerja
                $dataTargetKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => 100,
                ];
                $sumRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('persentase_realisasi_kinerja');
                $dataRealisasiKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiKinerja ? ($sumRealisasiKinerja / $arrSubKegiatan->count()) : 0,
                ];
            }


            // Keuangan Summary
            // MODEL LAMA
            // $summaryTotalAnggaran = ApbdSubKegiatan::where('year', $request->year)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('month', date('m'))
            //     ->get()
            //     ->sum('total_anggaran');
            // MODEL BARU
            $summaryTotalAnggaran = DB::table('data_target_kinerja')
                ->where('year', $request->year)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('month', date('m'))
                // ->groupBy('program_id')
                // ->groupBy('kode_rekening_id')
                ->sum('pagu_sipd');
            // $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('realisasi_anggaran') ?? 0;
            $summaryRealisasiAnggaran = collect($dataRealisasiAnggaranMain)->max('realisasi');

            $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiAnggaran === 0) {
                $latestSummaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('realisasi_anggaran', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiAnggaran) {
                    $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('realisasi_anggaran');
                    $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            // Kinerja Summary
            // $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('persentase_realisasi_kinerja') ?? 0;

            $summaryRealisasiKinerja = collect($dataRealisasiKinerjaMain)->max('realisasi');

            $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiKinerja === 0) {
                $latestSummaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('persentase_realisasi_kinerja', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiKinerja) {
                    $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('persentase_realisasi_kinerja');
                    $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            $persentaseSummaryRealisasiKinerja = $summaryRealisasiKinerja ? ($summaryRealisasiKinerja / $arrSubKegiatan->count()) : 0;

            $arrKegiatans = Kegiatan::where('instance_id', $instance->id)
                ->where('program_id', $program->id)
                ->where('status', 'active')
                ->orderBy('fullcode')
                ->get();
            $returnKegiatans = [];
            foreach ($arrKegiatans as $kegiatan) {
                // MODEL LAMA
                // $totalAnggaranApbd = ApbdSubKegiatan::where('instance_id', $instance->id)
                //     ->where('kegiatan_id', $kegiatan->id)
                //     ->where('year', $request->year)
                //     ->where('month', date('m'))
                //     ->where('status', 'active')
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $totalAnggaranApbd = DB::table('data_target_kinerja')
                    ->where('instance_id', $instance->id)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');
                $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('realisasi_anggaran');

                if ($realisasiAnggaran === 0) {
                    $latestRealisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('realisasi_anggaran', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiAnggaran) {
                        $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiAnggaran->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('realisasi_anggaran');
                    }
                }

                $percentRealisasiAnggaran = 0;
                if ($totalAnggaranApbd) {
                    $percentRealisasiAnggaran = ($realisasiAnggaran / $totalAnggaranApbd) * 100;
                }

                $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('persentase_realisasi_kinerja');
                if ($realisasiKinerja === 0) {
                    $latestRealisasiSubKegiatanHasKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('persentase_realisasi_kinerja', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiSubKegiatanHasKinerja) {
                        $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiSubKegiatanHasKinerja->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('persentase_realisasi_kinerja');
                    }
                }

                $realisasiKinerja = $realisasiKinerja ?
                    ($realisasiKinerja / $arrSubKegiatan
                        ->where('kegiatan_id', $kegiatan->id)
                        ->count()) : 0;

                $returnKegiatans[] = [
                    'id' => $kegiatan->id,
                    'code' => $kegiatan->fullcode,
                    'name' => $kegiatan->name,
                    'anggaran' => $totalAnggaranApbd ?? 0,
                    'realisasi_anggaran' => $realisasiAnggaran ?? 0,
                    'persentase_realisasi_anggaran' => $percentRealisasiAnggaran ?? 0,
                    'persentase_realisasi_kinerja' => $realisasiKinerja ?? 0,
                ];
            }

            return $this->successResponse([
                'chart_keuangan' => [
                    'target' => $dataTargetAnggaranMain,
                    'realisasi' => $dataRealisasiAnggaranMain,
                ],
                'chart_kinerja' => [
                    'target' => $dataTargetKinerjaMain,
                    'realisasi' => $dataRealisasiKinerjaMain,
                ],
                'summary' => [
                    'target_kinerja' => 100,
                    'realisasi_kinerja' => $summaryRealisasiKinerja ?? 0,
                    'realisasi_kinerja_updated_at' => $summaryRealisasiKinerjaUpdatedAt->updated_at ?? null,
                    'target_anggaran' => $summaryTotalAnggaran ?? 0,
                    'realisasi_anggaran' => $summaryRealisasiAnggaran ?? 0,
                    'realisasi_anggaran_updated_at' => $summaryRealisasiAnggaranUpdatedAt->updated_at ?? null,
                    'persentase_realisasi_anggaran' => ($summaryRealisasiAnggaran && $summaryTotalAnggaran)
                        ? ($summaryRealisasiAnggaran / $summaryTotalAnggaran) * 100
                        : 0,
                ],
                'kegiatans' => $returnKegiatans,
            ], 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    function detailKegiatanInstance($instance_id, $id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $return = [];
            $instance = Instance::where('id', $instance_id)->first();
            if (!$instance) {
                return $this->errorResponse('Instance tidak ditemukan');
            }
            $kegiatan = Kegiatan::where('id', $id)
                ->where('instance_id', $instance->id)
                ->where('status', 'active')
                ->first();
            if (!$kegiatan) {
                return $this->errorResponse('Kegiatan tidak ditemukan');
            }
            $program = Program::where('id', $kegiatan->program_id)
                ->where('instance_id', $instance->id)
                ->where('status', 'active')
                ->first();

            if ($request->view == 1) {
                $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }

            if ($request->view == 2) {
                $rangeMonths = [1, 2, 3];
            }

            if ($request->view == 3) {
                $rangeMonths = [4, 5, 6];
            }

            if ($request->view == 4) {
                $rangeMonths = [7, 8, 9];
            }

            if ($request->view == 5) {
                $rangeMonths = [10, 11, 12];
            }

            if ($request->view > 5 || $request->view < 1) {
                return $this->errorResponse('View tidak valid');
            }

            $dataTargetAnggaranMain = [];
            $dataRealisasiAnggaranMain = [];

            $dataTargetKinerjaMain = [];
            $dataRealisasiKinerjaMain = [];
            $dataPersentaseRealisasiKinerjaMain = null;

            $arrSubKegiatan = SubKegiatan::where('instance_id', $instance->id)
                ->where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('status', 'active')
                ->get();


            foreach ($rangeMonths as $month) {
                // Keuangan
                // MODEL LAMA
                // $sumTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $sumTargetAnggaran = DB::table('data_target_kinerja')
                    ->where('year', $request->year)
                    ->where('month', $month)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');

                $dataTargetAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => $sumTargetAnggaran ?? 0,
                ];

                $sumRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('realisasi_anggaran');

                $dataRealisasiAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiAnggaran ?? 0,
                ];
                // Kinerja
                $dataTargetKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => 100,
                ];
                $sumRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('persentase_realisasi_kinerja');
                $dataRealisasiKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiKinerja ? ($sumRealisasiKinerja / $arrSubKegiatan->count()) : 0,
                ];
            }


            // Keuangan Summary
            // MODEL LAMA
            // $summaryTotalAnggaran = ApbdSubKegiatan::where('year', $request->year)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('month', date('m'))
            //     ->get()
            //     ->sum('total_anggaran');
            // MODEL BARU
            $summaryTotalAnggaran = DB::table('data_target_kinerja')
                ->where('year', $request->year)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('month', date('m'))
                // ->groupBy('program_id')
                // ->groupBy('kode_rekening_id')
                ->sum('pagu_sipd');
            // $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('realisasi_anggaran') ?? 0;

            $summaryRealisasiAnggaran = collect($dataRealisasiAnggaranMain)->max('realisasi');

            $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiAnggaran === 0) {
                $latestSummaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('realisasi_anggaran', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiAnggaran) {
                    $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('realisasi_anggaran');
                    $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            // Kinerja Summary
            // $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('persentase_realisasi_kinerja') ?? 0;

            $summaryRealisasiKinerja = collect($dataRealisasiKinerjaMain)->max('realisasi');

            $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiKinerja === 0) {
                $latestSummaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('persentase_realisasi_kinerja', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiKinerja) {
                    $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('persentase_realisasi_kinerja');
                    $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            $persentaseSummaryRealisasiKinerja = $summaryRealisasiKinerja ? ($summaryRealisasiKinerja / $arrSubKegiatan->count()) : 0;


            $arrSubKegiatans = SubKegiatan::where('instance_id', $instance->id)
                ->where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('status', 'active')
                ->orderBy('fullcode')
                ->get();
            $returnSubKegiatans = [];

            foreach ($arrSubKegiatans as $subKegiatan) {
                // MODEL LAMA
                // $totalAnggaranApbd = ApbdSubKegiatan::where('instance_id', $instance->id)
                //     ->where('sub_kegiatan_id', $subKegiatan->id)
                //     ->where('year', $request->year)
                //     ->where('month', date('m'))
                //     ->where('status', 'active')
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $totalAnggaranApbd = DB::table('data_target_kinerja')
                    ->where('instance_id', $instance->id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');
                $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('realisasi_anggaran');

                if ($realisasiAnggaran === 0) {
                    $latestRealisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('sub_kegiatan_id', $subKegiatan->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('realisasi_anggaran', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiAnggaran) {
                        $realisasiAnggaran = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiAnggaran->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('realisasi_anggaran');
                    }
                }

                $percentRealisasiAnggaran = 0;
                if ($totalAnggaranApbd) {
                    $percentRealisasiAnggaran = ($realisasiAnggaran / $totalAnggaranApbd) * 100;
                }

                $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', date('m'))
                    ->where('status', 'verified')
                    ->get()
                    ->sum('persentase_realisasi_kinerja');
                if ($realisasiKinerja === 0) {
                    $latestRealisasiSubKegiatanHasKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                        ->where('sub_kegiatan_id', $subKegiatan->id)
                        ->where('year', $request->year)
                        ->where('status', 'verified')
                        ->where('persentase_realisasi_kinerja', '>', 0)
                        ->latest('month')
                        ->first();
                    if ($latestRealisasiSubKegiatanHasKinerja) {
                        $realisasiKinerja = RealisasiSubKegiatan::where('instance_id', $instance->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('year', $request->year)
                            ->where('month', $latestRealisasiSubKegiatanHasKinerja->month)
                            ->where('status', 'verified')
                            ->get()
                            ->sum('persentase_realisasi_kinerja');
                    }
                }

                // $realisasiKinerja = $realisasiKinerja ? ($realisasiKinerja / $arrSubKegiatan->count()) : 0;
                $realisasiKinerja = $realisasiKinerja;

                $returnSubKegiatans[] = [
                    'id' => $subKegiatan->id,
                    'code' => $subKegiatan->fullcode,
                    'name' => $subKegiatan->name,
                    'anggaran' => $totalAnggaranApbd ?? 0,
                    'realisasi_anggaran' => $realisasiAnggaran ?? 0,
                    'persentase_realisasi_anggaran' => $percentRealisasiAnggaran ?? 0,
                    'persentase_realisasi_kinerja' => $realisasiKinerja ?? 0,
                ];
            }


            return $this->successResponse([
                'chart_keuangan' => [
                    'target' => $dataTargetAnggaranMain,
                    'realisasi' => $dataRealisasiAnggaranMain,
                ],
                'chart_kinerja' => [
                    'target' => $dataTargetKinerjaMain,
                    'realisasi' => $dataRealisasiKinerjaMain,
                ],
                'summary' => [
                    'target_kinerja' => 100,
                    'realisasi_kinerja' => $summaryRealisasiKinerja ?? 0,
                    'realisasi_kinerja_updated_at' => $summaryRealisasiKinerjaUpdatedAt->updated_at ?? null,
                    'target_anggaran' => $summaryTotalAnggaran ?? 0,
                    'realisasi_anggaran' => $summaryRealisasiAnggaran ?? 0,
                    'realisasi_anggaran_updated_at' => $summaryRealisasiAnggaranUpdatedAt->updated_at ?? null,
                    'persentase_realisasi_anggaran' => ($summaryRealisasiAnggaran && $summaryTotalAnggaran)
                        ? ($summaryRealisasiAnggaran / $summaryTotalAnggaran) * 100
                        : 0,
                ],
                'sub_kegiatans' => $returnSubKegiatans,
            ], 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    function detailSubKegiatanInstance($instance_id, $id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);

        try {
            $return = [];
            $instance = Instance::where('id', $instance_id)->first();
            if (!$instance) {
                return $this->errorResponse('Instance tidak ditemukan');
            }
            $subKegiatan = SubKegiatan::find($id);
            if (!$subKegiatan) {
                return $this->errorResponse('Sub Kegiatan tidak ditemukan');
            }
            $kegiatan = Kegiatan::where('id', $subKegiatan->kegiatan_id)
                ->where('instance_id', $instance->id)
                ->where('status', 'active')
                ->first();
            $program = Program::where('id', $kegiatan->program_id)
                ->where('instance_id', $instance->id)
                ->where('status', 'active')
                ->first();

            if ($request->view == 1) {
                $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }

            if ($request->view == 2) {
                $rangeMonths = [1, 2, 3];
            }

            if ($request->view == 3) {
                $rangeMonths = [4, 5, 6];
            }

            if ($request->view == 4) {
                $rangeMonths = [7, 8, 9];
            }

            if ($request->view == 5) {
                $rangeMonths = [10, 11, 12];
            }

            if ($request->view > 5 || $request->view < 1) {
                return $this->errorResponse('View tidak valid');
            }

            $dataTargetAnggaranMain = [];
            $dataRealisasiAnggaranMain = [];

            $dataTargetKinerjaMain = [];
            $dataRealisasiKinerjaMain = [];
            $dataPersentaseRealisasiKinerjaMain = null;

            $arrSubKegiatan = SubKegiatan::where('instance_id', $instance->id)
                ->where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('id', $subKegiatan->id)
                ->where('status', 'active')
                ->get();


            foreach ($rangeMonths as $month) {
                // Keuangan
                // MODEL LAMA
                // $sumTargetAnggaran = ApbdSubKegiatan::where('year', $request->year)
                //     ->where('month', $month)
                //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                //     ->get()
                //     ->sum('total_anggaran');
                // MODEL BARU
                $sumTargetAnggaran = DB::table('data_target_kinerja')
                    ->where('year', $request->year)
                    ->where('month', $month)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    // ->groupBy('program_id')
                    // ->groupBy('kode_rekening_id')
                    ->sum('pagu_sipd');
                $dataTargetAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => $sumTargetAnggaran ?? 0,
                ];

                $sumRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('realisasi_anggaran');

                $dataRealisasiAnggaranMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiAnggaran ?? 0,
                ];
                // Kinerja
                $dataTargetKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'target' => 100,
                ];
                $sumRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('month', $month)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('status', 'verified')
                    ->sum('persentase_realisasi_kinerja');
                $dataRealisasiKinerjaMain[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                    'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                    'realisasi' => $sumRealisasiKinerja ? ($sumRealisasiKinerja / $arrSubKegiatan->count()) : 0,
                ];
            }

            // Keuangan Summary
            // $summaryTotalAnggaran = ApbdSubKegiatan::where('year', $request->year)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('month', date('m'))
            //     ->get()
            //     ->sum('total_anggaran');
            // MODEL BARU
            $summaryTotalAnggaran = DB::table('data_target_kinerja')
                ->where('year', $request->year)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('month', date('m'))
                // ->groupBy('program_id')
                // ->groupBy('kode_rekening_id')
                ->sum('pagu_sipd');
            // $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('realisasi_anggaran') ?? 0;

            $summaryRealisasiAnggaran = collect($dataRealisasiAnggaranMain)->max('realisasi');

            $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiAnggaran === 0) {
                $latestSummaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('realisasi_anggaran', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiAnggaran) {
                    $summaryRealisasiAnggaran = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('realisasi_anggaran');
                    $summaryRealisasiAnggaranUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiAnggaran->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            // Kinerja Summary
            // $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
            //     ->where('month', date('m'))
            //     ->where('instance_id', $instance->id)
            //     ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
            //     ->where('status', 'verified')
            //     ->get()
            //     ->sum('persentase_realisasi_kinerja') ?? 0;

            $summaryRealisasiKinerja = collect($dataRealisasiKinerjaMain)->max('realisasi');

            $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                ->where('month', date('m'))
                ->where('instance_id', $instance->id)
                ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                ->where('status', 'verified')
                ->latest()
                ->first();
            if ($summaryRealisasiKinerja === 0) {
                $latestSummaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                    ->where('instance_id', $instance->id)
                    ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                    ->where('persentase_realisasi_kinerja', '>', 0)
                    ->where('status', 'verified')
                    ->latest('month')
                    ->first();
                if ($latestSummaryRealisasiKinerja) {
                    $summaryRealisasiKinerja = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->get()
                        ->sum('persentase_realisasi_kinerja');
                    $summaryRealisasiKinerjaUpdatedAt = RealisasiSubKegiatan::where('year', $request->year)
                        ->where('month', $latestSummaryRealisasiKinerja->month)
                        ->where('instance_id', $instance->id)
                        ->whereIn('sub_kegiatan_id', $arrSubKegiatan->pluck('id')->toArray())
                        ->where('status', 'verified')
                        ->latest()
                        ->first();
                }
            }

            $persentaseSummaryRealisasiKinerja = $summaryRealisasiKinerja ? ($summaryRealisasiKinerja / $arrSubKegiatan->count()) : 0;



            return $this->successResponse([
                'chart_keuangan' => [
                    'target' => $dataTargetAnggaranMain,
                    'realisasi' => $dataRealisasiAnggaranMain,
                ],
                'chart_kinerja' => [
                    'target' => $dataTargetKinerjaMain,
                    'realisasi' => $dataRealisasiKinerjaMain,
                ],
                'summary' => [
                    'target_kinerja' => 100,
                    'realisasi_kinerja' => $summaryRealisasiKinerja ?? 0,
                    'realisasi_kinerja_updated_at' => $summaryRealisasiKinerjaUpdatedAt->updated_at ?? null,
                    'target_anggaran' => $summaryTotalAnggaran ?? 0,
                    'realisasi_anggaran' => $summaryRealisasiAnggaran ?? 0,
                    'realisasi_anggaran_updated_at' => $summaryRealisasiAnggaranUpdatedAt->updated_at ?? null,
                    'persentase_realisasi_anggaran' => ($summaryRealisasiAnggaran && $summaryTotalAnggaran)
                        ? ($summaryRealisasiAnggaran / $summaryTotalAnggaran) * 100
                        : 0,
                ],
                'rincian_realisasi' => $this->_GetDetailRealisasiInstance($subKegiatan->id, $request->year, date('m')),
            ], 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    function _GetDetailRealisasiInstance($idSubKegiatan, $year, $month)
    {
        $return = [];
        // $subKegiatan = SubKegiatan::find($idSubKegiatan);

        $RealisasiStatus = RealisasiSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'verified')
            ->first();

        if (!$RealisasiStatus) {
            $RealisasiStatus = RealisasiSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
                ->where('year', $year)
                // ->where('month', $month)
                ->where('status', 'verified')
                ->latest('month')
                ->first();
            if (!$RealisasiStatus) {
                $return['data_error'] = true;
                $return['error_message'] = 'Data Realisasi Tidak Ditemukan / Belum Diverifikasi';
                return $return;
            }
        }

        $month = $RealisasiStatus->month;

        $return['info'] = [
            'last_update' => $RealisasiStatus->updated_at,
            'month' => $month,
            'month_name' => Carbon::createFromDate($year, $month)->translatedFormat('F'),
            'month_short' => Carbon::createFromDate($year, $month)->translatedFormat('M'),
            'year' => $year,
        ];

        $RealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'verified')
            ->first();

        $arrTags = TaggingSumberDana::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('status', 'active')
            ->get();
        foreach ($arrTags as $tag) {
            $return['tags_sumber_dana'][] = [
                'id' => $tag->id,
                'tag_id' => $tag->ref_tag_id,
                'tag_name' => $tag->RefTag->name,
                'nominal' => $tag->nominal,
            ];
        }

        $return['data'] = [];

        $arrKodeRekSelected = TargetKinerja::select('kode_rekening_id')
            ->where('year', $year)
            ->where('month', $month)
            ->where('sub_kegiatan_id', $idSubKegiatan)
            ->groupBy('kode_rekening_id')
            ->get();

        $reks = [];
        $rincs = [];
        $objs = [];
        $jens = [];
        $kelos = [];
        $akuns = [];
        foreach ($arrKodeRekSelected as $krs) {
            $rekening = KodeRekening::find($krs->kode_rekening_id);
            if (!$rekening) {
                $return['data'][] = [
                    'editable' => false,
                    'long' => true,
                    'type' => 'rekening',
                    'id' => null,
                    'parent_id' => null,
                    'uraian' => 'Sub kegiatan ini Tidak Memiliki Kode Rekening',
                    'fullcode' => null,
                    'pagu' => 0,
                    'rincian_belanja' => [],
                ];
                $datas['data_error'] = true;
                $datas['error_message'] = 'Sub kegiatan ini Tidak Memiliki Kode Rekening';
                return $this->successResponse($datas, 'detail target kinerja');
            }
            $rekeningRincian = KodeRekening::find($rekening->parent_id);
            $rekeningObjek = KodeRekening::find($rekeningRincian->parent_id);
            $rekeningJenis = KodeRekening::find($rekeningObjek->parent_id);
            $rekeningKelompok = KodeRekening::find($rekeningJenis->parent_id);
            $rekeningAkun = KodeRekening::find($rekeningKelompok->parent_id);

            $akuns[] = $rekeningAkun;
            $kelos[] = $rekeningKelompok;
            $jens[] = $rekeningJenis;
            $objs[] = $rekeningObjek;
            $rincs[] = $rekeningRincian;
            $reks[] = $rekening;
        }

        $collectAkun = collect($akuns)->unique('id')->values();
        $collectKelompok = collect($kelos)->unique('id')->values();
        $collectJenis = collect($jens)->unique('id')->values();
        $collectObjek = collect($objs)->unique('id')->values();
        $collectRincian = collect($rincs)->unique('id')->values();
        $collectRekening = collect($reks)->unique('id')->values();

        foreach ($collectAkun as $akun) {
            $arrKodeRekenings = KodeRekening::where('parent_id', $akun->id)->get();
            $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
            $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
            $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
            $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

            $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                ->where('year', $year)
                ->where('month', $month)
                ->where('sub_kegiatan_id', $idSubKegiatan)
                ->get();
            $paguSipd = $arrDataTarget->sum('pagu_sipd');

            $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                ->where('year', $year)
                ->where('month', $month)
                ->where('sub_kegiatan_id', $idSubKegiatan)
                ->sum('anggaran');
            $return['data'][] = [
                'editable' => false,
                'long' => true,
                'type' => 'rekening',
                'rek' => 1,
                'id' => $akun->id,
                'parent_id' => null,
                'uraian' => $akun->name,
                'fullcode' => $akun->fullcode,
                'pagu' => $paguSipd,
                'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                'realisasi_year' => $year,
                'realisasi_month' => $month,
                'rincian_belanja' => [],
            ];

            // Level 2
            foreach ($collectKelompok->where('parent_id', $akun->id) as $kelompok) {
                $arrKodeRekenings = KodeRekening::where('parent_id', $kelompok->id)->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('sub_kegiatan_id', $idSubKegiatan)
                    ->get();
                $paguSipd = $arrDataTarget->sum('pagu_sipd');

                $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('sub_kegiatan_id', $idSubKegiatan)
                    ->sum('anggaran');
                $return['data'][] = [
                    'editable' => false,
                    'long' => true,
                    'type' => 'rekening',
                    'rek' => 2,
                    'id' => $kelompok->id,
                    'parent_id' => $akun->id,
                    'uraian' => $kelompok->name,
                    'fullcode' => $kelompok->fullcode,
                    'pagu' => $paguSipd,
                    'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                    'realisasi_year' => $year,
                    'realisasi_month' => $month,
                    'rincian_belanja' => [],
                ];

                // Level 3
                foreach ($collectJenis->where('parent_id', $kelompok->id) as $jenis) {
                    $arrKodeRekenings = KodeRekening::where('parent_id', $jenis->id)->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                    $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $year)
                        ->where('month', $month)
                        ->where('sub_kegiatan_id', $idSubKegiatan)
                        ->get();
                    $paguSipd = $arrDataTarget->sum('pagu_sipd');

                    $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $year)
                        ->where('month', $month)
                        ->where('sub_kegiatan_id', $idSubKegiatan)
                        ->sum('anggaran');
                    $return['data'][] = [
                        'editable' => false,
                        'long' => true,
                        'type' => 'rekening',
                        'rek' => 3,
                        'id' => $jenis->id,
                        'parent_id' => $kelompok->id,
                        'uraian' => $jenis->name,
                        'fullcode' => $jenis->fullcode,
                        'pagu' => $paguSipd,
                        'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                        'realisasi_year' => $year,
                        'realisasi_month' => $month,
                        'rincian_belanja' => [],
                    ];

                    // Level 4
                    foreach ($collectObjek->where('parent_id', $jenis->id) as $objek) {

                        $arrKodeRekenings = KodeRekening::where('parent_id', $objek->id)->get();
                        $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                        $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $year)
                            ->where('month', $month)
                            ->where('sub_kegiatan_id', $idSubKegiatan)
                            ->get();
                        $paguSipd = $arrDataTarget->sum('pagu_sipd');

                        $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $year)
                            ->where('month', $month)
                            ->where('sub_kegiatan_id', $idSubKegiatan)
                            ->sum('anggaran');
                        $return['data'][] = [
                            'editable' => false,
                            'long' => true,
                            'type' => 'rekening',
                            'rek' => 4,
                            'id' => $objek->id,
                            'parent_id' => $jenis->id,
                            'uraian' => $objek->name,
                            'fullcode' => $objek->fullcode,
                            'pagu' => $paguSipd,
                            'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                            'realisasi_year' => $year,
                            'realisasi_month' => $month,
                            'rincian_belanja' => [],
                        ];

                        // Level 5
                        foreach ($collectRincian->where('parent_id', $objek->id) as $rincian) {

                            $arrKodeRekenings = KodeRekening::where('parent_id', $rincian->id)->get();
                            $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $year)
                                ->where('month', $month)
                                ->where('sub_kegiatan_id', $idSubKegiatan)
                                ->get();
                            $paguSipd = $arrDataTarget->sum('pagu_sipd');

                            $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $year)
                                ->where('month', $month)
                                ->where('sub_kegiatan_id', $idSubKegiatan)
                                ->sum('anggaran');
                            $return['data'][] = [
                                'editable' => false,
                                'long' => true,
                                'type' => 'rekening',
                                'rek' => 5,
                                'id' => $rincian->id,
                                'parent_id' => $objek->id,
                                'uraian' => $rincian->name,
                                'fullcode' => $rincian->fullcode,
                                'pagu' => $paguSipd,
                                'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                                'realisasi_year' => $year,
                                'realisasi_month' => $month,
                                'rincian_belanja' => [],
                            ];

                            // Level 6
                            foreach ($collectRekening->where('parent_id', $rincian->id) as $rekening) {

                                $arrDataTarget = TargetKinerja::where('kode_rekening_id', $rekening->id)
                                    ->where('year', $year)
                                    ->where('month', $month)
                                    ->where('sub_kegiatan_id', $idSubKegiatan)
                                    ->orderBy('nama_paket')
                                    ->get();

                                $arrTargetKinerja = [];
                                foreach ($arrDataTarget as $dataTarget) {
                                    $tempPagu = TargetKinerjaRincian::where('target_kinerja_id', $dataTarget->id)->sum('pagu_sipd');
                                    $tempPagu = (int)$tempPagu;
                                    if ($dataTarget->is_detail === true) {
                                        $isPaguMatch = (int)$dataTarget->pagu_sipd === $tempPagu ? true : false;
                                    } elseif ($dataTarget->is_detail === false) {
                                        $isPaguMatch = true;
                                    }
                                    $dataRealisasi = Realisasi::where('target_id', $dataTarget->id)
                                        ->where('status', 'verified')
                                        ->first();

                                    $arrTargetKinerja[] = [
                                        'editable' => true,
                                        'long' => true,
                                        'type' => 'target-kinerja',
                                        'id_target' => $dataTarget->id,
                                        'id' => $dataRealisasi->id ?? null,
                                        'id_rek_1' => $akun->id,
                                        'id_rek_2' => $kelompok->id,
                                        'id_rek_3' => $jenis->id,
                                        'id_rek_4' => $objek->id,
                                        'id_rek_5' => $rincian->id,
                                        'id_rek_6' => $rekening->id,
                                        'parent_id' => $rekening->id,
                                        'year' => $dataTarget->year,
                                        'jenis' => $dataTarget->type,
                                        'sumber_dana_id' => $dataTarget->sumber_dana_id,
                                        'sumber_dana_fullcode' => $dataTarget->SumberDana->fullcode ?? null,
                                        'sumber_dana_name' => $dataTarget->SumberDana->name ?? null,
                                        'nama_paket' => $dataTarget->nama_paket,
                                        'pagu' => $dataTarget->pagu_sipd,
                                        'realisasi_anggaran' => (int)($dataRealisasi->anggaran ?? 0),
                                        'realisasi_year' => $dataRealisasi->year ?? $year,
                                        'realisasi_month' => $dataRealisasi->month ?? $month,
                                        'is_pagu_match' => $isPaguMatch,
                                        'temp_pagu' => $tempPagu,
                                        'is_detail' => $dataTarget->is_detail,
                                        'created_by' => $dataTarget->created_by,
                                        'created_by_name' => $dataTarget->CreatedBy->fullname ?? null,
                                        'updated_by' => $dataTarget->updated_by,
                                        'updated_by_name' => $dataTarget->UpdatedBy->fullname ?? null,
                                        'rincian_belanja' => [],
                                    ];
                                }

                                $sumRealisasiAnggaran = Realisasi::where('kode_rekening_id', $rekening->id)
                                    ->where('year', $year)
                                    ->where('month', $month)
                                    ->where('sub_kegiatan_id', $idSubKegiatan)
                                    ->where('status', 'verified')
                                    ->sum('anggaran');

                                $return['data'][] = [
                                    'editable' => false,
                                    'long' => true,
                                    'type' => 'rekening',
                                    'rek' => 6,
                                    'id' => $rekening->id,
                                    'parent_id' => $rincian->id,
                                    'uraian' => $rekening->name,
                                    'fullcode' => $rekening->fullcode,
                                    'pagu' => $arrDataTarget->sum('pagu_sipd'), // Tarik dari Data Rekening
                                    'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                                    'realisasi_year' => $year,
                                    'realisasi_month' => $month,
                                    'rincian_belanja' => [],
                                ];

                                foreach ($arrTargetKinerja as $targetKinerja) {
                                    $return['data'][] = $targetKinerja;
                                    $dbTargetKinerja = TargetKinerja::find($targetKinerja['id']);
                                    $arrRincianBelanja = [];
                                    $arrRincianBelanja = TargetKinerjaRincian::where('target_kinerja_id', $targetKinerja['id_target'])
                                        ->get();
                                    foreach ($arrRincianBelanja as $keyRincianBelanja => $rincianBelanja) {
                                        $realisasiRincian = RealisasiRincian::where('realisasi_id', $targetKinerja['id'])
                                            ->where('sub_kegiatan_id', $idSubKegiatan)
                                            ->where('kode_rekening_id', $dbTargetKinerja->kode_rekening_id)
                                            ->where('sumber_dana_id', $dbTargetKinerja->sumber_dana_id)
                                            // ->where('target_rincian_id', $rincianBelanja->id)
                                            ->first();
                                        $return['data'][count($return['data']) - 1]['rincian_belanja'][$keyRincianBelanja] = [
                                            'editable' => true,
                                            'long' => true,
                                            'type' => 'rincian-belanja',
                                            'id_rincian_target' => $rincianBelanja->id,
                                            'id' => $realisasiRincian->id,
                                            'id_rek_1' => $akun->id,
                                            'id_rek_2' => $kelompok->id,
                                            'id_rek_3' => $jenis->id,
                                            'id_rek_4' => $objek->id,
                                            'id_rek_5' => $rincian->id,
                                            'id_rek_6' => $rekening->id,
                                            'target_kinerja_id' => $rincianBelanja->target_kinerja_id,
                                            'title' => $rincianBelanja->title,
                                            'pagu' => (int)$rincianBelanja->pagu_sipd,
                                            'realisasi_anggaran' => (int)$realisasiRincian->anggaran,
                                            'realisasi_year' => $realisasiRincian->year,
                                            'realisasi_month' => $realisasiRincian->month,
                                            'keterangan_rincian' => [],
                                        ];

                                        $arrKeterangan = TargetKinerjaKeterangan::where('parent_id', $rincianBelanja->id)->get();
                                        foreach ($arrKeterangan as $targetKeterangan) {
                                            $realisasiKeterangan = RealisasiKeterangan::where('realisasi_id', $targetKinerja['id'])
                                                // ->where('target_keterangan_id', $targetKeterangan->id)
                                                ->where('parent_id', $realisasiRincian->id)
                                                ->where('year', $year)
                                                ->where('month', $month)
                                                ->first();
                                            $isRealisasiMatch = (int)$realisasiKeterangan->anggaran === (int)$targetKeterangan->pagu ? true : false;
                                            $return['data'][count($return['data']) - 1]['rincian_belanja'][$keyRincianBelanja]['keterangan_rincian'][] = [
                                                'editable' => true,
                                                'long' => false,
                                                'type' => 'keterangan-rincian',
                                                'id_target_keterangan' => $targetKeterangan->id,
                                                'id' => $realisasiKeterangan->id,
                                                'target_kinerja_id' => $targetKeterangan->target_kinerja_id,
                                                'title' => $targetKeterangan->title,

                                                'koefisien' => $targetKeterangan->koefisien,
                                                'satuan_id' => $targetKeterangan->satuan_id,
                                                'satuan_name' => $targetKeterangan->satuan_name,
                                                'harga_satuan' => $targetKeterangan->harga_satuan,
                                                'ppn' => $targetKeterangan->ppn,
                                                'pagu' => (int)$targetKeterangan->pagu,
                                                'is_realisasi_match' => $isRealisasiMatch,

                                                'realisasi_anggaran_keterangan' => (int)$realisasiKeterangan->anggaran,
                                                'target_persentase_kinerja' => $targetKeterangan->persentase_kinerja ?? 100,
                                                'persentase_kinerja' => $realisasiKeterangan->persentase_kinerja,
                                                'koefisien_realisasi' => $realisasiKeterangan->koefisien,

                                                'realisasi_year' => $realisasiKeterangan->year,
                                                'realisasi_month' => $realisasiKeterangan->month,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $return['keterangan'] = [
            'id' => null,
            'notes' => null,
            'link_map' => null,
            'faktor_penghambat' => null,
            'longitude' => null,
            'latitude' => null,
            'latitude' => null,
        ];
        $return['files'] = [];

        if ($RealisasiSubKegiatan) {
            $dataKeterangan = RealisasiSubKegiatanKeterangan::where('sub_kegiatan_id', $idSubKegiatan)
                ->where('year', $year)
                ->where('month', $month)
                ->where('instance_id', $RealisasiSubKegiatan->instance_id)
                ->first();
            if ($dataKeterangan) {
                $return['keterangan'] = [
                    'id' => $dataKeterangan->id,
                    'notes' => $dataKeterangan->notes,
                    'link_map' => $dataKeterangan->link_map,
                    'faktor_penghambat' => $dataKeterangan->faktor_penghambat,
                    'longitude' => $dataKeterangan->longitude,
                    'latitude' => $dataKeterangan->latitude,
                    'latitude' => $dataKeterangan->latitude,
                ];
            }
            $datas = RealisasiSubKegiatanFiles::where('parent_id', $RealisasiSubKegiatan->id)->get();
            foreach ($datas as $data) {
                $return['files'][] = [
                    'id' => $data->id,
                    'type' => $data->type,
                    'file' => asset($data->file),
                    'filename' => $data->filename,
                    'mime_type' => $data->mime_type,
                    'created_at' => $data->created_at,
                    'createdBy' => $data->CreatedBy->fullname ?? null,
                ];
            }
        }

        $datas = RealisasiSubKegiatanKontrak::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        foreach ($datas as $data) {
            $return['contracts'][] = [
                'id' => $data->id,
                'sub_kegiatan_id' => $data->sub_kegiatan_id,
                'no_kontrak' => $data->no_kontrak,
                'kd_tender' => $data->kd_tender,
                'data_spse' => json_decode($data->data_spse, true),
            ];
        }

        return $return;
    }
}
