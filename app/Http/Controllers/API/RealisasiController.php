<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Instance;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Data\TargetKinerja;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Data\RealisasiStatus;
use App\Models\Data\RealisasiRincian;
use App\Models\Data\TaggingSumberDana;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\TargetKinerjaStatus;
use App\Models\Data\TargetKinerjaRincian;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetKinerjaKeterangan;

class RealisasiController extends Controller
{
    use JsonReturner;


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

    function detailRealisasi($id, Request $request)
    {
        $datas = [];
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }

        $RealisasiStatus = RealisasiStatus::where('sub_kegiatan_id', $id)
            ->where('month', $request->month)
            ->first();
        if (!$RealisasiStatus) {
            $RealisasiStatus = new RealisasiStatus();
            $RealisasiStatus->sub_kegiatan_id = $id;
            $RealisasiStatus->month = $request->month;
            $RealisasiStatus->year = $request->year;
            $RealisasiStatus->status = 'draft';
            $RealisasiStatus->status_leader = 'draft';
            $RealisasiStatus->save();

            DB::table('notes_realisasi')->insert([
                'data_id' => $RealisasiStatus->id,
                'user_id' => auth()->user()->id,
                'status' => 'draft',
                'type' => 'system',
                'message' => 'Data dibuat',
                'created_at' => now(),
            ]);
        }
        $TargetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();

        $tagSumberDana = [];
        $arrTags = TaggingSumberDana::where('sub_kegiatan_id', $id)
            ->where('status', 'active')
            ->get();
        foreach ($arrTags as $tag) {
            $tagSumberDana[] = [
                'id' => $tag->id,
                'tag_id' => $tag->ref_tag_id,
                'tag_name' => $tag->RefTag->name,
                'nominal' => $tag->nominal,
            ];
        }

        $datas['subkegiatan'] = [
            'id' => $subKegiatan->id,
            'fullcode' => $subKegiatan->fullcode,
            'name' => $subKegiatan->name,
            'instance_name' => $subKegiatan->Instance->name ?? 'Tidak Diketahui',
            'status' => $RealisasiStatus->status,
            'status_leader' => $RealisasiStatus->status_leader,
            'status_target' => $TargetKinerjaStatus->status,
            'tag_sumber_dana' => $tagSumberDana,
        ];

        $datas['data'] = [];

        $arrKodeRekSelected = TargetKinerja::select('kode_rekening_id')
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('sub_kegiatan_id', $id)
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
                $datas['data'][] = [
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
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->where('sub_kegiatan_id', $id)
                ->get();
            $paguSipd = $arrDataTarget->sum('pagu_sipd');

            $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->where('sub_kegiatan_id', $id)
                ->sum('anggaran');
            $datas['data'][] = [
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
                'rincian_belanja' => [],
            ];

            // Level 2
            foreach ($collectKelompok->where('parent_id', $akun->id) as $kelompok) {
                $arrKodeRekenings = KodeRekening::where('parent_id', $kelompok->id)->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('sub_kegiatan_id', $id)
                    ->get();
                $paguSipd = $arrDataTarget->sum('pagu_sipd');

                $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('sub_kegiatan_id', $id)
                    ->sum('anggaran');
                $datas['data'][] = [
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
                    'rincian_belanja' => [],
                ];

                // Level 3
                foreach ($collectJenis->where('parent_id', $kelompok->id) as $jenis) {
                    $arrKodeRekenings = KodeRekening::where('parent_id', $jenis->id)->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                    $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();

                    $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $request->year)
                        ->where('month', $request->month)
                        ->where('sub_kegiatan_id', $id)
                        ->get();
                    $paguSipd = $arrDataTarget->sum('pagu_sipd');

                    $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $request->year)
                        ->where('month', $request->month)
                        ->where('sub_kegiatan_id', $id)
                        ->sum('anggaran');
                    $datas['data'][] = [
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
                        'rincian_belanja' => [],
                    ];

                    // Level 4
                    foreach ($collectObjek->where('parent_id', $jenis->id) as $objek) {

                        $arrKodeRekenings = KodeRekening::where('parent_id', $objek->id)->get();
                        $arrKodeRekenings = KodeRekening::whereIn('parent_id', $arrKodeRekenings->pluck('id'))->get();
                        $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $request->year)
                            ->where('month', $request->month)
                            ->where('sub_kegiatan_id', $id)
                            ->get();
                        $paguSipd = $arrDataTarget->sum('pagu_sipd');

                        $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $request->year)
                            ->where('month', $request->month)
                            ->where('sub_kegiatan_id', $id)
                            ->sum('anggaran');
                        $datas['data'][] = [
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
                            'rincian_belanja' => [],
                        ];

                        // Level 5
                        foreach ($collectRincian->where('parent_id', $objek->id) as $rincian) {

                            $arrKodeRekenings = KodeRekening::where('parent_id', $rincian->id)->get();
                            $arrDataTarget = TargetKinerja::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $request->year)
                                ->where('month', $request->month)
                                ->where('sub_kegiatan_id', $id)
                                ->get();
                            $paguSipd = $arrDataTarget->sum('pagu_sipd');

                            $sumRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $request->year)
                                ->where('month', $request->month)
                                ->where('sub_kegiatan_id', $id)
                                ->sum('anggaran');
                            $datas['data'][] = [
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
                                'rincian_belanja' => [],
                            ];

                            // Level 6
                            foreach ($collectRekening->where('parent_id', $rincian->id) as $rekening) {

                                $arrDataTarget = TargetKinerja::where('kode_rekening_id', $rekening->id)
                                    ->where('year', $request->year)
                                    ->where('month', $request->month)
                                    ->where('sub_kegiatan_id', $id)
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
                                    $dataRealisasi = Realisasi::where('target_id', $dataTarget->id)->first();
                                    if (!$dataRealisasi) {
                                        $dataRealisasi = new Realisasi();
                                        $dataRealisasi->periode_id = $dataTarget->periode_id;
                                        $dataRealisasi->year = $dataTarget->year;
                                        $dataRealisasi->month = $dataTarget->month;
                                        $dataRealisasi->instance_id = $dataTarget->instance_id;
                                        $dataRealisasi->target_id = $dataTarget->id;
                                        $dataRealisasi->urusan_id = $dataTarget->urusan_id;
                                        $dataRealisasi->bidang_urusan_id = $dataTarget->bidang_urusan_id;
                                        $dataRealisasi->program_id = $dataTarget->program_id;
                                        $dataRealisasi->kegiatan_id = $dataTarget->kegiatan_id;
                                        $dataRealisasi->sub_kegiatan_id = $dataTarget->sub_kegiatan_id;
                                        $dataRealisasi->kode_rekening_id = $dataTarget->kode_rekening_id;
                                        $dataRealisasi->sumber_dana_id = $dataTarget->sumber_dana_id;
                                        $dataRealisasi->status = 'draft';
                                        $dataRealisasi->status_leader = 'draft';
                                        $dataRealisasi->created_by = auth()->user()->id;
                                        $dataRealisasi->save();
                                    }
                                    $arrTargetKinerja[] = [
                                        'editable' => true,
                                        'long' => true,
                                        'type' => 'target-kinerja',
                                        'id_target' => $dataTarget->id,
                                        'id' => $dataRealisasi->id,
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
                                        'realisasi_anggaran' => (int)$dataRealisasi->anggaran,
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
                                    ->where('year', $request->year)
                                    ->where('month', $request->month)
                                    ->where('sub_kegiatan_id', $id)
                                    ->sum('anggaran');

                                $datas['data'][] = [
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
                                    'rincian_belanja' => [],
                                ];

                                foreach ($arrTargetKinerja as $targetKinerja) {
                                    $datas['data'][] = $targetKinerja;
                                    $arrRincianBelanja = [];
                                    $arrRincianBelanja = TargetKinerjaRincian::where('target_kinerja_id', $targetKinerja['id_target'])
                                        ->get();
                                    foreach ($arrRincianBelanja as $keyRincianBelanja => $rincianBelanja) {
                                        $realisasiRincian = RealisasiRincian::where('realisasi_id', $targetKinerja['id'])
                                            ->where('target_rincian_id', $rincianBelanja->id)
                                            ->first();
                                        if (!$realisasiRincian) {
                                            $realisasiRincian = new RealisasiRincian();
                                            $realisasiRincian->periode_id = $rincianBelanja->periode_id;
                                            $realisasiRincian->realisasi_id = $targetKinerja['id'];
                                            $realisasiRincian->target_rincian_id = $rincianBelanja->id;
                                            $realisasiRincian->title = $rincianBelanja->title;
                                            $realisasiRincian->pagu_sipd = $rincianBelanja->pagu_sipd;
                                            $realisasiRincian->anggaran = 0;
                                            $realisasiRincian->kinerja = 0;
                                            $realisasiRincian->persentase_kinerja = 0;
                                            $realisasiRincian->created_by = auth()->user()->id;
                                            $realisasiRincian->save();
                                        }
                                        $datas['data'][count($datas['data']) - 1]['rincian_belanja'][$keyRincianBelanja] = [
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
                                            'keterangan_rincian' => [],
                                        ];

                                        $arrKeterangan = TargetKinerjaKeterangan::where('parent_id', $rincianBelanja->id)->get();
                                        foreach ($arrKeterangan as $targetKeterangan) {
                                            $realisasiKeterangan = RealisasiKeterangan::where('realisasi_id', $realisasiRincian->id)
                                                ->where('target_keterangan_id', $targetKeterangan->id)
                                                ->where('parent_id', $realisasiRincian->id)
                                                ->first();
                                            if (!$realisasiKeterangan) {
                                                $realisasiKeterangan = new RealisasiKeterangan();
                                                $realisasiKeterangan->periode_id = $targetKeterangan->periode_id;
                                                $realisasiKeterangan->realisasi_id = $realisasiRincian->id;
                                                $realisasiKeterangan->target_keterangan_id = $targetKeterangan->id;
                                                $realisasiKeterangan->parent_id = $realisasiRincian->id;
                                                $realisasiKeterangan->title = $targetKeterangan->title;
                                                $realisasiKeterangan->koefisien = 0;
                                                $realisasiKeterangan->satuan_id = $targetKeterangan->satuan_id;
                                                $realisasiKeterangan->satuan_name = $targetKeterangan->satuan_name;
                                                $realisasiKeterangan->harga_satuan = $targetKeterangan->harga_satuan;
                                                $realisasiKeterangan->ppn = $targetKeterangan->ppn;
                                                $realisasiKeterangan->anggaran = 0;
                                                $realisasiKeterangan->kinerja = 0;
                                                $realisasiKeterangan->persentase_kinerja = 0;
                                                $realisasiKeterangan->created_by = auth()->user()->id;
                                                $realisasiKeterangan->save();
                                            }
                                            $isRealisasiMatch = (int)$realisasiKeterangan->anggaran === (int)$targetKeterangan->pagu ? true : false;
                                            $datas['data'][count($datas['data']) - 1]['rincian_belanja'][$keyRincianBelanja]['keterangan_rincian'][] = [
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
                                                'koefisien_realisasi' => $realisasiKeterangan->koefisien,
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

        $datas['data_error'] = false;
        $datas['error_message'] = null;
        return $this->successResponse($datas, 'detail realisasi');
    }

    function saveRealisasi($id, Request $request)
    {
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }
        DB::beginTransaction();
        try {
            $datas = collect($request->data)
                ->where('type', 'target-kinerja')
                ->values();

            $return = null;
            foreach ($datas as $data) {
                if ($data['is_detail'] === false && count($data['rincian_belanja']) === 0) {
                    $realisasi = Realisasi::find($data['id']);

                    // Check Target Kinerja Status Start
                    $targetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                        ->where('year', $realisasi->year)
                        ->where('month', $realisasi->month)
                        ->first();
                    if (!$targetKinerjaStatus) {
                        return $this->errorResponse('Data Target Kinerja Status tidak ditemukan', 200);
                    }
                    if ($targetKinerjaStatus->status !== 'verified') {
                        return $this->errorResponse('Data Target Kinerja belum diverifikasi', 200);
                    }
                    // Check Target Kinerja Status End

                    $realisasi->anggaran = $data['realisasi_anggaran'];
                    $realisasi->save();

                    // Update Realisasi Next Month until December Start
                    $arrRealisasiNext = Realisasi::where('instance_id', $realisasi->instance_id)
                        ->where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                        ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                        ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                        ->where('year', $realisasi->year)
                        ->whereBetween('month', [$realisasi->month, 12])
                        ->get();

                    foreach ($arrRealisasiNext as $realisasiNextMonth) {
                        $realisasiNextMonth->anggaran = $data['realisasi_anggaran'];
                        $realisasiNextMonth->save();
                    }
                    // Update Realisasi Next Month until December End
                }

                if ($data['is_detail'] === true && count($data['rincian_belanja']) > 0) {
                    $realisasi = Realisasi::find($data['id']);

                    // Check Target Kinerja Status Start
                    $targetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                        ->where('year', $realisasi->year)
                        ->where('month', $realisasi->month)
                        ->first();
                    if (!$targetKinerjaStatus) {
                        return $this->errorResponse('Data Target Kinerja Status tidak ditemukan', 200);
                    }
                    if ($targetKinerjaStatus->status !== 'verified') {
                        return $this->errorResponse('Data Target Kinerja belum diverifikasi', 200);
                    }
                    // Check Target Kinerja Status End

                    $realisasi->anggaran = $data['realisasi_anggaran'];
                    $realisasi->save();

                    // Update Realisasi Next Month until December Start
                    $arrRealisasiNext = Realisasi::where('instance_id', $realisasi->instance_id)
                        ->where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                        ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                        ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                        ->where('year', $realisasi->year)
                        ->whereBetween('month', [$realisasi->month, 12])
                        ->get();

                    foreach ($arrRealisasiNext as $realisasiNextMonth) {
                        $realisasiNextMonth->anggaran = $data['realisasi_anggaran'];
                        $realisasiNextMonth->save();
                    }
                    // Update Realisasi Next Month until December End

                    foreach ($data['rincian_belanja'] as $rincian) {
                        $realisasiRincian = RealisasiRincian::find($rincian['id']);
                        $realisasiRincian->anggaran = $rincian['realisasi_anggaran'];
                        $realisasiRincian->save();

                        foreach ($rincian['keterangan_rincian'] as $keterangan) {
                            $realisasiKeterangan = RealisasiKeterangan::find($keterangan['id']);
                            $koefisien = $keterangan['koefisien_realisasi'];
                            $koefisien = str_replace(',', '.', $koefisien);
                            $realisasiKeterangan->koefisien = $koefisien;
                            // $realisasiKeterangan->koefisien = $keterangan['koefisien_realisasi'];
                            $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_keterangan'];
                            $realisasiKeterangan->save();
                        }
                    }
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Realisasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function logsRealisasi($id, Request $request)
    {
        $return = [];
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }

        $dataStatus = RealisasiStatus::where('sub_kegiatan_id', $id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();
        if (!$dataStatus) {
            return $this->errorResponse('Data tidak ditemukan', 200);
        }

        $return['data_status'] = $dataStatus;
        $logs = DB::table('notes_realisasi')
            ->where('data_id', $dataStatus->id)
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($logs as $log) {
            $log->created_by_name = User::find($log->user_id)->fullname;
        }
        $return['logs'] = $logs;

        return $this->successResponse($return, 'Logs Realisasi');
    }

    function postLogsRealisasi($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|string',
            'message' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->status === 'sent') {
            DB::beginTransaction();
            try {
                $data = RealisasiStatus::where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->first();
                if (!$data) {
                    return $this->errorResponse('Data tidak ditemukan', 200);
                }
                if ($data->status == 'verified') {
                    return $this->errorResponse('Permintaan tidak dapat diteruskan. Dikarenakan telah Terverifikasi');
                }
                if ($data->status == 'waiting') {
                    return $this->errorResponse('Permintaan tidak dapat diteruskan. Dikarenakan sedang Menunggu Verifikasi');
                }
                $data->status = 'sent';
                $data->save();

                DB::table('notes_realisasi')->insert([
                    'data_id' => $data->id,
                    'user_id' => auth()->user()->id,
                    'status' => 'sent',
                    'type' => 'request',
                    'message' => $request->message,
                    'created_at' => now(),
                ]);

                DB::table('data_realisasi')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => 'sent']);

                DB::commit();
                return $this->successResponse(null, 'Permintaan Verifikasi berhasil dikirim');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
            }
        }

        if (($request->status !== 'sent') && ($request->status == 'verified' || $request->status == 'draft' || $request->status == 'reject' || $request->status == 'return' || $request->status == 'waiting')) {
            DB::beginTransaction();
            try {
                $data = RealisasiStatus::where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->first();
                if (!$data) {
                    return $this->errorResponse('Data tidak ditemukan', 200);
                }
                if ($data->status == $request->status) {
                    if ($data->stats == 'verified') {
                        return $this->errorResponse('Data telah diverifikasi');
                    }
                    if ($data->status == 'waiting') {
                        return $this->errorResponse('Data sedang menunggu verifikasi');
                    }
                    if ($data->status == 'sent') {
                        return $this->errorResponse('Data telah dikirim');
                    }
                    if ($data->status == 'draft') {
                        return $this->errorResponse('Data masih dalam draft');
                    }
                    if ($data->status == 'reject') {
                        return $this->errorResponse('Data telah ditolak');
                    }
                    if ($data->status == 'return') {
                        return $this->errorResponse('Data telah dikembalikan');
                    }
                }
                $data->status = $request->status;
                $data->save();

                DB::table('notes_realisasi')->insert([
                    'data_id' => $data->id,
                    'user_id' => auth()->user()->id,
                    'status' => $request->status,
                    'type' => 'return',
                    'message' => $request->message,
                    'created_at' => now(),
                ]);

                DB::table('data_realisasi')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => $request->status]);

                DB::commit();
                return $this->successResponse(null, 'Tanggapan berhasil dikirim');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
            }
        }
        return $this->errorResponse('Status tidak valid', 200);
    }
}
