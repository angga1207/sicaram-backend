<?php

namespace App\Http\Controllers\API;

use App\Models\Instance;
use App\Models\Ref\Bidang;
use App\Models\Ref\Satuan;
use App\Models\Ref\Urusan;
use App\Models\Caram\Renja;
use App\Models\Caram\RPJMD;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Renstra;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening1;
use App\Models\Ref\KodeRekening2;
use App\Models\Ref\KodeRekening3;
use App\Models\Ref\KodeRekening4;
use App\Models\Ref\KodeRekening5;
use App\Models\Ref\KodeRekening6;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RenjaKegiatan;
use App\Http\Controllers\Controller;
use App\Models\Caram\RenstraKegiatan;
use App\Models\Data\RealisasiRincian;
use App\Models\Ref\IndikatorKegiatan;
use App\Models\Caram\RenjaSubKegiatan;
use App\Models\Caram\RenstraSubKegiatan;
use App\Models\Ref\IndikatorSubKegiatan;
use Illuminate\Support\Facades\Validator;
use DragonCode\Support\Facades\Helpers\Arr;

class RealisasiController extends Controller
{
    use JsonReturner;

    function getKodeRekening(Request $request)
    {
        try {
            $datas = [];
            if ($request->level == 1 || !$request->level) {
                $datas = KodeRekening1::all();
            }
            if ($request->level == 2) {
                $datas = KodeRekening2::when($request->parent_id, function ($query) use ($request) {
                    return $query->where('ref_kode_rekening_1', $request->parent_id);
                })->get();
            }
            if ($request->level == 3) {
                $datas = KodeRekening3::when($request->parent_id, function ($query) use ($request) {
                    return $query->where('ref_kode_rekening_2', $request->parent_id);
                })->get();
            }
            if ($request->level == 4) {
                $datas = KodeRekening4::when($request->parent_id, function ($query) use ($request) {
                    return $query->where('ref_kode_rekening_3', $request->parent_id);
                })->get();
            }
            if ($request->level == 5) {
                $datas = KodeRekening5::when($request->parent_id, function ($query) use ($request) {
                    return $query->where('ref_kode_rekening_4', $request->parent_id);
                })->get();
            }
            if ($request->level == 6) {
                $datas = KodeRekening6::when($request->parent_id, function ($query) use ($request) {
                    return $query->where('ref_kode_rekening_5', $request->parent_id);
                })->get();
            }

            return $this->successResponse($datas, 'List of Kode Rekening');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function listInstance(Request $request)
    {
        try {
            $instances = Instance::search($request->search)
                ->with(['Programs', 'Kegiatans', 'SubKegiatans'])
                ->oldest('id')
                ->get();
            $datas = [];
            foreach ($instances as $instance) {
                $website = $instance->website;
                if ($website) {
                    if (str()->contains($website, 'http')) {
                        $website = $instance->website;
                    } else {
                        $website = 'http://' . $instance->website;
                    }
                }
                $facebook = $instance->facebook;
                if ($facebook) {
                    if (str()->contains($facebook, 'http')) {
                        $facebook = $instance->facebook;
                    } else {
                        $facebook = 'http://facebook.com/search/top/?q=' . $instance->facebook;
                    }
                }
                $instagram = $instance->instagram;
                if ($instagram) {
                    if (str()->contains($instagram, 'http')) {
                        $instagram = $instance->instagram;
                    } else {
                        $instagram = 'http://instagram.com/' . $instance->instagram;
                    }
                }
                $youtube = $instance->youtube;
                if ($youtube) {
                    if (str()->contains($youtube, 'http')) {
                        $youtube = $instance->youtube;
                    } else {
                        $youtube = 'http://youtube.com/results?search_query=' . $instance->youtube;
                    }
                }
                $datas[] = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'alias' => $instance->alias,
                    'code' => $instance->code,
                    'logo' => asset($instance->logo),
                    'website' => $website,
                    'facebook' => $facebook,
                    'instagram' => $instagram,
                    'youtube' => $youtube,
                    'programs' => $instance->Programs->count(),
                    'kegiatans' => $instance->Kegiatans->count(),
                    'sub_kegiatans' => $instance->SubKegiatans->count(),
                ];
            }
            return $this->successResponse($datas, 'List of instances');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function listProgramsSubKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'required|exists:instances,id',
        ], [], [
            'instance_id' => 'Instance ID',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        $instance = Instance::find($request->instance_id);
        if ($instance) {
            $programs = $instance->Programs->sortBy('fullcode');
            $datas = [];
            foreach ($programs as $program) {
                $kegiatans = $program->Kegiatans->sortBy('code_1')->sortBy('code_2');
                $kegiatanDatas = [];
                foreach ($kegiatans as $kegiatan) {
                    $subKegiatans = $kegiatan->SubKegiatans->sortBy('code');
                    $subKegiatanDatas = [];
                    foreach ($subKegiatans as $subKegiatan) {
                        $subKegiatanDatas[] = [
                            'id' => $subKegiatan->id,
                            'name' => $subKegiatan->name,
                            'fullcode' => $subKegiatan->fullcode,
                            'description' => $subKegiatan->description,
                            'status' => $subKegiatan->status,
                            'created_at' => $subKegiatan->created_at,
                            'updated_at' => $subKegiatan->updated_at,
                        ];
                    }
                    $kegiatanDatas[] = [
                        'id' => $kegiatan->id,
                        'name' => $kegiatan->name,
                        'fullcode' => $kegiatan->fullcode,
                        'description' => $kegiatan->description,
                        'status' => $kegiatan->status,
                        'created_at' => $kegiatan->created_at,
                        'updated_at' => $kegiatan->updated_at,
                        'sub_kegiatans' => $subKegiatanDatas,
                    ];
                }
                $datas[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'fullcode' => $program->fullcode,
                    'description' => $program->description,
                    'status' => $program->status,
                    'created_at' => $program->created_at,
                    'updated_at' => $program->updated_at,
                    'kegiatans' => $kegiatanDatas,
                ];
            }
            return $this->successResponse($datas, 'List of programs and sub kegiatans');
        } else {
            return $this->errorResponse('Instance not found');
        }
    }

    function getDataSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
        ], [], [
            'periode' => 'Periode',
            'year' => 'Tahun',
            'month' => 'Bulan',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        try {
            $data = [];
            $subKegiatan = SubKegiatan::find($id);
            $urusan = Urusan::find($subKegiatan->urusan_id);
            $bidang = Bidang::find($subKegiatan->bidang_id);
            $program = Program::find($subKegiatan->program_id);
            $kegiatan = Kegiatan::find($subKegiatan->kegiatan_id);

            $rpjmd = RPJMD::where('periode_id', $request->periode)
                ->where('instance_id', $subKegiatan->instance_id)
                ->where('program_id', $subKegiatan->program_id)
                ->latest('id') // latest id karena Ada Duplikat dengan Program ID yang sama
                ->first();

            $rpjmdIndikators = $rpjmd->Indicators->where('year', $request->year);
            $rpjmdIndikators = collect($rpjmdIndikators->values()->all());

            $rpjmdIndikators = $rpjmdIndikators->map(function ($item, $key) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'value' => $item->value,
                    'satuan_id' => $item->satuan_id,
                    'satuan_name' => Satuan::find($item->satuan_id)->name ?? null,
                    'year' => $item->year,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            $capsProgram = [
                'anggaran' => $rpjmd->Anggarans,
                'indikator' => $rpjmdIndikators,
            ];

            $renstra = Renstra::where('periode_id', $request->periode)
                ->where('rpjmd_id', $rpjmd->id)
                ->where('instance_id', $kegiatan->instance_id)
                ->where('program_id', $kegiatan->program_id)
                ->first();

            if (!$renstra) {
                // return response()->json([
                //     'status' => 'error renstra not found',
                //     'message' => 'Renstra untuk kegiatan ini tidak ditemukan',
                // ], 200);
                return $this->errorResponse('Renstra untuk kegiatan ini tidak ditemukan');
            }

            $renja = Renja::where('periode_id', $request->periode)
                ->where('rpjmd_id', $rpjmd->id)
                ->where('renstra_id', $renstra->id)
                ->where('instance_id', $kegiatan->instance_id)
                ->where('program_id', $kegiatan->program_id)
                ->first();

            $kegiatanRenstra = RenstraKegiatan::where('renstra_id', $renstra->id)
                ->where('program_id', $kegiatan->program_id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('year', $request->year)
                ->first();

            $kegiatanRenja = RenjaKegiatan::where('renja_id', $renja->id)
                ->where('renstra_id', $renstra->id)
                ->where('program_id', $kegiatan->program_id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('year', $request->year)
                ->first();

            $pivotKegiatanIndikator = DB::table('con_indikator_kinerja_kegiatan')
                ->where('instance_id', $kegiatan->instance_id)
                ->where('program_id', $kegiatan->program_id)
                ->where('kegiatan_id', $kegiatan->id)
                ->first();
            $kegiatanIndikator = IndikatorKegiatan::where('pivot_id', $pivotKegiatanIndikator->id)
                ->get();


            $resultKegiatanIndikator = [];
            foreach ($kegiatanIndikator as $key => $indi) {
                if ($kegiatanRenstra && $kegiatanRenstra->kinerja_json) {
                    $renstraValue = json_decode($kegiatanRenstra->kinerja_json, true)[$key] ?? null;
                }
                if ($kegiatanRenstra && $kegiatanRenstra->satuan_json) {
                    $renstraSatuan = json_decode($kegiatanRenstra->satuan_json, true)[$key] ?? null;
                }
                if ($kegiatanRenja && $kegiatanRenja->kinerja_json) {
                    $renjaValue = json_decode($kegiatanRenja->kinerja_json, true)[$key] ?? null;
                }
                if ($kegiatanRenja && $kegiatanRenja->satuan_json) {
                    $renjaSatuan = json_decode($kegiatanRenja->satuan_json, true)[$key] ?? null;
                }

                $resultKegiatanIndikator[] = [
                    'name' => $indi->name,
                    'renstra_value' => $renstraValue ?? null,
                    'renstra_satuan_id' => $renstraSatuan ?? null,
                    'renstra_satuan_name' => $renstraSatuan ? Satuan::find($renstraSatuan ?? null)->name : null,
                    'renja_value' => $renjaValue ?? null,
                    'renja_satuan_id' => $renjaSatuan ?? null,
                    'renja_satuan_name' => $renjaSatuan ? Satuan::find($renjaSatuan ?? null)->name : null,
                    'year' => $kegiatanRenstra->year,
                ];
            }

            $capsKegiatan = [
                'indikator' => $resultKegiatanIndikator,
                'anggaran' => [],
            ];

            $subKegiatanRenstra = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                ->where('program_id', $subKegiatan->program_id)
                ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->where('year', $request->year)
                ->first();

            $subKegiatanRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                ->where('renstra_id', $renstra->id)
                ->where('program_id', $subKegiatan->program_id)
                ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->where('year', $request->year)
                ->first();

            $pivotSubKegiatanIndikator = DB::table('con_indikator_kinerja_sub_kegiatan')
                ->where('instance_id', $subKegiatan->instance_id)
                ->where('program_id', $subKegiatan->program_id)
                ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->first();

            $subKegiatanIndikator = IndikatorSubKegiatan::where('pivot_id', $pivotSubKegiatanIndikator->id)
                ->get();

            $resultSubKegiatanIndikator = [];
            foreach ($subKegiatanIndikator as $key => $indi) {
                if ($subKegiatanRenstra && $subKegiatanRenstra->kinerja_json) {
                    $renstraSubValue = json_decode($subKegiatanRenstra->kinerja_json, true)[$key] ?? null;
                }
                if ($subKegiatanRenstra && $subKegiatanRenstra->satuan_json) {
                    $renstraSubSatuan = json_decode($subKegiatanRenstra->satuan_json, true)[$key] ?? null;
                }
                if ($subKegiatanRenja && $subKegiatanRenja->kinerja_json) {
                    $renjaSubValue = json_decode($subKegiatanRenja->kinerja_json, true)[$key] ?? null;
                }
                if ($subKegiatanRenja && $subKegiatanRenja->satuan_json) {
                    $renjaSubSatuan = json_decode($subKegiatanRenja->satuan_json, true)[$key] ?? null;
                }

                $resultSubKegiatanIndikator[] = [
                    'name' => $indi->name,
                    'renstra_value' => $renstraSubValue ?? null,
                    'renstra_satuan_id' => $renstraSubSatuan ?? null,
                    'renstra_satuan_name' => $renstraSubSatuan ? Satuan::find($renstraSubSatuan ?? null)->name : null,
                    'renja_value' => $renjaSubValue ?? null,
                    'renja_satuan_id' => $renjaSubSatuan ?? null,
                    'renja_satuan_name' => $renjaSubSatuan ? Satuan::find($renjaSubSatuan ?? null)->name : null,
                    'year' => $subKegiatanRenstra->year,
                ];
            }
            $capsSubKegiatan = [
                'indikator' => $resultSubKegiatanIndikator,
                'anggaran' => [],
            ];


            $data = [
                'urusan_id' => $urusan->id,
                'urusan_name' => $urusan->name,
                'urusan_fullcode' => $urusan->fullcode,

                'bidang_id' => $bidang->id,
                'bidang_name' => $bidang->name,
                'bidang_fullcode' => $bidang->fullcode,

                'program_id' => $program->id,
                'program_name' => $program->name,
                'program_fullcode' => $program->fullcode,
                'caps_program' => $capsProgram,

                'kegiatan_id' => $kegiatan->id,
                'kegiatan_name' => $kegiatan->name,
                'kegiatan_fullcode' => $kegiatan->fullcode,
                'caps_kegiatan' => $capsKegiatan,

                'sub_kegiatan_id' => $subKegiatan->id,
                'sub_kegiatan_name' => $subKegiatan->name,
                'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                'caps_sub_kegiatan' => $capsSubKegiatan,
            ];

            return $this->successResponse($data, 'Data Realisasi Kinerja Sub Kegiatan');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine() . ' -> ' . $e->getFile());
        }
    }

    function getDataRealisasi($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
        ], [], [
            'periode' => 'Periode',
            'year' => 'Tahun',
            'month' => 'Bulan',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        try {
            $datas = [];
            $subKegiatan = SubKegiatan::find($id);

            $level1 = Realisasi::where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->where('instance_id', $subKegiatan->instance_id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->where('level', 1)
                ->first();

            $arrLevel2 = Realisasi::where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->where('instance_id', $subKegiatan->instance_id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->where('level', 2)
                ->get();

            // $countRincian = Realisasi::where('periode_id', $request->periode)
            //     ->where('year', $request->year)
            //     ->where('month', $request->month)
            //     ->where('instance_id', $subKegiatan->instance_id)
            //     ->where('sub_kegiatan_id', $subKegiatan->id)
            //     ->where('level', 6)
            //     ->first();


            foreach ($arrLevel2 as $level2) {
                $datas[] = [
                    'id' => $level2->id,
                    'type' => 'summary',
                    'rek_code' => $level2->Rekening2->fullcode ?? null,
                    'rek_name' => $level2->Rekening2->name ?? null,
                    'total' => $level2->GetTotal($request->year, $request->month) ?? null,
                ];

                $arrLevel3 = Realisasi::where('periode_id', $request->periode)
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('instance_id', $subKegiatan->instance_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('level', 3)
                    ->where('ref_kode_rekening_1', $level2->ref_kode_rekening_1)
                    ->where('ref_kode_rekening_2', $level2->ref_kode_rekening_2)
                    ->get();

                foreach ($arrLevel3 as $level3) {
                    $datas[] = [
                        'id' => $level3->id,
                        'type' => 'summary',
                        'rek_code' => $level3->Rekening3->fullcode ?? null,
                        'rek_name' => $level3->Rekening3->name ?? null,
                        'total' => $level3->GetTotal($request->year, $request->month) ?? null,
                    ];

                    $arrLevel4 = Realisasi::where('periode_id', $request->periode)
                        ->where('year', $request->year)
                        ->where('month', $request->month)
                        ->where('instance_id', $subKegiatan->instance_id)
                        ->where('sub_kegiatan_id', $subKegiatan->id)
                        ->where('level', 4)
                        ->where('ref_kode_rekening_1', $level3->ref_kode_rekening_1)
                        ->where('ref_kode_rekening_2', $level3->ref_kode_rekening_2)
                        ->where('ref_kode_rekening_3', $level3->ref_kode_rekening_3)
                        ->get();

                    foreach ($arrLevel4 as $level4) {
                        $datas[] = [
                            'id' => $level4->id,
                            'type' => 'summary',
                            'rek_code' => $level4->Rekening4->fullcode ?? null,
                            'rek_name' => $level4->Rekening4->name ?? null,
                            'total' => $level4->GetTotal($request->year, $request->month) ?? null,
                        ];

                        $arrLevel5 = Realisasi::where('periode_id', $request->periode)
                            ->where('year', $request->year)
                            ->where('month', $request->month)
                            ->where('instance_id', $subKegiatan->instance_id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('level', 5)
                            ->where('ref_kode_rekening_1', $level4->ref_kode_rekening_1)
                            ->where('ref_kode_rekening_2', $level4->ref_kode_rekening_2)
                            ->where('ref_kode_rekening_3', $level4->ref_kode_rekening_3)
                            ->where('ref_kode_rekening_4', $level4->ref_kode_rekening_4)
                            ->get();

                        foreach ($arrLevel5 as $level5) {
                            $datas[] = [
                                'id' => $level5->id,
                                'type' => 'summary',
                                'rek_code' => $level5->Rekening5->fullcode ?? null,
                                'rek_name' => $level5->Rekening5->name ?? null,
                                'total' => $level5->GetTotal($request->year, $request->month) ?? null,
                            ];

                            $arrLevel6 = Realisasi::where('periode_id', $request->periode)
                                ->where('year', $request->year)
                                ->where('month', $request->month)
                                ->where('instance_id', $subKegiatan->instance_id)
                                ->where('sub_kegiatan_id', $subKegiatan->id)
                                ->where('level', 6)
                                ->where('ref_kode_rekening_1', $level5->ref_kode_rekening_1)
                                ->where('ref_kode_rekening_2', $level5->ref_kode_rekening_2)
                                ->where('ref_kode_rekening_3', $level5->ref_kode_rekening_3)
                                ->where('ref_kode_rekening_4', $level5->ref_kode_rekening_4)
                                ->where('ref_kode_rekening_5', $level5->ref_kode_rekening_5)
                                ->get();

                            foreach ($arrLevel6 as $level6) {
                                $datas[] = [
                                    'id' => $level6->id,
                                    'type' => 'summary',
                                    'rek_code' => $level6->Rekening6->fullcode ?? null,
                                    'rek_name' => $level6->Rekening6->name ?? null,
                                    'total' => $level6->GetTotal($request->year, $request->month) ?? null,
                                ];

                                $arrRincians = RealisasiRincian::where('data_realisasi_id', $level6->id)->get();
                                foreach ($arrRincians as $rincian) {
                                    if ($rincian->type == 'titles') {
                                        $datas[] = [
                                            'id' => $rincian->id,
                                            'type' => $rincian->type,
                                            'rek_code' => $level6->Rekening6->fullcode ?? null,
                                            'uraian' => $rincian->uraian,
                                            'harga' => $rincian->harga_satuan,
                                            'koefisien' => $rincian->koefisien,
                                            'satuan_id' => $rincian->satuan_id,
                                            'satuan_name' => Satuan::find($rincian->satuan_id)->name ?? null,
                                            'total' => RealisasiRincian::where('data_realisasi_id', $level6->id)->sum('total'),
                                        ];
                                    }
                                    if ($rincian->type == 'detail') {
                                        $datas[] = [
                                            'id' => $rincian->id,
                                            'type' => $rincian->type,
                                            'rek_code' => $level6->Rekening6->fullcode ?? null,
                                            'uraian' => $rincian->uraian,
                                            'harga' => $rincian->harga_satuan,
                                            'koefisien' => $rincian->koefisien,
                                            'satuan_id' => $rincian->satuan_id,
                                            'satuan_name' => Satuan::find($rincian->satuan_id)->name ?? null,
                                            'total' => $rincian->total,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $datas = collect($datas);
            $datas = $datas->sortBy('rek_code')
                ->values()
                ->all();

            return $this->successResponse($datas, 'Data Realisasi Kinerja Sub Kegiatan');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine());
        }
    }

    function saveDataSubKegiatan(Request $request)
    {
        $validate = Validator::make([
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'sub_kegiatan_id' => 'required|numeric|exists:ref_sub_kegiatan,id',
            'judul_uraian' => 'required|string',
            'rekenings' => 'required|array',
            'rekenings.*.level' => 'required|numeric',
            'rekenings.*.rek_id' => 'required|numeric',
            'uraians' => 'required|array',
            'uraians.*.harga' => 'required|numeric',
            'uraians.*.koefisien' => 'required|numeric',
            'uraians.*.satuan' => 'required|numeric',
            'uraians.*.uraian' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'year' => 'Tahun',
            'month' => 'Bulan',
            'sub_kegiatan_id' => 'Sub Kegiatan ID',
            'judul_uraian' => 'Judul Uraian',
            'rekenings' => 'Rekenings',
            'rekenings.*.level' => 'Level Rekening',
            'rekenings.*.rek_id' => 'Rekening ID',
            'uraians' => 'Uraians',
            'uraians.*.harga' => 'Harga',
            'uraians.*.koefisien' => 'Koefisien',
            'uraians.*.satuan' => 'Satuan',
            'uraians.*.uraian' => 'Uraian',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $subKegiatan = SubKegiatan::find($request->sub_kegiatan_id);

            // Level 1
            $level1 = Realisasi::where([
                'periode_id' => $request->periode,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 1,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 1,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level1->periode_id = $request->periode;
            $level1->instance_id = $subKegiatan->instance_id;
            $level1->sub_kegiatan_id = $subKegiatan->id;
            $level1->level = 1;
            $level1->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level1->status = 'active';
            $level1->created_by = auth()->user()->id;
            $level1->save();

            // Level 2
            $level2 = Realisasi::where([
                'periode_id' => $request->periode,
                'parent_id' => $level1->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 2,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'parent_id' => $level1->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 2,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level2->periode_id = $request->periode;
            $level2->parent_id = $level1->id;
            $level2->instance_id = $subKegiatan->instance_id;
            $level2->sub_kegiatan_id = $subKegiatan->id;
            $level2->level = 2;
            $level2->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level2->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
            $level2->status = 'active';
            $level2->created_by = auth()->user()->id;
            $level2->save();

            // Level 3
            $level3 = Realisasi::where([
                'periode_id' => $request->periode,
                'parent_id' => $level2->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 3,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'parent_id' => $level2->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 3,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level3->periode_id = $request->periode;
            $level3->parent_id = $level2->id;
            $level3->instance_id = $subKegiatan->instance_id;
            $level3->sub_kegiatan_id = $subKegiatan->id;
            $level3->level = 3;
            $level3->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level3->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
            $level3->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
            $level3->status = 'active';
            $level3->created_by = auth()->user()->id;
            $level3->save();

            // Level 4
            $level4 = Realisasi::where([
                'periode_id' => $request->periode,
                'parent_id' => $level3->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 4,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'parent_id' => $level3->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 4,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level4->periode_id = $request->periode;
            $level4->parent_id = $level3->id;
            $level4->instance_id = $subKegiatan->instance_id;
            $level4->sub_kegiatan_id = $subKegiatan->id;
            $level4->level = 4;
            $level4->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level4->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
            $level4->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
            $level4->ref_kode_rekening_4 = $request->rekenings[3]['rek_id'];
            $level4->status = 'active';
            $level4->created_by = auth()->user()->id;
            $level4->save();

            // Level 5
            $level5 = Realisasi::where([
                'periode_id' => $request->periode,
                'parent_id' => $level4->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 5,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                'ref_kode_rekening_5' => $request->rekenings[4]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'parent_id' => $level4->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 5,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                'ref_kode_rekening_5' => $request->rekenings[4]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level5->periode_id = $request->periode;
            $level5->parent_id = $level4->id;
            $level5->instance_id = $subKegiatan->instance_id;
            $level5->sub_kegiatan_id = $subKegiatan->id;
            $level5->level = 5;
            $level5->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level5->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
            $level5->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
            $level5->ref_kode_rekening_4 = $request->rekenings[3]['rek_id'];
            $level5->ref_kode_rekening_5 = $request->rekenings[4]['rek_id'];
            $level5->status = 'active';
            $level5->created_by = auth()->user()->id;
            $level5->save();

            // Level 6
            $level6 = Realisasi::where([
                'periode_id' => $request->periode,
                'parent_id' => $level5->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 6,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                'ref_kode_rekening_5' => $request->rekenings[4]['rek_id'],
                'ref_kode_rekening_6' => $request->rekenings[5]['rek_id'],
            ])->firstOrNew([
                'periode_id' => $request->periode,
                'parent_id' => $level5->id,
                'year' => $request->year,
                'month' => $request->month,
                'instance_id' => $subKegiatan->instance_id,
                'sub_kegiatan_id' => $subKegiatan->id,
                'level' => 6,
                'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                'ref_kode_rekening_5' => $request->rekenings[4]['rek_id'],
                'ref_kode_rekening_6' => $request->rekenings[5]['rek_id'],
                'status' => 'active',
                'created_by' => auth()->user()->id,
            ]);
            $level6->periode_id = $request->periode;
            $level6->parent_id = $level5->id;
            $level6->instance_id = $subKegiatan->instance_id;
            $level6->sub_kegiatan_id = $subKegiatan->id;
            $level6->level = 6;
            $level6->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
            $level6->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
            $level6->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
            $level6->ref_kode_rekening_4 = $request->rekenings[3]['rek_id'];
            $level6->ref_kode_rekening_5 = $request->rekenings[4]['rek_id'];
            $level6->ref_kode_rekening_6 = $request->rekenings[5]['rek_id'];
            $level6->status = 'active';
            $level6->created_by = auth()->user()->id;
            $level6->save();

            if ($request->judul_uraian) {
                // $judulRincian = new RealisasiRincian();
                $judulRincian = RealisasiRincian::where([
                    'data_realisasi_id' => $level6->id,
                    'type' => 'title',
                ])->firstOrNew([
                    'data_realisasi_id' => $level6->id,
                    'type' => 'title',
                    'ref_kode_rekening_1' => $request->rekenings[0]['rek_id'],
                    'ref_kode_rekening_2' => $request->rekenings[1]['rek_id'],
                    'ref_kode_rekening_3' => $request->rekenings[2]['rek_id'],
                    'ref_kode_rekening_4' => $request->rekenings[3]['rek_id'],
                    'ref_kode_rekening_5' => $request->rekenings[4]['rek_id'],
                    'ref_kode_rekening_6' => $request->rekenings[5]['rek_id'],
                    'status' => 'active',
                    'created_by' => auth()->user()->id,
                ]);
                $judulRincian->data_realisasi_id = $level6->id;
                $judulRincian->type = 'title';
                $judulRincian->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
                $judulRincian->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
                $judulRincian->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
                $judulRincian->ref_kode_rekening_4 = $request->rekenings[3]['rek_id'];
                $judulRincian->ref_kode_rekening_5 = $request->rekenings[4]['rek_id'];
                $judulRincian->ref_kode_rekening_6 = $request->rekenings[5]['rek_id'];
                $judulRincian->uraian = $request->judul_uraian;
                $judulRincian->total = 0;
                $judulRincian->status = 'active';
                $judulRincian->created_by = auth()->user()->id;
                $judulRincian->save();
            }

            if ($request->uraians) {
                foreach ($request->uraians as $uraian) {
                    $rincian = new RealisasiRincian();
                    $rincian->data_realisasi_id = $level6->id;
                    $rincian->type = 'detail';
                    $rincian->ref_kode_rekening_1 = $request->rekenings[0]['rek_id'];
                    $rincian->ref_kode_rekening_2 = $request->rekenings[1]['rek_id'];
                    $rincian->ref_kode_rekening_3 = $request->rekenings[2]['rek_id'];
                    $rincian->ref_kode_rekening_4 = $request->rekenings[3]['rek_id'];
                    $rincian->ref_kode_rekening_5 = $request->rekenings[4]['rek_id'];
                    $rincian->ref_kode_rekening_6 = $request->rekenings[5]['rek_id'];
                    $rincian->uraian = $uraian['uraian'] ?? null;
                    $rincian->koefisien = $uraian['koefisien'] ?? 0;
                    $rincian->satuan_id = $uraian['satuan'] ?? null;
                    $rincian->harga_satuan = $uraian['harga'] ?? 0;
                    $rincian->ppn = $uraian['ppn'] ?? 0;
                    $rincian->pph = $uraian['pph'] ?? 0;
                    $rincian->pph_final = $uraian['pph_final'] ?? 0;
                    $rincian->total = ($uraian['koefisien'] * $uraian['harga']) ?? 0;
                    $rincian->status = 'active';
                    $rincian->created_by = auth()->user()->id;
                    $rincian->save();
                }
            }

            DB::commit();
            return $this->successResponse(null, 'Data Realisasi Kinerja Sub Kegiatan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine());
        }
    }

    function detailDataSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
        ], [], [
            'periode' => 'Periode',
            'year' => 'Tahun',
            'month' => 'Bulan',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        try {
            $data = RealisasiRincian::find($id);
            if ($data->Parent->periode_id != $request->periode) {
                return $this->errorResponse('Data tidak ditemukan');
            }

            if ($data->Parent->year != $request->year) {
                return $this->errorResponse('Data tidak ditemukan');
            }

            if ($data->Parent->month != $request->month) {
                return $this->errorResponse('Data tidak ditemukan');
            }

            $data = [
                'id' => $data->id,
                'type' => $data->type,
                'uraian' => $data->uraian,
                'koefisien' => $data->koefisien,
                'satuan' => $data->satuan_id,
                'satuan_name' => Satuan::find($data->satuan_id)->name ?? null,
                'harga' => $data->harga_satuan,
                'ppn' => $data->ppn,
                'pph' => $data->pph,
                'pph_final' => $data->pph_final,
                'total' => $data->total,
                'rek_id_1' => $data->ref_kode_rekening_1,
                'kode_rek_1' => KodeRekening1::find($data->ref_kode_rekening_1)->fullcode ?? null,
                'kode_rek_1_uraian' => KodeRekening1::find($data->ref_kode_rekening_1)->name ?? null,
                'rek_id_2' => $data->ref_kode_rekening_2,
                'kode_rek_2' => KodeRekening2::find($data->ref_kode_rekening_2)->fullcode ?? null,
                'kode_rek_2_uraian' => KodeRekening2::find($data->ref_kode_rekening_2)->name ?? null,
                'rek_id_3' => $data->ref_kode_rekening_3,
                'kode_rek_3' => KodeRekening3::find($data->ref_kode_rekening_3)->fullcode ?? null,
                'kode_rek_3_uraian' => KodeRekening3::find($data->ref_kode_rekening_3)->name ?? null,
                'rek_id_4' => $data->ref_kode_rekening_4,
                'kode_rek_4' => KodeRekening4::find($data->ref_kode_rekening_4)->fullcode ?? null,
                'kode_rek_4_uraian' => KodeRekening4::find($data->ref_kode_rekening_4)->name ?? null,
                'rek_id_5' => $data->ref_kode_rekening_5,
                'kode_rek_5' => KodeRekening5::find($data->ref_kode_rekening_5)->fullcode ?? null,
                'kode_rek_5_uraian' => KodeRekening5::find($data->ref_kode_rekening_5)->name ?? null,
                'rek_id_6' => $data->ref_kode_rekening_6,
                'kode_rek_6' => KodeRekening6::find($data->ref_kode_rekening_6)->fullcode ?? null,
                'kode_rek_6_uraian' => KodeRekening6::find($data->ref_kode_rekening_6)->name ?? null,
                'judul_uraian' => RealisasiRincian::where('data_realisasi_id', $data->data_realisasi_id)->where('type', 'title')->first()->uraian ?? null,
            ];

            return $this->successResponse($data, 'Data Realisasi Berhasil disimpan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine());
        }
    }

    function updateDataSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'koefisien' => 'required|numeric',
            'satuan' => 'required|numeric',
            'harga' => 'required|numeric',
            'type' => 'required|string',
        ], [], [
            'uraian' => 'Uraian',
            'koefisien' => 'Koefisien',
            'satuan' => 'Satuan',
            'harga' => 'Harga',
            'type' => 'Type',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = RealisasiRincian::find($id);
            $data->uraian = $request->uraian;
            $data->koefisien = $request->koefisien;
            $data->satuan_id = $request->satuan;
            $data->harga_satuan = $request->harga;
            $data->total = $request->koefisien * $request->harga;
            $data->updated_by = auth()->user()->id;
            $data->save();

            DB::commit();
            return $this->successResponse(null, 'Data Realisasi Berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine());
        }
    }

    function deleteDataSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
        ], [], [
            'periode' => 'Periode',
            'year' => 'Tahun',
            'month' => 'Bulan',
        ]);

        if ($validate->fails()) {
            return $this->errorResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = RealisasiRincian::find($id);
            // return ($data->Parent->Rincians);
            if ($data->Parent->periode_id != $request->periode) {
                return $this->errorResponse('Data tidak dapat dihapus');
            }

            if ($data->Parent->year != $request->year) {
                return $this->errorResponse('Data tidak dapat dihapus');
            }

            if ($data->Parent->month != $request->month) {
                return $this->errorResponse('Data tidak dapat dihapus');
            }
            if ($data->type == 'detail') {
                $data->delete();
            } else {
                return $this->errorResponse('Data tidak dapat dihapus');
            }

            $similarCount = RealisasiRincian::where('data_realisasi_id', $data->data_realisasi_id)
                ->where('type', 'detail')
                ->count();

            if ($similarCount == 0) {
                RealisasiRincian::where('data_realisasi_id', $data->data_realisasi_id)
                    ->where('type', 'title')
                    ->delete();

                $parent = $data->Parent;
                $parent->delete();

                $parent2 = $parent->Parent;
                if ($parent2->Childs->count() == 0) {
                    $parent2->delete();
                }
                if ($parent2->Parent) {
                    $parent3 = $parent2->Parent;
                    if ($parent3->Childs->count() == 0) {
                        $parent3->delete();
                    }

                    if ($parent3->Parent) {
                        $parent4 = $parent3->Parent;
                        if ($parent4->Childs->count() == 0) {
                            $parent4->delete();
                        }

                        if ($parent4->Parent) {
                            $parent5 = $parent4->Parent;
                            if ($parent5->Childs->count() == 0) {
                                $parent5->delete();
                            }

                            if ($parent5->Parent) {
                                $parent6 = $parent5->Parent;
                                if ($parent6->Childs->count() == 0) {
                                    $parent6->delete();
                                }
                            }
                        }
                    }
                }
            }


            DB::commit();
            return $this->successResponse(null, 'Data Realisasi Berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' -> ' . $e->getLine());
        }
    }
}
