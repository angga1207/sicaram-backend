<?php

namespace App\Http\Controllers\API;

use App\Models\Instance;
use App\Models\Caram\Apbd;
use App\Models\Ref\Satuan;
use App\Models\Caram\Renja;
use App\Models\Caram\RPJMD;
use App\Models\Ref\Periode;
use App\Models\Ref\Program;
use App\Models\Caram\Tujuan;
use App\Models\Ref\Kegiatan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Renstra;
use App\Models\Caram\Sasaran;
use App\Models\Ref\SubKegiatan;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Ref\TagSumberDana;
use App\Models\Caram\ApbdKegiatan;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RenjaKegiatan;
use App\Http\Controllers\Controller;
use App\Models\Caram\ApbdSubKegiatan;
use App\Models\Caram\RenstraKegiatan;
use App\Models\Ref\IndikatorKegiatan;
use App\Models\Caram\RenjaSubKegiatan;
use App\Models\Data\TaggingSumberDana;
use App\Models\Caram\RenstraSubKegiatan;
use App\Models\Data\Realisasi;
use App\Models\Ref\IndikatorSubKegiatan;
use App\Models\Data\RealisasiSubKegiatan;
use App\Models\Data\TargetKinerja;
use App\Models\Ref\KodeRekening;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    use JsonReturner;

    function getRefs(Request $request)
    {
        try {
            $return = [];

            // periodes
            $periodes = Periode::select('id', 'name', 'start_date', 'end_date', 'status')
                ->get();
            $return['periodes'] = $periodes;

            // periode range
            $periodeRange = [];
            $periode = DB::table('ref_periode')
                ->where('id', $request->periode_id)
                ->first();

            // range years
            $startYear = date('Y', strtotime($periode->start_date));
            $endYear = date('Y', strtotime($periode->end_date));
            $years = range($startYear, $endYear);

            // range months
            $startMonth = date('m', strtotime($periode->start_date));
            $endMonth = date('m', strtotime($periode->end_date));
            $months = range($startMonth, $endMonth);

            // range days
            $startDay = date('d', strtotime($periode->start_date));
            $endDay = date('d', strtotime($periode->end_date));
            $days = range($startDay, $endDay);

            $periodeRange = [
                'years' => $years,
                'months' => $months,
                'days' => $days,
            ];
            $return['periodeRange'] = $periodeRange;

            // instances
            $user = auth()->user();
            $instanceIds = [];
            if ($user->role_id == 6) {
                $Ids = DB::table('pivot_user_verificator_instances')
                    ->where('user_id', $user->id)
                    ->get();
                foreach ($Ids as $id) {
                    $instanceIds[] = $id->instance_id;
                }
            }

            $instances = Instance::search($request->search)
                ->when($user->role_id == 6, function ($query) use ($instanceIds) {
                    return $query->whereIn('id', $instanceIds);
                })
                ->orderBy('name')
                ->get();
            $datas = [];
            foreach ($instances as $instance) {
                $datas[] = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'alias' => $instance->alias,
                    'code' => $instance->code,
                ];
            }
            $return['instances'] = $datas;

            // ref tagging sumber dana
            $tagsSumberDana = TagSumberDana::where('status', 'active')
                ->select('id', 'name')
                ->get();
            $return['tagsSumberDana'] = $tagsSumberDana;

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function reportRealisasiHead(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|integer|exists:instances,id',
            'year' => 'required',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'year' => 'Tahun',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $return = [];
            $instance = Instance::find($request->instance);
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 200);
            }
            $return['instance'] = [
                'id' => $instance->id,
                'name' => $instance->name,
                'alias' => $instance->alias,
                'code' => $instance->code,
                'logo' => asset($instance->logo),
            ];

            $arrTujuan = Tujuan::where('instance_id', $instance->id)
                ->get();
            foreach ($arrTujuan as $tujuan) {
                $indikatorTujuan = [];
                foreach ($tujuan->RefIndikatorTujuan as $indTujuan) {
                    $indikatorTujuan[] = [
                        'name' => $indTujuan->name,
                        'rumus' => DB::table('pivot_master_tujuan_to_ref_tujuan')
                            ->where('tujuan_id', $tujuan->id)
                            ->where('ref_id', $indTujuan->id)
                            ->first()
                            ->rumus ?? '',
                    ];
                }

                $arrSasaran = Sasaran::where('tujuan_id', $tujuan->id)
                    ->get();
                $returnSasaran = [];
                foreach ($arrSasaran as $sasaran) {
                    $indikatorSasaran = [];
                    foreach ($sasaran->RefIndikatorSasaran as $indSasaran) {
                        $indikatorSasaran[] = [
                            'name' => $indSasaran->name,
                            'rumus' => DB::table('pivot_master_sasaran_to_ref_sasaran')
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->id)
                                ->first()
                                ->rumus ?? '',
                        ];
                    }

                    $returnSasaran[] = [
                        'sasaran_name' => $sasaran->RefSasaran->name ?? '',
                        'indikator_sasaran' => $indikatorSasaran ?? [],
                    ];
                }

                $return['tujuan'][] = [
                    'tujuan_name' => $tujuan->RefTujuan->name ?? '',
                    'indikator_tujuan' => $indikatorTujuan ?? [],
                    // 'rumus' => $rumus,
                    'sasaran' => $returnSasaran,
                ];
            }

            return $this->successResponse($return);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function reportRealisasi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required',
            'triwulan' => 'required',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'periode' => 'Periode',
            'year' => 'Tahun',
            'triwulan' => 'Triwulan',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $instance = Instance::find($request->instance);
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 200);
            }
            $arrProgram = Program::where('instance_id', $instance->id)
                ->where('periode_id', $request->periode)
                ->orderBy('fullcode')
                ->get();

            $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            if ($request->triwulan == 1) {
                $arrMonths = [1, 2, 3];
            } elseif ($request->triwulan == 2) {
                $arrMonths = [4, 5, 6];
            } elseif ($request->triwulan == 3) {
                $arrMonths = [7, 8, 9];
            } elseif ($request->triwulan == 4) {
                $arrMonths = [10, 11, 12];
            }

            $datas = [];
            foreach ($arrProgram as $program) {
                $rpjmd = RPJMD::where('program_id', $program->id)
                    ->where('periode_id', $request->periode)
                    ->where('instance_id', $request->instance)
                    ->first();
                $IndikatorKinerjaProgram = $rpjmd ? $rpjmd->Indicators->where('year', $request->year)->pluck('name') : [];
                $RealisasiProgram = [];
                $TargetKinerjaProgram = [];
                if ($rpjmd) {
                    $arrIndicators = collect($rpjmd->Indicators->where('year', $request->year))->values();
                    foreach ($arrIndicators as $keyIndi => $value) {
                        $TargetKinerjaProgram[] = [
                            'name' => $value->name,
                            'targetRpjmd' => $value->value,
                            'satuanRpjmd' => $value->Satuan->name ?? '',
                        ];

                        $RealisasiProgram[$keyIndi]['name'] = $value->name;
                        foreach ($arrMonths as $month) {
                            $dataRealisasiProgram = RealisasiSubKegiatan::where('program_id', $program->id)
                                ->where('instance_id', $request->instance)
                                ->where('year', $request->year)
                                ->where('month', $month)
                                ->get();
                            $RealisasiProgram[$keyIndi][$month] = [
                                'kinerja' => $dataRealisasiProgram->avg('persentase_realisasi_kinerja'),
                                'kinerjaSatuan' => '%',
                                'keuangan' => $dataRealisasiProgram->sum('realisasi_anggaran')
                            ];
                        }
                    }
                }
                $AnggaranRenstraProgram = Renstra::where('program_id', $program->id)
                    ->where('instance_id', $request->instance)
                    ->where('periode_id', $program->periode_id)
                    ->sum('total_anggaran');
                $AnggaranRenjaProgram = Renja::where('program_id', $program->id)
                    ->where('instance_id', $request->instance)
                    ->where('periode_id', $program->periode_id)
                    ->sum('total_anggaran');
                $AnggaranApbdProgram = Apbd::where('program_id', $program->id)
                    ->where('instance_id', $request->instance)
                    ->where('periode_id', $program->periode_id)
                    ->sum('total_anggaran');

                // Kegiatan Start
                $arrKegiatan = $program->Kegiatans->where('instance_id', $request->instance)->sortBy('fullcode');
                $returnKegiatan = [];
                foreach ($arrKegiatan as $kegiatan) {
                    $pluckIds = DB::table('con_indikator_kinerja_kegiatan')
                        ->where('instance_id', $instance->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->get()
                        ->pluck('id');
                    $indikatorKinerjaKegiatan = IndikatorKegiatan::whereIn('pivot_id', $pluckIds->toArray())
                        ->get();
                    $TargetKinerjaKegiatan = [];
                    $RealisasiKegiatan = [];
                    foreach ($indikatorKinerjaKegiatan as $keyIndKgt => $indKgt) {
                        $renstraKegiatan = RenstraKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->first();
                        $kinerjaRenstraKegiatanJson = json_decode($renstraKegiatan->kinerja_json, true);
                        $satuanRenstraKegiatanJson = json_decode($renstraKegiatan->satuan_json, true);
                        if ($satuanRenstraKegiatanJson) {
                            $satuanRenstraKegiatanName = $satuanRenstraKegiatanJson[$keyIndKgt] ?? null;
                            if ($satuanRenstraKegiatanName) {
                                $satuanRenstraKegiatanName = Satuan::find($satuanRenstraKegiatanJson[$keyIndKgt] ?? 0)->name ?? '';
                            }
                        }

                        $renjaKegiatan = RenjaKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->first();
                        $kinerjaRenjaKegiatanJson = json_decode($renjaKegiatan->kinerja_json, true);
                        $satuanRenjaKegiatanJson = json_decode($renjaKegiatan->satuan_json, true);
                        if ($satuanRenjaKegiatanJson) {
                            $satuanRenjaKegiatanName = $satuanRenjaKegiatanJson[$keyIndKgt] ?? null;
                            if ($satuanRenjaKegiatanName) {
                                $satuanRenjaKegiatanName = Satuan::find($satuanRenjaKegiatanJson[$keyIndKgt] ?? 0)->name ?? '';
                            }
                        }

                        $TargetKinerjaKegiatan[] = [
                            'name' => $indKgt->name,
                            'targetRenstra' => $kinerjaRenstraKegiatanJson[$keyIndKgt] ?? 0,
                            'satuanRenstra' => $satuanRenstraKegiatanName ?? '',
                            'targetRenja' => $kinerjaRenjaKegiatanJson[$keyIndKgt] ?? 0,
                            'satuanRenja' => $satuanRenjaKegiatanName ?? '',
                        ];

                        $RealisasiKegiatan[$keyIndKgt]['name'] = $indKgt->name;
                        foreach ($arrMonths as $month) {
                            $dataRealisasiKegiatan = RealisasiSubKegiatan::where('program_id', $program->id)
                                ->where('kegiatan_id', $kegiatan->id)
                                ->where('instance_id', $request->instance)
                                ->where('year', $request->year)
                                ->where('month', $month)
                                ->get();
                            $RealisasiKegiatan[$keyIndKgt][$month] = [
                                'kinerja' => $dataRealisasiKegiatan->avg('persentase_realisasi_kinerja'),
                                'kinerjaSatuan' => '%',
                                'keuangan' => $dataRealisasiKegiatan->sum('realisasi_anggaran')
                            ];
                        }
                    }
                    $anggaranRenstraKegiatan = RenstraKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->sum('total_anggaran');
                    $anggaranRenstraKegiatan = RenjaKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->sum('total_anggaran');
                    $anggaranApbdKegiatan = ApbdKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->sum('total_anggaran');


                    // Sub Kegiatan Start
                    $arrSubKegiatan = $kegiatan->SubKegiatans->where('instance_id', $request->instance)->sortBy('fullcode');
                    $returnSubKegiatan = [];
                    foreach ($arrSubKegiatan as $subKegiatan) {
                        $pluckIds = DB::table('con_indikator_kinerja_sub_kegiatan')
                            ->where('instance_id', $instance->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->get()
                            ->pluck('id');
                        $indikatorKinerjaSubKegiatan = IndikatorSubKegiatan::whereIn('pivot_id', $pluckIds->toArray())
                            ->get();
                        $TargetKinerjaSubKegiatan = [];
                        $RealisasiSubKegiatan = [];
                        foreach ($indikatorKinerjaSubKegiatan as $keyIndSubKgt => $indSubKgt) {
                            $renstraSubKegiatan = RenstraSubKegiatan::where('program_id', $program->id)
                                ->where('kegiatan_id', $kegiatan->id)
                                ->where('sub_kegiatan_id', $subKegiatan->id)
                                ->first();
                            $kinerjaRenstraSubKegiatanJson = json_decode($renstraSubKegiatan->kinerja_json, true);
                            $satuanRenstraSubKegiatanJson = json_decode($renstraSubKegiatan->satuan_json, true);
                            if ($satuanRenstraSubKegiatanJson) {
                                $satuanRenstraSubKegiatanName = $satuanRenstraSubKegiatanJson[$keyIndSubKgt] ?? null;
                                if ($satuanRenstraSubKegiatanName) {
                                    $satuanRenstraSubKegiatanName = Satuan::find($satuanRenstraSubKegiatanJson[$keyIndSubKgt] ?? 0);
                                    $satuanRenstraSubKegiatanName = $satuanRenstraSubKegiatanName ? $satuanRenstraSubKegiatanName->name : '';
                                }
                            }

                            $renjaSubKegiatan = RenjaSubKegiatan::where('program_id', $program->id)
                                ->where('kegiatan_id', $kegiatan->id)
                                ->where('sub_kegiatan_id', $subKegiatan->id)
                                ->first();
                            $kinerjaRenjaSubKegiatanJson = json_decode($renjaSubKegiatan->kinerja_json, true);
                            $satuanRenjaSubKegiatanJson = json_decode($renjaSubKegiatan->satuan_json, true);
                            if ($satuanRenjaSubKegiatanJson) {
                                $satuanRenjaSubKegiatanName = $satuanRenjaSubKegiatanJson[$keyIndSubKgt] ?? null;
                                if ($satuanRenjaSubKegiatanName) {
                                    $satuanRenjaSubKegiatanName = Satuan::find($satuanRenjaSubKegiatanJson[$keyIndSubKgt] ?? 0);
                                    $satuanRenjaSubKegiatanName = $satuanRenjaSubKegiatanName ? $satuanRenjaSubKegiatanName->name : '';
                                }
                            }

                            $TargetKinerjaSubKegiatan[] = [
                                'name' => $indSubKgt->name,
                                'targetRenstra' => $kinerjaRenstraSubKegiatanJson[$keyIndSubKgt] ?? 0,
                                'satuanRenstra' => $satuanRenstraSubKegiatanName ?? '',
                                'targetRenja' => $kinerjaRenjaSubKegiatanJson[$keyIndSubKgt] ?? 0,
                                'satuanRenja' => $satuanRenjaSubKegiatanName ?? '',
                            ];

                            $RealisasiSubKegiatan[$keyIndSubKgt]['name'] = $indSubKgt->name;
                            foreach ($arrMonths as $month) {
                                $dataRealisasiSubKegiatan = RealisasiSubKegiatan::where('program_id', $program->id)
                                    ->where('kegiatan_id', $kegiatan->id)
                                    ->where('sub_kegiatan_id', $subKegiatan->id)
                                    ->where('instance_id', $request->instance)
                                    ->where('year', $request->year)
                                    ->where('month', $month)
                                    ->first();
                                if ($dataRealisasiSubKegiatan) {
                                    $CollectKinerjaSubs = json_decode($dataRealisasiSubKegiatan->realisasi_kinerja_json, true);
                                    $CollectKinerjaSubs = collect($CollectKinerjaSubs)->where('type', 'kinerja');
                                    $KinerjaSubs = $CollectKinerjaSubs->pluck('realisasi');
                                    $KinerjaSubsSatuan = $CollectKinerjaSubs->pluck('satuan_name');
                                }
                                $RealisasiSubKegiatan[$keyIndSubKgt][$month] = [
                                    'kinerja' => $KinerjaSubs[$keyIndSubKgt] ?? 0,
                                    'kinerjaSatuan' => $KinerjaSubsSatuan[$keyIndSubKgt] ?? '',
                                    'keuangan' => $dataRealisasiSubKegiatan->realisasi_anggaran ?? 0,
                                ];
                            }
                        }
                        $anggaranRenstraSubKegiatan = RenstraSubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->sum('total_anggaran');
                        $anggaranRenstraSubKegiatan = RenjaSubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->sum('total_anggaran');
                        $anggaranApbdSubKegiatan = ApbdSubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->sum('total_anggaran');

                        $arrTagsSumberDana = TaggingSumberDana::where('sub_kegiatan_id', $subKegiatan->id)
                            // ->where('status', 'active')
                            ->get();
                        $returnTagsSumberDana = [];
                        foreach ($arrTagsSumberDana as $tagSumberDana) {
                            $returnTagsSumberDana[] = [
                                'name' => $tagSumberDana->RefTag->name,
                                'nominal' => $tagSumberDana->nominal,
                                'notes' => $tagSumberDana->notes,
                            ];
                        }

                        $returnSubKegiatan[] = [
                            'type' => 'subKegiatan',
                            'id' => $subKegiatan->id,
                            'fullcode' => $subKegiatan->fullcode,
                            'name' => $subKegiatan->name,
                            'indikatorKinerja' => $indikatorKinerjaSubKegiatan->pluck('name'),
                            'targetKinerja' => $TargetKinerjaSubKegiatan,
                            'anggaranRenstra' => $anggaranRenstraSubKegiatan,
                            'anggaranRenja' => $anggaranRenstraSubKegiatan,
                            'anggaranApbd' => $anggaranApbdSubKegiatan,
                            'realisasi' => $RealisasiSubKegiatan,
                            'tagsSumberDana' => $returnTagsSumberDana,
                        ];
                    }

                    $returnKegiatan[] = [
                        'type' => 'kegiatan',
                        'id' => $kegiatan->id,
                        'fullcode' => $kegiatan->fullcode,
                        'name' => $kegiatan->name,
                        'indikatorKinerja' => $indikatorKinerjaKegiatan->pluck('name'),
                        'targetKinerja' => $TargetKinerjaKegiatan,
                        'anggaranRenstra' => $anggaranRenstraKegiatan,
                        'anggaranRenja' => $anggaranRenstraKegiatan,
                        'anggaranApbd' => $anggaranApbdKegiatan,
                        'realisasi' => $RealisasiKegiatan,
                        'subKegiatan' => $returnSubKegiatan,
                    ];
                }

                $datas[] = [
                    'type' => 'program',
                    'id' => $program->id,
                    'fullcode' => $program->fullcode,
                    'name' => $program->name,
                    'indikatorKinerja' => $IndikatorKinerjaProgram,
                    'targetKinerja' => $TargetKinerjaProgram,
                    'anggaranRenstra' => $AnggaranRenstraProgram,
                    'anggaranRenja' => $AnggaranRenjaProgram,
                    'anggaranApbd' => $AnggaranApbdProgram,
                    'realisasi' => $RealisasiProgram,
                    'kegiatan' => $returnKegiatan,
                ];
            }

            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function reportTagSumberDana(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'instance' => 'required',
            'year' => 'required',
            'tag' => 'required',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'year' => 'Tahun',
            'tag' => 'Tag Sumber Dana',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        DB::beginTransaction();
        try {
            $tag = TagSumberDana::find($request->tag);
            if (!$tag) {
                return $this->errorResponse('Tag Sumber Dana tidak ditemukan', 200);
            }

            $return = [];
            if ($request->instance != 0) {
                $instance = Instance::find($request->instance);
                if (!$instance) {
                    return $this->errorResponse('Perangkat Daerah tidak ditemukan', 200);
                }
                $arrSubKegiatans = SubKegiatan::where('instance_id', $instance->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();
            } elseif ($request->instance == 0) {
                $instance = Instance::find(1);
                $arrSubKegiatans = SubKegiatan::get()
                    ->pluck('id')
                    ->toArray();
            }

            $datas = TaggingSumberDana::whereIn('ref_tag_id', [$tag->id])
                ->whereIn('sub_kegiatan_id', $arrSubKegiatans)
                ->where('year', $request->year)
                ->get();

            $pluckSubKegiatanIds = $datas->groupBy('sub_kegiatan_id');
            $pluckSubKegiatanIds = collect($pluckSubKegiatanIds)->values();
            $pluckSubKegiatanIds = $pluckSubKegiatanIds->pluck('0.sub_kegiatan_id');

            $plckKegiatanIds = SubKegiatan::whereIn('id', $pluckSubKegiatanIds->toArray())
                ->get()
                ->pluck('kegiatan_id')
                ->toArray();

            $pluckProgramIds = Kegiatan::whereIn('id', $plckKegiatanIds)
                ->get()
                ->pluck('program_id')
                ->toArray();

            $return['summary'] = [
                'tag' => $tag->name,
                'sub_kegiatan_ids' => $pluckSubKegiatanIds,
                'sub_kegiatan_count' => $pluckSubKegiatanIds->count(),
                'kegiatan_ids' => $plckKegiatanIds,
                'kegiatan_count' => count($plckKegiatanIds),
                'program_ids' => $pluckProgramIds,
                'program_count' => count($pluckProgramIds),
                'total_anggaran' => $datas->sum('nominal'),
            ];

            $arrProgram = Program::whereIn('id', $pluckProgramIds)
                ->get();
            $return['programs'] = [];
            foreach ($arrProgram as $program) {
                $arrSubKegiatan = $program->SubKegiatans->whereIn('id', $pluckSubKegiatanIds->toArray());
                foreach ($arrSubKegiatan as $subKegiatan) {
                    $arrTagsSumberDana = TaggingSumberDana::where('sub_kegiatan_id', $subKegiatan->id)
                        ->where('ref_tag_id', $tag->id)
                        ->where('year', $request->year)
                        ->get();
                    $return['programs'][] = [
                        'id' => $program->id,
                        'name' => $program->name,
                        'fullcode' => $program->fullcode,
                        'instance_name' => $program->Instance->name,
                        'year' => $request->year,
                        'total_anggaran' => $arrTagsSumberDana->sum('nominal'),
                    ];
                }
            }

            $arrKegiatan = Kegiatan::whereIn('id', $plckKegiatanIds)
                ->get();
            $return['kegiatans'] = [];
            foreach ($arrKegiatan as $kegiatan) {
                $arrSubKegiatan = $kegiatan->SubKegiatans->whereIn('id', $pluckSubKegiatanIds->toArray());
                foreach ($arrSubKegiatan as $subKegiatan) {
                    $arrTagsSumberDana = TaggingSumberDana::where('sub_kegiatan_id', $subKegiatan->id)
                        ->where('ref_tag_id', $tag->id)
                        ->where('year', $request->year)
                        ->get();
                    $return['kegiatans'][] = [
                        'id' => $kegiatan->id,
                        'name' => $kegiatan->name,
                        'fullcode' => $kegiatan->fullcode,
                        'program_name' => $kegiatan->Program->name,
                        'instance_name' => $kegiatan->Instance->name,
                        'year' => $request->year,
                        'total_anggaran' => $arrTagsSumberDana->sum('nominal'),
                    ];
                }
            }

            $arrSubKegiatan = SubKegiatan::whereIn('id', $pluckSubKegiatanIds->toArray())
                ->get();
            $return['sub_kegiatans'] = [];
            foreach ($arrSubKegiatan as $subKegiatan) {
                $arrTagsSumberDana = TaggingSumberDana::where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('ref_tag_id', $tag->id)
                    ->where('year', $request->year)
                    ->get();
                $return['sub_kegiatans'][] = [
                    'id' => $subKegiatan->id,
                    'name' => $subKegiatan->name,
                    'fullcode' => $subKegiatan->fullcode,
                    'kegiatan_name' => $subKegiatan->Kegiatan->name,
                    'program_name' => $subKegiatan->Program->name,
                    'instance_name' => $subKegiatan->Instance->name,
                    'year' => $request->year,
                    'total_anggaran' => $arrTagsSumberDana->sum('nominal'),
                ];
            }

            $return['datas'] = [];
            $allDatas = [];
            $arrTags = TagSumberDana::where('status', 'active')
                ->get();
            foreach ($arrTags as $tag) {
                $datas = TaggingSumberDana::where('ref_tag_id', $tag->id)
                    ->where('year', $request->year)
                    ->get();
                $allDatas[] = [
                    'tag' => $tag->name,
                    'total_anggaran' => $datas->sum('nominal'),
                ];
            }
            $allDatas = collect($allDatas)->sortByDesc('total_anggaran')
                ->where('total_anggaran', '>', 0)
                ->values();
            $return['datas'] = $allDatas;

            DB::commit();
            return $this->successResponse($return);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function reportRealisasiPDF(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|integer',
            'year' => 'required',
            'triwulan' => 'required',
            'kind' => 'required',
            'model_id' => 'required',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'year' => 'Tahun',
            'triwulan' => 'Triwulan',
            'kind' => 'Jenis',
            'model_id' => 'ID Model',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $datas = [];
        $info = [];
        if ($request->kind == 'sub-kegiatan') {
            $info = [
                'title' => 'Laporan Realisasi Sub Kegiatan',
                'triwulan' => $request->triwulan,
            ];
            $datas = $this->__getReportSubKegiatan($request->model_id, $request->year, $request->triwulan);
        }

        if ($request->kind == 'kegiatan') {
            $info = [
                'title' => 'Laporan Realisasi Kegiatan',
                'triwulan' => $request->triwulan,
            ];
            $datas = $this->__getReportKegiatan($request->model_id, $request->year, $request->triwulan);
        }

        if ($request->kind == 'program') {
            $info = [
                'title' => 'Laporan Realisasi Program',
                'triwulan' => $request->triwulan,
            ];
            $datas = $this->__getReportProgram($request->model_id, $request->year, $request->triwulan);
        }


        $return = [
            'info' => $info,
            'data' => $datas,
        ];
        // dd($return);


        $pdf = Pdf::loadView('pdf.realisasi', $return);
        return $pdf->stream();
    }

    function __getReportProgram($id, $year, $triwulan)
    {
        $return = [];
        $program = Program::find($id);
        $instance = Instance::find($program->instance_id);


        $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        if ($triwulan == 1) {
            $arrMonths = [1, 2, 3];
        } elseif ($triwulan == 2) {
            $arrMonths = [4, 5, 6];
        } elseif ($triwulan == 3) {
            $arrMonths = [7, 8, 9];
        } elseif ($triwulan == 4) {
            $arrMonths = [10, 11, 12];
        }


        $rpjmd = RPJMD::where('program_id', $program->id)
            ->where('instance_id', $instance->id)
            ->first();
        $IndikatorKinerjaProgram = $rpjmd ? $rpjmd->Indicators->where('year', $year)->pluck('name') : [];
        $RealisasiProgram = [];
        $TargetKinerjaProgram = [];
        if ($rpjmd) {
            $arrIndicators = collect($rpjmd->Indicators->where('year', $year))->values();
            foreach ($arrIndicators as $keyIndi => $value) {
                $TargetKinerjaProgram[] = [
                    'name' => $value->name,
                    'targetRpjmd' => $value->value,
                    'satuanRpjmd' => $value->Satuan->name ?? '',
                ];

                $RealisasiProgram[$keyIndi]['name'] = $value->name;
                foreach ($arrMonths as $month) {
                    $dataRealisasiProgram = RealisasiSubKegiatan::where('program_id', $program->id)
                        ->where('instance_id', $instance->id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->get();
                    $RealisasiProgram[$keyIndi][$month] = [
                        'kinerja' => $dataRealisasiProgram->avg('persentase_realisasi_kinerja'),
                        'kinerjaSatuan' => '%',
                        'keuangan' => $dataRealisasiProgram->sum('realisasi_anggaran')
                    ];
                }
                $RealisasiProgram[$keyIndi]['total_realisasi_keuangan'] = collect($RealisasiProgram[$keyIndi])->max('keuangan');
            }
        }
        $AnggaranRenstraProgram = Renstra::where('program_id', $program->id)
            ->where('instance_id', $instance->id)
            ->where('periode_id', $program->periode_id)
            ->sum('total_anggaran');
        $AnggaranRenjaProgram = Renja::where('program_id', $program->id)
            ->where('instance_id', $instance->id)
            ->where('periode_id', $program->periode_id)
            ->sum('total_anggaran');
        $AnggaranApbdProgram = Apbd::where('program_id', $program->id)
            ->where('instance_id', $instance->id)
            ->where('periode_id', $program->periode_id)
            ->sum('total_anggaran');


        $return = [
            'type' => 'program',
            'id' => $program->id,
            'fullcode' => $program->fullcode,
            'name' => $program->name,
            'instance_code' => $instance->code,
            'instance_name' => $instance->name,
            'indikatorKinerja' => $IndikatorKinerjaProgram,
            'targetKinerja' => $TargetKinerjaProgram,
            'anggaranRenstra' => $AnggaranRenstraProgram,
            'anggaranRenja' => $AnggaranRenjaProgram,
            'anggaranApbd' => $AnggaranApbdProgram,
            'realisasi' => $RealisasiProgram,
            'total_realisasi_keuangan' => collect($RealisasiProgram)->max('total_realisasi_keuangan'),
            'persentase_realisasi_keuangan' => $AnggaranApbdProgram ? (collect($RealisasiProgram)->max('total_realisasi_keuangan') / $AnggaranApbdProgram * 100) : 0,
        ];

        // dd($return);
        return $return;
    }

    function __getReportKegiatan($id, $year, $triwulan)
    {
        $return = [];
        $kegiatan = Kegiatan::find($id);
        $program = Program::find($kegiatan->program_id);
        $instance = Instance::find($kegiatan->instance_id);


        $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        if ($triwulan == 1) {
            $arrMonths = [1, 2, 3];
        } elseif ($triwulan == 2) {
            $arrMonths = [4, 5, 6];
        } elseif ($triwulan == 3) {
            $arrMonths = [7, 8, 9];
        } elseif ($triwulan == 4) {
            $arrMonths = [10, 11, 12];
        }

        $pluckIds = DB::table('con_indikator_kinerja_kegiatan')
            ->where('instance_id', $instance->id)
            ->where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->get()
            ->pluck('id');

        $indikatorKinerjaKegiatan = IndikatorKegiatan::whereIn('pivot_id', $pluckIds->toArray())
            ->get();
        $TargetKinerjaKegiatan = [];
        $RealisasiKegiatan = [];
        foreach ($indikatorKinerjaKegiatan as $keyIndKgt => $indKgt) {
            $renstraKegiatan = RenstraKegiatan::where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->first();
            $kinerjaRenstraKegiatanJson = json_decode($renstraKegiatan->kinerja_json, true);
            $satuanRenstraKegiatanJson = json_decode($renstraKegiatan->satuan_json, true);
            if ($satuanRenstraKegiatanJson) {
                $satuanRenstraKegiatanName = $satuanRenstraKegiatanJson[$keyIndKgt] ?? null;
                if ($satuanRenstraKegiatanName) {
                    $satuanRenstraKegiatanName = Satuan::find($satuanRenstraKegiatanJson[$keyIndKgt] ?? 0)->name ?? '';
                }
            }

            $renjaKegiatan = RenjaKegiatan::where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->first();
            $kinerjaRenjaKegiatanJson = json_decode($renjaKegiatan->kinerja_json, true);
            $satuanRenjaKegiatanJson = json_decode($renjaKegiatan->satuan_json, true);
            if ($satuanRenjaKegiatanJson) {
                $satuanRenjaKegiatanName = $satuanRenjaKegiatanJson[$keyIndKgt] ?? null;
                if ($satuanRenjaKegiatanName) {
                    $satuanRenjaKegiatanName = Satuan::find($satuanRenjaKegiatanJson[$keyIndKgt] ?? 0)->name ?? '';
                }
            }

            $TargetKinerjaKegiatan[] = [
                'name' => $indKgt->name,
                'targetRenstra' => $kinerjaRenstraKegiatanJson[$keyIndKgt] ?? 0,
                'satuanRenstra' => $satuanRenstraKegiatanName ?? '',
                'targetRenja' => $kinerjaRenjaKegiatanJson[$keyIndKgt] ?? 0,
                'satuanRenja' => $satuanRenjaKegiatanName ?? '',
            ];

            $RealisasiKegiatan[$keyIndKgt]['name'] = $indKgt->name;
            foreach ($arrMonths as $month) {
                $dataRealisasiKegiatan = RealisasiSubKegiatan::where('program_id', $program->id)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('instance_id', $instance->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();
                $RealisasiKegiatan[$keyIndKgt][$month] = [
                    'kinerja' => $dataRealisasiKegiatan->avg('persentase_realisasi_kinerja'),
                    'kinerjaSatuan' => '%',
                    'keuangan' => $dataRealisasiKegiatan->sum('realisasi_anggaran')
                ];
            }
            $RealisasiKegiatan[$keyIndKgt]['total_realisasi_keuangan'] = collect($RealisasiKegiatan[$keyIndKgt])->max('keuangan');
        }
        $anggaranRenstraKegiatan = RenstraKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->sum('total_anggaran');
        $anggaranRenstraKegiatan = RenjaKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->sum('total_anggaran');
        $anggaranApbdKegiatan = ApbdKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->sum('total_anggaran');

        $return = [
            'type' => 'kegiatan',
            'id' => $kegiatan->id,
            'fullcode' => $kegiatan->fullcode,
            'name' => $kegiatan->name,
            'program_fullcode' => $program->fullcode,
            'program_name' => $program->name,
            'instance_code' => $instance->code,
            'instance_name' => $instance->name,
            'indikatorKinerja' => $indikatorKinerjaKegiatan->pluck('name'),
            'targetKinerja' => $TargetKinerjaKegiatan,
            'anggaranRenstra' => $anggaranRenstraKegiatan,
            'anggaranRenja' => $anggaranRenstraKegiatan,
            'anggaranApbd' => $anggaranApbdKegiatan,
            'realisasi' => $RealisasiKegiatan,
            'total_realisasi_keuangan' => collect($RealisasiKegiatan)->max('total_realisasi_keuangan'),
            'persentase_realisasi_keuangan' => $anggaranApbdKegiatan ? (collect($RealisasiKegiatan)->max('total_realisasi_keuangan') / $anggaranApbdKegiatan * 100) : 0,
        ];

        // dd($return);
        return $return;
    }

    function __getReportSubKegiatan($id, $year, $triwulan)
    {
        $return = [];
        $subKegiatan = SubKegiatan::find($id);
        $program = Program::find($subKegiatan->program_id);
        $kegiatan = Kegiatan::find($subKegiatan->kegiatan_id);
        $instance = Instance::find($subKegiatan->instance_id);


        $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        if ($triwulan == 1) {
            $arrMonths = [1, 2, 3];
        } elseif ($triwulan == 2) {
            $arrMonths = [4, 5, 6];
        } elseif ($triwulan == 3) {
            $arrMonths = [7, 8, 9];
        } elseif ($triwulan == 4) {
            $arrMonths = [10, 11, 12];
        }

        $pluckIds = DB::table('con_indikator_kinerja_sub_kegiatan')
            ->where('instance_id', $instance->id)
            ->where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->where('sub_kegiatan_id', $subKegiatan->id)
            ->get()
            ->pluck('id');
        $indikatorKinerjaSubKegiatan = IndikatorSubKegiatan::whereIn('pivot_id', $pluckIds->toArray())
            ->get();
        $TargetKinerjaSubKegiatan = [];
        $RealisasiSubKegiatan = [];
        foreach ($indikatorKinerjaSubKegiatan as $keyIndSubKgt => $indSubKgt) {
            $renstraSubKegiatan = RenstraSubKegiatan::where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->first();
            $kinerjaRenstraSubKegiatanJson = json_decode($renstraSubKegiatan->kinerja_json, true);
            $satuanRenstraSubKegiatanJson = json_decode($renstraSubKegiatan->satuan_json, true);
            if ($satuanRenstraSubKegiatanJson) {
                $satuanRenstraSubKegiatanName = $satuanRenstraSubKegiatanJson[$keyIndSubKgt] ?? null;
                if ($satuanRenstraSubKegiatanName) {
                    $satuanRenstraSubKegiatanName = Satuan::find($satuanRenstraSubKegiatanJson[$keyIndSubKgt] ?? 0);
                    $satuanRenstraSubKegiatanName = $satuanRenstraSubKegiatanName ? $satuanRenstraSubKegiatanName->name : '';
                }
            }

            $renjaSubKegiatan = RenjaSubKegiatan::where('program_id', $program->id)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('sub_kegiatan_id', $subKegiatan->id)
                ->first();
            $kinerjaRenjaSubKegiatanJson = json_decode($renjaSubKegiatan->kinerja_json, true);
            $satuanRenjaSubKegiatanJson = json_decode($renjaSubKegiatan->satuan_json, true);
            if ($satuanRenjaSubKegiatanJson) {
                $satuanRenjaSubKegiatanName = $satuanRenjaSubKegiatanJson[$keyIndSubKgt] ?? null;
                if ($satuanRenjaSubKegiatanName) {
                    $satuanRenjaSubKegiatanName = Satuan::find($satuanRenjaSubKegiatanJson[$keyIndSubKgt] ?? 0);
                    $satuanRenjaSubKegiatanName = $satuanRenjaSubKegiatanName ? $satuanRenjaSubKegiatanName->name : '';
                }
            }

            $TargetKinerjaSubKegiatan[] = [
                'name' => $indSubKgt->name,
                'targetRenstra' => $kinerjaRenstraSubKegiatanJson[$keyIndSubKgt] ?? 0,
                'satuanRenstra' => $satuanRenstraSubKegiatanName ?? '',
                'targetRenja' => $kinerjaRenjaSubKegiatanJson[$keyIndSubKgt] ?? 0,
                'satuanRenja' => $satuanRenjaSubKegiatanName ?? '',
            ];

            $RealisasiSubKegiatan[$keyIndSubKgt]['name'] = $indSubKgt->name;
            foreach ($arrMonths as $month) {
                $dataRealisasiSubKegiatan = RealisasiSubKegiatan::where('program_id', $program->id)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('instance_id', $instance->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
                if ($dataRealisasiSubKegiatan) {
                    $CollectKinerjaSubs = json_decode($dataRealisasiSubKegiatan->realisasi_kinerja_json, true);
                    $CollectKinerjaSubs = collect($CollectKinerjaSubs)->where('type', 'kinerja');
                    $KinerjaSubs = $CollectKinerjaSubs->pluck('realisasi');
                    $KinerjaSubsSatuan = $CollectKinerjaSubs->pluck('satuan_name');
                }
                $RealisasiSubKegiatan[$keyIndSubKgt][$month] = [
                    'kinerja' => $KinerjaSubs[$keyIndSubKgt] ?? 0,
                    'kinerjaSatuan' => $KinerjaSubsSatuan[$keyIndSubKgt] ?? '',
                    'keuangan' => $dataRealisasiSubKegiatan->realisasi_anggaran,
                ];
            }
            $RealisasiSubKegiatan[$keyIndSubKgt]['total_realisasi_keuangan'] = collect($RealisasiSubKegiatan[$keyIndSubKgt])->max('keuangan');
        }
        $anggaranRenstraSubKegiatan = RenstraSubKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->where('sub_kegiatan_id', $subKegiatan->id)
            ->sum('total_anggaran');
        $anggaranRenstraSubKegiatan = RenjaSubKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->where('sub_kegiatan_id', $subKegiatan->id)
            ->sum('total_anggaran');
        $anggaranApbdSubKegiatan = ApbdSubKegiatan::where('program_id', $program->id)
            ->where('kegiatan_id', $kegiatan->id)
            ->where('sub_kegiatan_id', $subKegiatan->id)
            ->sum('total_anggaran');

        $arrTagsSumberDana = TaggingSumberDana::where('sub_kegiatan_id', $subKegiatan->id)
            // ->where('status', 'active')
            ->get();
        $returnTagsSumberDana = [];
        foreach ($arrTagsSumberDana as $tagSumberDana) {
            $returnTagsSumberDana[] = [
                'name' => $tagSumberDana->RefTag->name,
                'nominal' => $tagSumberDana->nominal,
                'notes' => $tagSumberDana->notes,
            ];
        }

        $return = [
            'type' => 'subKegiatan',
            'id' => $subKegiatan->id,
            'fullcode' => $subKegiatan->fullcode,
            'name' => $subKegiatan->name,
            'kegiatan_fullcode' => $kegiatan->fullcode,
            'kegiatan_name' => $kegiatan->name,
            'program_fullcode' => $program->fullcode,
            'program_name' => $program->name,
            'instance_code' => $instance->code,
            'instance_name' => $instance->name,
            'indikatorKinerja' => $indikatorKinerjaSubKegiatan->pluck('name'),
            'targetKinerja' => $TargetKinerjaSubKegiatan,
            'anggaranRenstra' => $anggaranRenstraSubKegiatan,
            'anggaranRenja' => $anggaranRenstraSubKegiatan,
            'anggaranApbd' => $anggaranApbdSubKegiatan,
            'realisasi' => $RealisasiSubKegiatan,
            'total_realisasi_keuangan' => collect($RealisasiSubKegiatan)->max('total_realisasi_keuangan'),
            'persentase_realisasi_keuangan' => $anggaranApbdSubKegiatan ? (collect($RealisasiSubKegiatan)->max('total_realisasi_keuangan') / $anggaranApbdSubKegiatan * 100) : 0,
            'tagsSumberDana' => $returnTagsSumberDana,
        ];

        // dd($return);

        return $return;
    }

    function reportKodeRekening(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|integer',
            'periode' => 'required|integer|exists:ref_periode,id',
            'year' => 'required',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'periode' => 'Periode',
            'year' => 'Tahun',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $return = [];
        $instance = Instance::find($request->instance);
        if ($instance) {
            $return['instance'] = [
                'id' => $instance->id,
                'name' => $instance->name,
                'code' => $instance->code,
                'alias' => $instance->alias,
                'logo' => asset($instance->logo),
            ];
        } else {
            $return['instance'] = [
                'id' => null,
                'name' => null,
                'code' => null,
                'alias' => null,
                'logo' => null,
            ];
        }

        $datas = [];

        $arrKodeRekSelected = TargetKinerja::select('kode_rekening_id')
            ->where('periode_id', $request->periode)
            ->where('month', 12)
            ->when($instance, function ($q) use ($instance) {
                return $q->where('instance_id', $instance->id);
            })
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
            if (!$rekeningRincian) {
                continue;
            }
            $rekeningObjek = KodeRekening::find($rekeningRincian->parent_id);
            if (!$rekeningObjek) {
                continue;
            }
            $rekeningJenis = KodeRekening::find($rekeningObjek->parent_id);
            if (!$rekeningJenis) {
                continue;
            }
            $rekeningKelompok = KodeRekening::find($rekeningJenis->parent_id);
            if (!$rekeningKelompok) {
                continue;
            }
            $rekeningAkun = KodeRekening::find($rekeningKelompok->parent_id);
            if (!$rekeningAkun) {
                continue;
            }

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
                ->where('periode_id', $request->periode)
                ->where('month', 12)
                ->when($instance, function ($q) use ($instance) {
                    return $q->where('instance_id', $instance->id);
                })
                ->get();
            $paguAnggaran = $arrDataTarget->sum('pagu_sipd');
            $realisasiAnggaranLalu = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                ->where('year', $request->year - 1)
                ->where('periode_id', $request->periode)
                ->where('month', 12)
                ->when($instance, function ($q) use ($instance) {
                    return $q->where('instance_id', $instance->id);
                })
                ->sum('anggaran');
            $realisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                ->where('year', $request->year)
                ->where('periode_id', $request->periode)
                ->where('month', 12)
                ->when($instance, function ($q) use ($instance) {
                    return $q->where('instance_id', $instance->id);
                })
                ->sum('anggaran');
            $datas[] = [
                'kode_rekening_id' => $akun->id,
                'kode_rekening' => $akun->fullcode,
                'code_1' => $akun->code_1,
                'code_2' => $akun->code_2,
                'code_3' => $akun->code_3,
                'code_4' => $akun->code_4,
                'code_5' => $akun->code_5,
                'code_6' => $akun->code_6,
                'nama_rekening' => $akun->name,
                'pagu_anggaran' => $paguAnggaran,
                'realisasi_anggaran_ini' => $realisasiAnggaran,
                'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
            ];


            // Level 2
            foreach ($collectKelompok->where('parent_id', $akun->id) as $kelompok) {
                $arrKodeRekenings = KodeRekening::where('parent_id', $kelompok->id)->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('periode_id', $request->periode)
                    ->where('month', 12)
                    ->when($instance, function ($q) use ($instance) {
                        return $q->where('instance_id', $instance->id);
                    })
                    ->get();
                $paguAnggaran = $arrDataTarget->sum('pagu_sipd');

                $realisasiAnggaranLalu = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $request->year - 1)
                    ->where('periode_id', $request->periode)
                    ->where('month', 12)
                    ->when($instance, function ($q) use ($instance) {
                        return $q->where('instance_id', $instance->id);
                    })
                    ->sum('anggaran');

                $realisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $request->year)
                    ->where('periode_id', $request->periode)
                    ->where('month', 12)
                    ->when($instance, function ($q) use ($instance) {
                        return $q->where('instance_id', $instance->id);
                    })
                    ->sum('anggaran');

                $datas[] = [
                    'kode_rekening_id' => $kelompok->id,
                    'kode_rekening' => $kelompok->fullcode,
                    'code_1' => $kelompok->code_1,
                    'code_2' => $kelompok->code_2,
                    'code_3' => $kelompok->code_3,
                    'code_4' => $kelompok->code_4,
                    'code_5' => $kelompok->code_5,
                    'code_6' => $kelompok->code_6,
                    'nama_rekening' => $kelompok->name,
                    'pagu_anggaran' => $paguAnggaran,
                    'realisasi_anggaran_ini' => $realisasiAnggaran,
                    'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                    'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
                ];


                // Level 3
                foreach ($collectJenis->where('parent_id', $kelompok->id) as $jenis) {
                    $arrKodeRekenings = KodeRekening::where('parent_id', $jenis->id)->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                    $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('periode_id', $request->periode)
                        ->where('month', 12)
                        ->when($instance, function ($q) use ($instance) {
                            return $q->where('instance_id', $instance->id);
                        })
                        ->get();
                    $paguAnggaran = $arrDataTarget->sum('pagu_sipd');

                    $realisasiAnggaranLalu = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $request->year - 1)
                        ->where('periode_id', $request->periode)
                        ->where('month', 12)
                        ->when($instance, function ($q) use ($instance) {
                            return $q->where('instance_id', $instance->id);
                        })
                        ->sum('anggaran');

                    $realisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $request->year)
                        ->where('periode_id', $request->periode)
                        ->where('month', 12)
                        ->when($instance, function ($q) use ($instance) {
                            return $q->where('instance_id', $instance->id);
                        })
                        ->sum('anggaran');

                    $datas[] = [
                        'kode_rekening_id' => $jenis->id,
                        'kode_rekening' => $jenis->fullcode,
                        'code_1' => $jenis->code_1,
                        'code_2' => $jenis->code_2,
                        'code_3' => $jenis->code_3,
                        'code_4' => $jenis->code_4,
                        'code_5' => $jenis->code_5,
                        'code_6' => $jenis->code_6,
                        'nama_rekening' => $jenis->name,
                        'pagu_anggaran' => $paguAnggaran,
                        'realisasi_anggaran_ini' => $realisasiAnggaran,
                        'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                        'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
                    ];


                    // Level 4
                    foreach ($collectObjek->where('parent_id', $jenis->id) as $objek) {
                        $arrKodeRekenings = KodeRekening::where('parent_id', $objek->id)->get();
                        $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                        $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('periode_id', $request->periode)
                            ->where('month', 12)
                            ->when($instance, function ($q) use ($instance) {
                                return $q->where('instance_id', $instance->id);
                            })
                            ->get();
                        $paguAnggaran = $arrDataTarget->sum('pagu_sipd');

                        $realisasiAnggaranLalu = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $request->year - 1)
                            ->where('periode_id', $request->periode)
                            ->where('month', 12)
                            ->when($instance, function ($q) use ($instance) {
                                return $q->where('instance_id', $instance->id);
                            })
                            ->sum('anggaran');

                        $realisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $request->year)
                            ->where('periode_id', $request->periode)
                            ->where('month', 12)
                            ->when($instance, function ($q) use ($instance) {
                                return $q->where('instance_id', $instance->id);
                            })
                            ->sum('anggaran');

                        $datas[] = [
                            'kode_rekening_id' => $objek->id,
                            'kode_rekening' => $objek->fullcode,
                            'code_1' => $objek->code_1,
                            'code_2' => $objek->code_2,
                            'code_3' => $objek->code_3,
                            'code_4' => $objek->code_4,
                            'code_5' => $objek->code_5,
                            'code_6' => $objek->code_6,
                            'nama_rekening' => $objek->name,
                            'pagu_anggaran' => $paguAnggaran,
                            'realisasi_anggaran_ini' => $realisasiAnggaran,
                            'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                            'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
                        ];


                        // Level 5
                        foreach ($collectRincian->where('parent_id', $objek->id) as $rincian) {

                            $arrKodeRekenings = KodeRekening::where('parent_id', $rincian->id)->get();
                            $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('periode_id', $request->periode)
                                ->where('month', 12)
                                ->when($instance, function ($q) use ($instance) {
                                    return $q->where('instance_id', $instance->id);
                                })
                                ->get();
                            $paguAnggaran = $arrDataTarget->sum('pagu_sipd');

                            $realisasiAnggaranLalu = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $request->year - 1)
                                ->where('periode_id', $request->periode)
                                ->where('month', 12)
                                ->when($instance, function ($q) use ($instance) {
                                    return $q->where('instance_id', $instance->id);
                                })
                                ->sum('anggaran');

                            $realisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $request->year)
                                ->where('periode_id', $request->periode)
                                ->where('month', 12)
                                ->when($instance, function ($q) use ($instance) {
                                    return $q->where('instance_id', $instance->id);
                                })
                                ->sum('anggaran');

                            $datas[] = [
                                'kode_rekening_id' => $rincian->id,
                                'kode_rekening' => $rincian->fullcode,
                                'code_1' => $rincian->code_1,
                                'code_2' => $rincian->code_2,
                                'code_3' => $rincian->code_3,
                                'code_4' => $rincian->code_4,
                                'code_5' => $rincian->code_5,
                                'code_6' => $rincian->code_6,
                                'nama_rekening' => $rincian->name,
                                'pagu_anggaran' => $paguAnggaran,
                                'realisasi_anggaran_ini' => $realisasiAnggaran,
                                'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                                'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
                            ];


                            // Level 6
                            foreach ($collectRekening->where('parent_id', $rincian->id) as $rekening) {
                                $arrDataTarget = TargetKinerja::where('kode_rekening_id', $rekening->id)
                                    ->where('periode_id', $request->periode)
                                    ->where('month', 12)
                                    ->when($instance, function ($q) use ($instance) {
                                        return $q->where('instance_id', $instance->id);
                                    })
                                    ->orderBy('nama_paket')
                                    ->get();
                                $paguAnggaran = $arrDataTarget->sum('pagu_sipd');


                                $realisasiAnggaranLalu = Realisasi::where('kode_rekening_id', $rekening->id)
                                    ->where('periode_id', $request->periode)
                                    ->where('year', $request->year - 1)
                                    ->where('month', 12)
                                    ->when($instance, function ($q) use ($instance) {
                                        return $q->where('instance_id', $instance->id);
                                    })
                                    ->where('status', 'verified')
                                    ->sum('anggaran');

                                $realisasiAnggaran = Realisasi::where('kode_rekening_id', $rekening->id)
                                    ->where('year', $request->year)
                                    ->where('periode_id', $request->periode)
                                    ->where('month', 12)
                                    ->when($instance, function ($q) use ($instance) {
                                        return $q->where('instance_id', $instance->id);
                                    })
                                    ->where('status', 'verified')
                                    ->sum('anggaran');

                                $datas[] = [
                                    'kode_rekening_id' => $rekening->id,
                                    'kode_rekening' => $rekening->fullcode,
                                    'code_1' => $rekening->code_1,
                                    'code_2' => $rekening->code_2,
                                    'code_3' => $rekening->code_3,
                                    'code_4' => $rekening->code_4,
                                    'code_5' => $rekening->code_5,
                                    'code_6' => $rekening->code_6,
                                    'nama_rekening' => $rekening->name,
                                    'pagu_anggaran' => $paguAnggaran,
                                    'realisasi_anggaran_ini' => $realisasiAnggaran,
                                    'realisasi_anggaran_lalu' => $realisasiAnggaranLalu,
                                    'realisasi_anggaran_total' => $realisasiAnggaranLalu + $realisasiAnggaran,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $return['datas'] = $datas;
        return $this->successResponse($return);
    }
}
