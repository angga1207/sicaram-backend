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
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetKinerjaKeterangan;

class TargetKinerjaController extends Controller
{
    use JsonReturner;

    function detailTargetKinerja($id, Request $request)
    {
        $datas = [];
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }
        $TargetKinerjaStatus = TargetKinerjaStatus::where('sub_kegiatan_id', $id)
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
                                    'id' => $rekening->id,
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
        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }

        DB::beginTransaction();
        try {
            $datas = collect($request->data)
                ->where('type', 'target-kinerja')
                ->values();
            // $datas = collect($request->data);
            // return $datas;
            $return = null;
            foreach ($datas as $data) {
                if (count($data['rincian_belanja']) > 0) {
                    $targetKinerja = TargetKinerja::find($data['id']);
                    foreach ($data['rincian_belanja'] as $rincian) {
                        if ($rincian['id'] !== null) {
                            $rincianBelanja = TargetKinerjaRincian::find($rincian['id']);
                            $rincianBelanja->updated_by = auth()->user()->id;
                        } elseif ($rincian['id'] === null) {
                            $rincianBelanja = new TargetKinerjaRincian();
                            $rincianBelanja->periode_id = $targetKinerja->periode_id;
                            $rincianBelanja->target_kinerja_id = $data['id'];
                            $rincianBelanja->created_by = auth()->user()->id;
                        }
                        $rincianBelanja->pagu_sipd = $rincian['pagu'] ?? 0;
                        $rincianBelanja->title = $rincian['title'];
                        $rincianBelanja->save();

                        if (count($rincian['keterangan_rincian']) > 0) {
                            foreach ($rincian['keterangan_rincian'] as $keterangan) {
                                if ($keterangan['id'] !== null) {
                                    $rincianKeterangan = TargetKinerjaKeterangan::find($keterangan['id']);
                                    $rincianKeterangan->updated_by = auth()->user()->id;
                                } elseif ($keterangan['id'] === null) {
                                    $rincianKeterangan = new TargetKinerjaKeterangan();
                                    $rincianKeterangan->periode_id = $targetKinerja->periode_id;
                                    $rincianKeterangan->parent_id = $rincianBelanja->id;
                                    $rincianKeterangan->target_kinerja_id = $data['id'];
                                    $rincianKeterangan->created_by = auth()->user()->id;
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
                        }
                        $rincianBelanja->pagu_sipd = $rincianBelanja->Keterangan->sum('pagu');
                        $rincianBelanja->save();
                    }

                    TargetKinerjaRincian::where('target_kinerja_id', $data['id'])
                        ->whereNotIn('id', collect($data['rincian_belanja'])->pluck('id'))
                        ->delete();
                    TargetKinerjaKeterangan::where('target_kinerja_id', $data['id'])
                        ->whereNotIn('parent_id', collect($data['rincian_belanja'])->pluck('id'))
                        ->delete();
                }
                if (count($data['rincian_belanja']) === 0) {
                    TargetKinerjaRincian::where('target_kinerja_id', $data['id'])->delete();
                    TargetKinerjaKeterangan::where('target_kinerja_id', $data['id'])->delete();
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Data berhasil disimpan');
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

                DB::commit();
                return $this->successResponse(null, 'Tanggapan berhasil dikirim');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine(), 500);
            }
        }
    }
}
