<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Ref\Satuan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Data\TargetKinerja;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Data\TaggingSumberDana;
use App\Models\Data\TargetKinerjaStatus;
use App\Models\Data\TargetKinerjaRincian;
use App\Notifications\GlobalNotification;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetKinerjaKeterangan;
use Illuminate\Support\Facades\Notification;

class TargetKinerjaController extends Controller
{
    use JsonReturner;
    public $isAbleToInput = true;
    public $globalMessage = 'Sedang Dalam Perbaikan!';

    function detailTargetKinerja($id, Request $request)
    {
        $datas = [];
        $subKegiatan = SubKegiatan::find($id);

        // verifikator rules
        // $user = auth()->user();
        // $instanceIds = [];
        // if ($user->role_id == 6) {
        //     $Ids = DB::table('pivot_user_verificator_instances')
        //         ->where('user_id', $user->id)
        //         ->get();
        //     foreach ($Ids as $id) {
        //         $instanceIds[] = $id->instance_id;
        //     }
        // }

        // if ($user->role_id == 6 && !in_array($subKegiatan->instance_id, $instanceIds)) {
        //     return $this->errorResponse('Anda Bukan Ampuhan Sub Kegiatan ini!', 200);
        // }

        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }
        $TargetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();
        if (!$TargetKinerjaStatus) {
            $TargetKinerjaStatus = new TargetKinerjaStatus();
            $TargetKinerjaStatus->sub_kegiatan_id = $id;
            $TargetKinerjaStatus->month = $request->month;
            $TargetKinerjaStatus->year = $request->year;
            $TargetKinerjaStatus->status = 'draft';
            $TargetKinerjaStatus->status_leader = 'draft';
            $TargetKinerjaStatus->save();

            DB::table('notes_target_kinerja')->insert([
                'data_id' => $TargetKinerjaStatus->id,
                'user_id' => auth()->user()->id,
                'status' => 'draft',
                'type' => 'system',
                'message' => 'Data dibuat',
                'created_at' => now(),
            ]);
        }

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
            'status' => $TargetKinerjaStatus->status ?? 'draft',
            'status_leader' => $TargetKinerjaStatus->status_leader ?? 'draft',
            'tag_sumber_dana' => $tagSumberDana,
        ];
        $datas['data'] = [];

        $arrKodeRekSelected = TargetKinerja::select('kode_rekening_id')
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('sub_kegiatan_id', $id)
            ->groupBy('kode_rekening_id')
            ->get();

        if ($arrKodeRekSelected->count() === 0) {
            $datas['data'][] = [
                'editable' => false,
                'long' => true,
                'type' => 'rekening',
                'id' => null,
                'parent_id' => null,
                'uraian' => 'Sub Kegiatan ini Belum Memiliki Data Rekap Versi 5',
                'fullcode' => null,
                'pagu' => 0,
                'rincian_belanja' => [],
            ];
            $datas['data_error'] = true;
            $datas['error_message'] = 'Sub Kegiatan ini Belum Memiliki Data Rekap Versi 5';
            return $this->successResponse($datas, 'detail target kinerja');
        }

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

        // Level 1
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

            $datas['data'][] = [
                'editable' => false,
                'long' => true,
                'type' => 'rekening',
                'rekening' => '1',
                'id' => $akun->id,
                'parent_id' => null,
                'uraian' => $akun->name,
                'fullcode' => $akun->fullcode,
                'pagu' => $paguSipd,
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

                $datas['data'][] = [
                    'editable' => false,
                    'long' => true,
                    'type' => 'rekening',
                    'rekening' => '2',
                    'id' => $kelompok->id,
                    'parent_id' => $akun->id,
                    'uraian' => $kelompok->name,
                    'fullcode' => $kelompok->fullcode,
                    'pagu' => $paguSipd,
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

                    $datas['data'][] = [
                        'editable' => false,
                        'long' => true,
                        'type' => 'rekening',
                        'rekening' => '3',
                        'id' => $jenis->id,
                        'parent_id' => $kelompok->id,
                        'uraian' => $jenis->name,
                        'fullcode' => $jenis->fullcode,
                        'pagu' => $paguSipd,
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

                        $datas['data'][] = [
                            'editable' => false,
                            'long' => true,
                            'type' => 'rekening',
                            'rekening' => '4',
                            'id' => $objek->id,
                            'parent_id' => $jenis->id,
                            'uraian' => $objek->name,
                            'fullcode' => $objek->fullcode,
                            'pagu' => $paguSipd,
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

                            $datas['data'][] = [
                                'editable' => false,
                                'long' => true,
                                'type' => 'rekening',
                                'rekening' => '5',
                                'id' => $rincian->id,
                                'parent_id' => $objek->id,
                                'uraian' => $rincian->name,
                                'fullcode' => $rincian->fullcode,
                                'pagu' => $paguSipd,
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
                                    $arrTargetKinerja[] = [
                                        'editable' => true,
                                        'long' => true,
                                        'type' => 'target-kinerja',
                                        'id_rek_1' => $akun->id,
                                        'id_rek_2' => $kelompok->id,
                                        'id_rek_3' => $jenis->id,
                                        'id_rek_4' => $objek->id,
                                        'id_rek_5' => $rincian->id,
                                        'id_rek_6' => $rekening->id,
                                        'id' => $dataTarget->id,
                                        'year' => $dataTarget->year,
                                        'jenis' => $dataTarget->type,
                                        'sumber_dana_id' => $dataTarget->sumber_dana_id,
                                        'sumber_dana_fullcode' => $dataTarget->SumberDana->fullcode ?? null,
                                        'sumber_dana_name' => $dataTarget->SumberDana->name ?? null,
                                        'nama_paket' => $dataTarget->nama_paket,
                                        'pagu' => $dataTarget->pagu_sipd,
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

                                $datas['data'][] = [
                                    'editable' => false,
                                    'long' => true,
                                    'type' => 'rekening',
                                    'rekening' => '6',
                                    'id' => $rekening->id,

                                    'id_rek_1' => $akun->id,
                                    'id_rek_2' => $kelompok->id,
                                    'id_rek_3' => $jenis->id,
                                    'id_rek_4' => $objek->id,
                                    'id_rek_5' => $rincian->id,
                                    'id_rek_6' => $rekening->id,

                                    'parent_id' => $rincian->id,
                                    'uraian' => $rekening->name,
                                    'fullcode' => $rekening->fullcode,
                                    'pagu' => $arrDataTarget->sum('pagu_sipd'), // Tarik dari Data Rekening
                                    'rincian_belanja' => [],
                                ];

                                foreach ($arrTargetKinerja as $targetKinerja) {
                                    $datas['data'][] = $targetKinerja;
                                    $arrRincianBelanja = [];
                                    $arrRincianBelanja = TargetKinerjaRincian::where('target_kinerja_id', $targetKinerja['id'])
                                        ->get();
                                    foreach ($arrRincianBelanja as $keyRincianBelanja => $rincianBelanja) {
                                        $datas['data'][count($datas['data']) - 1]['rincian_belanja'][$keyRincianBelanja] = [
                                            'editable' => true,
                                            'long' => true,
                                            'type' => 'rincian-belanja',
                                            'id' => $rincianBelanja->id,
                                            'target_kinerja_id' => $rincianBelanja->target_kinerja_id,
                                            'title' => $rincianBelanja->title,
                                            'pagu' => (int)$rincianBelanja->pagu_sipd,
                                            'year' => $rincianBelanja->year,
                                            'month' => $rincianBelanja->month,
                                            'keterangan_rincian' => [],
                                        ];

                                        $arrKeterangan = TargetKinerjaKeterangan::where('parent_id', $rincianBelanja->id)->get();
                                        foreach ($arrKeterangan as $keterangan) {
                                            $datas['data'][count($datas['data']) - 1]['rincian_belanja'][$keyRincianBelanja]['keterangan_rincian'][] = [
                                                'editable' => true,
                                                'long' => false,
                                                'type' => 'keterangan-rincian',
                                                'id' => $keterangan->id,
                                                'target_kinerja_id' => $keterangan->target_kinerja_id,
                                                'title' => $keterangan->title,
                                                'year' => $keterangan->year,
                                                'month' => $keterangan->month,

                                                'koefisien' => $keterangan->koefisien,
                                                'satuan_id' => $keterangan->satuan_id,
                                                'satuan_name' => $keterangan->satuan_name,
                                                'harga_satuan' => $keterangan->harga_satuan,
                                                'ppn' => $keterangan->ppn,
                                                'pagu' => (int)$keterangan->pagu,
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

        return $this->successResponse($datas, 'detail target kinerja');
    }

    function saveTargetKinerja($id, Request $request)
    {
        // return $request->all();
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

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
                if (
                    $data['type'] == 'target-kinerja'
                    && $data['editable'] == true
                    // && count($data['rincian_belanja']) == 0
                ) {
                    $targetKinerja = TargetKinerja::find($data['id']);
                    if ($targetKinerja) {
                        $targetKinerja->nama_paket = $data['nama_paket'];
                        $targetKinerja->pagu_sipd = $data['pagu'];
                        // $targetKinerja->updated_by = auth()->id() ?? 6;
                        $targetKinerja->save();
                    }
                }

                if ($data['type'] == 'target-kinerja' && !$data['id'] && $data['is_new'] == true) {
                    $targetKinerja = new TargetKinerja();
                    $targetKinerja->periode_id = $request->periode;
                    $targetKinerja->year = $request->year;
                    $targetKinerja->month = $request->month;
                    $targetKinerja->instance_id = $subKegiatan->instance_id;
                    $targetKinerja->urusan_id = $subKegiatan->urusan_id;
                    $targetKinerja->bidang_urusan_id = $subKegiatan->bidang_id;
                    $targetKinerja->program_id = $subKegiatan->program_id;
                    $targetKinerja->kegiatan_id = $subKegiatan->kegiatan_id;
                    $targetKinerja->sub_kegiatan_id = $subKegiatan->id;
                    $targetKinerja->kode_rekening_id = $data['id_rek_6'];
                    $targetKinerja->nama_paket = $data['nama_paket'];
                    $targetKinerja->sumber_dana_id = $data['sumber_dana_id'];
                    $targetKinerja->type = $data['jenis'] ?? "Manual";
                    $targetKinerja->pagu_sipd = $data['pagu'];
                    $targetKinerja->is_detail = $data['is_detail'];
                    $targetKinerja->status = 'draft';
                    $targetKinerja->status_leader = 'draft';
                    $targetKinerja->created_by = auth()->id() ?? 6;
                    $targetKinerja->save();
                }

                if (count($data['rincian_belanja']) > 0) {
                    $targetKinerja = TargetKinerja::find($data['id']);
                    foreach ($data['rincian_belanja'] as $rincian) {

                        $rincianBelanja = TargetKinerjaRincian::find($rincian['id']);
                        if (!$rincianBelanja) {
                            $rincianBelanja = new TargetKinerjaRincian();
                            $rincianBelanja->periode_id = $targetKinerja->periode_id;
                            $rincianBelanja->target_kinerja_id = $targetKinerja['id'];
                            $rincianBelanja->urusan_id = $targetKinerja->urusan_id;
                            $rincianBelanja->bidang_urusan_id = $targetKinerja->bidang_urusan_id;
                            $rincianBelanja->program_id = $targetKinerja->program_id;
                            $rincianBelanja->kegiatan_id = $targetKinerja->kegiatan_id;
                            $rincianBelanja->sub_kegiatan_id = $targetKinerja->sub_kegiatan_id;
                            $rincianBelanja->kode_rekening_id = $targetKinerja->kode_rekening_id;
                            $rincianBelanja->sumber_dana_id = $targetKinerja->sumber_dana_id;
                            $rincianBelanja->created_by = auth()->user()->id;
                        } else {
                            // $rincianBelanja->updated_by = auth()->id() ?? 6;
                        }
                        $rincianBelanja->pagu_sipd = $rincian['pagu'] ?? 0;
                        $rincianBelanja->title = $rincian['title'];
                        $rincianBelanja->year = $rincian['year'] ?? $request->year;
                        $rincianBelanja->month = $rincian['month'] ?? $request->month;
                        $rincianBelanja->save();
                    }

                    if (count($rincian['keterangan_rincian']) > 0) {
                        foreach ($rincian['keterangan_rincian'] as $keterangan) {
                            if ($keterangan['id'] !== null) {
                                $rincianKeterangan = TargetKinerjaKeterangan::find($keterangan['id']);
                                // $rincianKeterangan->updated_by = auth()->user()->id;
                            } elseif ($keterangan['id'] === null) {
                                $rincianKeterangan = new TargetKinerjaKeterangan();
                                $rincianKeterangan->periode_id = $targetKinerja->periode_id;
                                $rincianKeterangan->parent_id = $rincianBelanja->id;
                                $rincianKeterangan->target_kinerja_id = $targetKinerja['id'];
                                $rincianKeterangan->created_by = auth()->user()->id;
                                $rincianKeterangan->year = $keterangan['year'] ?? $request->year;
                                $rincianKeterangan->month = $keterangan['month'] ?? $request->month;
                                $rincianKeterangan->urusan_id = $targetKinerja->urusan_id;
                                $rincianKeterangan->bidang_urusan_id = $targetKinerja->bidang_urusan_id;
                                $rincianKeterangan->program_id = $targetKinerja->program_id;
                                $rincianKeterangan->kegiatan_id = $targetKinerja->kegiatan_id;
                                $rincianKeterangan->sub_kegiatan_id = $targetKinerja->sub_kegiatan_id;
                                $rincianKeterangan->kode_rekening_id = $targetKinerja->kode_rekening_id;
                                $rincianKeterangan->sumber_dana_id = $targetKinerja->sumber_dana_id;
                            }
                            $rincianKeterangan->title = $keterangan['title'];
                            $rincianKeterangan->koefisien = $keterangan['koefisien'];
                            if ($keterangan['satuan_id'] === 0) {
                                $newSatuan = Satuan::where('name', $keterangan['satuan_name'])->first();
                                if (!$newSatuan) {
                                    $newSatuan = new Satuan();
                                    $newSatuan->name = $keterangan['satuan_name'];
                                    $newSatuan->status = 'active';
                                    $newSatuan->created_by = auth()->user()->id;
                                    $newSatuan->save();
                                }
                                $rincianKeterangan->satuan_id = $newSatuan->id;
                                $rincianKeterangan->satuan_name = $newSatuan->name;
                            } else {
                                $rincianKeterangan->satuan_id = $keterangan['satuan_id'];
                                if ($keterangan['satuan_id']) {
                                    $rincianKeterangan->satuan_name = Satuan::find($keterangan['satuan_id'])->name;
                                }
                            }
                            $rincianKeterangan->harga_satuan = $keterangan['harga_satuan'];
                            $rincianKeterangan->ppn = $keterangan['ppn'] ?? 0;
                            $rincianKeterangan->pagu = $keterangan['pagu'] ?? 0;
                            $rincianKeterangan->save();
                        }
                        $rincianBelanja->pagu_sipd = $rincianBelanja->Keterangan->sum('pagu');
                        $rincianBelanja->save();
                    }


                    // Update to next month until December Start
                    $currentMonth = $request->month + 1;
                    $maxMonth = 12;
                    for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                        $nextTargetKinerja = TargetKinerja::where('periode_id', $targetKinerja->periode_id)
                            ->where('year', $request->year)
                            ->where('month', $i)
                            ->where('instance_id', $targetKinerja->instance_id)
                            ->where('urusan_id', $targetKinerja->urusan_id)
                            ->where('bidang_urusan_id', $targetKinerja->bidang_urusan_id)
                            ->where('program_id', $targetKinerja->program_id)
                            ->where('kegiatan_id', $targetKinerja->kegiatan_id)
                            ->where('sub_kegiatan_id', $targetKinerja->sub_kegiatan_id)
                            ->where('kode_rekening_id', $targetKinerja->kode_rekening_id)
                            ->where('sumber_dana_id', $targetKinerja->sumber_dana_id)
                            ->where('type', $targetKinerja->type)
                            ->where('is_detail', $targetKinerja->is_detail)
                            ->where('nama_paket', $targetKinerja->nama_paket)
                            ->first();
                        if ($nextTargetKinerja) {
                            $nextRincianBelanja = TargetKinerjaRincian::where('periode_id', $nextTargetKinerja->periode_id)
                                ->where('urusan_id', $nextTargetKinerja->urusan_id)
                                ->where('bidang_urusan_id', $nextTargetKinerja->bidang_urusan_id)
                                ->where('program_id', $nextTargetKinerja->program_id)
                                ->where('kegiatan_id', $nextTargetKinerja->kegiatan_id)
                                ->where('sub_kegiatan_id', $nextTargetKinerja->sub_kegiatan_id)
                                ->where('kode_rekening_id', $nextTargetKinerja->kode_rekening_id)
                                ->where('sumber_dana_id', $nextTargetKinerja->sumber_dana_id)
                                ->where('title', $rincian['title'])
                                ->where('year', $request->year)
                                ->where('month', $i)
                                ->first();

                            if (!$nextRincianBelanja) {
                                $nextRincianBelanja = new TargetKinerjaRincian();
                                $nextRincianBelanja->periode_id = $rincianBelanja->periode_id;
                                $nextRincianBelanja->target_kinerja_id = $nextTargetKinerja->id;
                                $nextRincianBelanja->urusan_id = $rincianBelanja->urusan_id;
                                $nextRincianBelanja->bidang_urusan_id = $rincianBelanja->bidang_urusan_id;
                                $nextRincianBelanja->program_id = $rincianBelanja->program_id;
                                $nextRincianBelanja->kegiatan_id = $rincianBelanja->kegiatan_id;
                                $nextRincianBelanja->sub_kegiatan_id = $rincianBelanja->sub_kegiatan_id;
                                $nextRincianBelanja->kode_rekening_id = $rincianBelanja->kode_rekening_id;
                                $nextRincianBelanja->sumber_dana_id = $rincianBelanja->sumber_dana_id;
                                $nextRincianBelanja->title = $rincian['title'];
                                $nextRincianBelanja->year = $request->year;
                                $nextRincianBelanja->month = $i;
                                $nextRincianBelanja->created_by = auth()->user()->id;
                            } else {
                                // $nextRincianBelanja->updated_by = auth()->id() ?? 6;
                            }
                            $nextRincianBelanja->pagu_sipd = $rincianBelanja->pagu_sipd;
                            $nextRincianBelanja->save();

                            if (count($rincian['keterangan_rincian']) > 0) {
                                foreach ($rincian['keterangan_rincian'] as $keterangan) {
                                    $nextRincianKeterangan = TargetKinerjaKeterangan::where('periode_id', $nextRincianBelanja->periode_id)
                                        ->where('parent_id', $nextRincianBelanja->id)
                                        ->where('target_kinerja_id', $nextTargetKinerja->id)
                                        ->where('urusan_id', $nextRincianBelanja->urusan_id)
                                        ->where('bidang_urusan_id', $nextRincianBelanja->bidang_urusan_id)
                                        ->where('program_id', $nextRincianBelanja->program_id)
                                        ->where('kegiatan_id', $nextRincianBelanja->kegiatan_id)
                                        ->where('sub_kegiatan_id', $nextRincianBelanja->sub_kegiatan_id)
                                        ->where('kode_rekening_id', $nextRincianBelanja->kode_rekening_id)
                                        ->where('sumber_dana_id', $nextRincianBelanja->sumber_dana_id)
                                        ->where('year', $request->year)
                                        ->where('month', $i)
                                        ->first();
                                    if (!$nextRincianKeterangan) {
                                        $nextRincianKeterangan = new TargetKinerjaKeterangan();
                                        $nextRincianKeterangan->periode_id = $nextRincianBelanja->periode_id;
                                        $nextRincianKeterangan->parent_id = $nextRincianBelanja->id;
                                        $nextRincianKeterangan->target_kinerja_id = $nextTargetKinerja->id;
                                        $nextRincianKeterangan->created_by = auth()->user()->id;
                                        $nextRincianKeterangan->year = $request->year;
                                        $nextRincianKeterangan->month = $i;
                                        $nextRincianKeterangan->urusan_id = $nextRincianBelanja->urusan_id;
                                        $nextRincianKeterangan->bidang_urusan_id = $nextRincianBelanja->bidang_urusan_id;
                                        $nextRincianKeterangan->program_id = $nextRincianBelanja->program_id;
                                        $nextRincianKeterangan->kegiatan_id = $nextRincianBelanja->kegiatan_id;
                                        $nextRincianKeterangan->sub_kegiatan_id = $nextRincianBelanja->sub_kegiatan_id;
                                        $nextRincianKeterangan->kode_rekening_id = $nextRincianBelanja->kode_rekening_id;
                                        $nextRincianKeterangan->sumber_dana_id = $nextRincianBelanja->sumber_dana_id;
                                    } else {
                                        // $nextRincianKeterangan->updated_by = auth()->id() ?? 6;
                                    }
                                    $nextRincianKeterangan->title = $keterangan['title'];
                                    $nextRincianKeterangan->koefisien = $keterangan['koefisien'];
                                    if ($keterangan['satuan_id'] === 0) {
                                        $newSatuan = Satuan::where('name', $keterangan['satuan_name'])->first();
                                        if (!$newSatuan) {
                                            $newSatuan = new Satuan();
                                            $newSatuan->name = $keterangan['satuan_name'];
                                            $newSatuan->status = 'active';
                                            $newSatuan->created_by = auth()->user()->id;
                                            $newSatuan->save();
                                        }
                                        $nextRincianKeterangan->satuan_id = $newSatuan->id;
                                        $nextRincianKeterangan->satuan_name = $newSatuan->name;
                                    } else {
                                        $nextRincianKeterangan->satuan_id = $keterangan['satuan_id'];
                                        if ($keterangan['satuan_id']) {
                                            $nextRincianKeterangan->satuan_name = Satuan::find($keterangan['satuan_id'])->name;
                                        }
                                    }
                                    $nextRincianKeterangan->harga_satuan = $keterangan['harga_satuan'];
                                    $nextRincianKeterangan->ppn = $keterangan['ppn'] ?? 0;
                                    $nextRincianKeterangan->pagu = $keterangan['pagu'] ?? 0;
                                    $nextRincianKeterangan->save();
                                }
                            }
                        }
                    }
                    // Update to next month until December End
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
        }
    }

    function deleteTargetKinerja($id, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $data = TargetKinerja::where('id', $id)->where('type', 'Manual')->first();
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 200);
        }

        DB::beginTransaction();
        try {
            $data->deleted_by = auth()->id() ?? 6;
            $data->timestamps = false;
            $data->save();
            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Rincian Belanja Berhasil Dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
        }
    }

    function deleteRincian($id, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $currentMonth = $request->month + 1;
        $maxMonth = 12;

        $data = TargetKinerjaRincian::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 200);
        }

        DB::beginTransaction();
        try {
            $parentData = TargetKinerja::find($data->target_kinerja_id);

            $arrParentData = TargetKinerja::where('periode_id', $parentData->periode_id)
                ->where('year', $parentData->year)
                ->whereBetween('month', [$currentMonth, $maxMonth])
                ->where('instance_id', $parentData->instance_id)
                ->where('urusan_id', $parentData->urusan_id)
                ->where('bidang_urusan_id', $parentData->bidang_urusan_id)
                ->where('program_id', $parentData->program_id)
                ->where('kegiatan_id', $parentData->kegiatan_id)
                ->where('sub_kegiatan_id', $parentData->sub_kegiatan_id)
                ->where('kode_rekening_id', $parentData->kode_rekening_id)
                ->where('sumber_dana_id', $parentData->sumber_dana_id)
                ->where('type', $parentData->type)
                ->where('is_detail', $parentData->is_detail)
                ->where('nama_paket', $parentData->nama_paket)
                ->orderBy('month')
                ->get();


            foreach ($arrParentData as $parentData) {
                $parentDataRincian = TargetKinerjaRincian::where('target_kinerja_id', $parentData->id)
                    ->where('title', $data->title)
                    ->first();
                if ($parentDataRincian) {
                    $parentDataRincian->delete();
                    $arrParentDataKeterangan = TargetKinerjaKeterangan::where('parent_id', $parentDataRincian->id)
                        ->get();
                    foreach ($arrParentDataKeterangan as $parentDataKeterangan) {
                        $parentDataKeterangan->delete();
                    }
                }
            }

            $data->delete();
            TargetKinerjaKeterangan::where('parent_id', $id)->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
        }
    }

    function logsTargetKinerja($id, Request $request)
    {
        $return = [];
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }
        $targetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();
        if (!$targetKinerjaStatus) {
            return $this->errorResponse('Data tidak ditemukan', 200);
        }

        $return['data_status'] = $targetKinerjaStatus;
        $logs = DB::table('notes_target_kinerja')
            ->where('data_id', $targetKinerjaStatus->id)
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($logs as $log) {
            $log->created_by_name = User::find($log->user_id)->fullname;
        }
        $return['logs'] = $logs;

        return $this->successResponse($return, 'Logs Target Kinerja');
    }

    function postLogsTargetKinerja($id, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $validate = Validator::make($request->all(), [
            'status' => 'required|string',
            'message' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->status === 'sent' || $request->status == 'draft') {
            DB::beginTransaction();
            try {
                $data = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
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

                $note = DB::table('notes_target_kinerja')->insert([
                    'data_id' => $data->id,
                    'user_id' => auth()->user()->id,
                    'status' => 'sent',
                    'type' => 'request',
                    'message' => $request->message,
                    'created_at' => now(),
                ]);

                DB::table('data_target_kinerja')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => 'sent']);

                // send notification
                // $users = User::where('role_id', 9)
                //     ->where('instance_id', $data->SubKegiatan->instance_id)
                //     ->get();
                $users = User::whereIn('role_id', [6])
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $data->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/kinerja/target/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $data->subKegiatan->periode_id,
                    'Permintaan Verifikasi Rincian Target',
                    auth()->user()->fullname . ' : ' . $request->message,
                    [
                        'method' => 'sent',
                        'uri' => '/kinerja/target/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $data->subKegiatan->periode_id,
                        'modelId' => $id,
                        'month' => $request->month,
                        'year' => $request->year,
                    ]
                ));

                DB::commit();
                return $this->successResponse(null, 'Permintaan Verifikasi berhasil dikirim');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
            }
        }

        if (($request->status !== 'sent') && ($request->status == 'verified' || $request->status == 'reject' || $request->status == 'return' || $request->status == 'waiting')) {
            DB::beginTransaction();
            try {
                $data = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
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

                $note = DB::table('notes_target_kinerja')->insert([
                    'data_id' => $data->id,
                    'user_id' => auth()->user()->id,
                    'status' => $request->status,
                    'type' => 'return',
                    'message' => $request->message,
                    'created_at' => now(),
                ]);

                DB::table('data_target_kinerja')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => $request->status]);

                // send notification
                $users = User::where('role_id', 9)
                    ->where('instance_id', $data->SubKegiatan->instance_id)
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'return',
                    $data->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/kinerja/target/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $data->subKegiatan->periode_id,
                    'Verifikasi Rincian Target',
                    auth()->user()->fullname . ' : ' . $request->message,
                    [
                        'method' => 'return',
                        'uri' => '/kinerja/target/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $data->subKegiatan->periode_id,
                        'modelId' => $id,
                        'month' => $request->month,
                        'year' => $request->year,
                    ]
                ));

                DB::commit();
                return $this->successResponse(null, 'Tanggapan berhasil dikirim');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
            }
        }
    }
}
