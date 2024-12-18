<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Instance;
use App\Models\Caram\Apbd;
use App\Models\Ref\Satuan;
use App\Models\Caram\Renja;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Renstra;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Data\TargetKinerja;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Data\RealisasiStatus;
use Illuminate\Support\Facades\Http;
use App\Models\Caram\ApbdSubKegiatan;
use App\Models\Data\RealisasiRincian;
use App\Models\Caram\RenjaSubKegiatan;
use App\Models\Data\TaggingSumberDana;
use App\Models\Caram\RenstraSubKegiatan;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\TargetKinerjaStatus;
use App\Models\Ref\IndikatorSubKegiatan;
use App\Models\Data\RealisasiSubKegiatan;
use App\Models\Data\TargetKinerjaRincian;
use App\Notifications\GlobalNotification;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetKinerjaKeterangan;
use Illuminate\Support\Facades\Notification;
use App\Models\Data\RealisasiSubKegiatanFiles;
use App\Models\Data\RealisasiSubKegiatanKontrak;
use App\Models\Data\RealisasiSubKegiatanKeterangan;

class RealisasiController extends Controller
{
    use JsonReturner;
    public $isAbleToInput = true;
    public $globalMessage = 'Sedang Dalam Perbaikan!';

    function listInstance(Request $request)
    {
        try {
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
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage());
        }
    }

    function listProgramsSubKegiatan(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'instance_id' => 'required|exists:instances,id',
            ], [], [
                'instance_id' => 'Instance ID',
            ]);

            if ($validate->fails()) {
                return $this->errorResponse($validate->errors());
            }

            $instance = Instance::find($request->instance_id);

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

            if ($user->role_id == 6 && !in_array($instance->id, $instanceIds)) {
                return $this->errorResponse('Perangkat Daerah Tidak Ditemukan');
            }

            if ($instance) {
                $programs = $instance
                    ->Programs
                    ->sortBy('fullcode');
                if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                    $progs = auth()->user()->MyPermissions()->pluck('program_id');
                    $progs = collect($progs)->unique()->values();
                    $programs = $instance
                        ->Programs
                        ->whereIn('id', $progs)
                        ->sortBy('fullcode');
                }
                $datas = [];
                foreach ($programs as $program) {
                    $kegiatans = $program
                        ->Kegiatans
                        ->sortBy('code_1')
                        ->sortBy('code_2');

                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                        $kegs = collect($kegs)->unique()->values();
                        $kegiatans = $program
                            ->Kegiatans
                            ->whereIn('id', $kegs)
                            ->sortBy('code_1')
                            ->sortBy('code_2');
                    }
                    $kegiatanDatas = [];
                    foreach ($kegiatans as $kegiatan) {
                        $subKegiatans = $kegiatan
                            ->SubKegiatans
                            ->sortBy('code');

                        if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                            $subKegs = auth()->user()->MyPermissions()->pluck('sub_kegiatan_id');
                            $subKegs = collect($subKegs)->unique()->values();
                            $subKegiatans = $kegiatan
                                ->SubKegiatans
                                ->whereIn('id', $subKegs)
                                ->sortBy('code');
                        }
                        $subKegiatanDatas = [];
                        foreach ($subKegiatans as $subKegiatan) {
                            $renstraSub = Renstra::where('program_id', $subKegiatan->program_id)
                                // ->where('year', $request->year)
                                ->first();
                            $renjaSub = Renja::where('program_id', $subKegiatan->program_id)
                                // ->where('year', $request->year)
                                ->first();
                            $apbdSub = Apbd::where('program_id', $subKegiatan->program_id)
                                // ->where('year', $request->year)
                                ->first();
                            $targetSub = TargetKinerjaStatus::where('sub_kegiatan_id', $subKegiatan->id)
                                ->where('year', $request->year)
                                ->where('month', 1)
                                ->first();
                            $subKegiatanDatas[] = [
                                'id' => $subKegiatan->id,
                                'name' => $subKegiatan->name,
                                'fullcode' => $subKegiatan->fullcode,
                                'description' => $subKegiatan->description,
                                'status' => $subKegiatan->status,
                                // 'renstra_status' => $renstraSub ? $renstraSub->status : null, // 9 JULI 2024 LOSS RENJA VERIFIKASI
                                // 'renja_status' => $renjaSub ? $renjaSub->status : 'verified', // 9 JULI 2024 LOSS RENJA VERIFIKASI
                                'renstra_status' => 'verified',
                                'renja_status' => 'verified', // 9 JULI 2024 LOSS RENJA VERIFIKASI
                                'apbd_status' => $apbdSub ? $apbdSub->status : null,
                                'target_status' => $targetSub ? $targetSub->status : 'draft',
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
                return $this->errorResponse('Perangkat Daerah Tidak Ditemukan');
            }
        } catch (\Exception $e) {
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
        }
    }

    function detailRealisasi($id, Request $request)
    {
        try {
            $datas = [];
            $subKegiatan = SubKegiatan::find($id);

            // verifikator rules
            $user = auth()->user();
            $instanceIds = [];
            if ($user->role_id == 6) {
                $Ids = DB::table('pivot_user_verificator_instances')
                    ->where('user_id', $user->id)
                    ->get();
                foreach ($Ids as $insID) {
                    $instanceIds[] = $insID->instance_id;
                }
            }

            if ($user->role_id == 6 && !in_array($subKegiatan->instance_id, $instanceIds)) {
                return $this->errorResponse('Anda Bukan Ampuhan Sub Kegiatan ini!', 200);
            }

            if (!$subKegiatan) {
                return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
            }

            $RealisasiStatus = RealisasiStatus::where('sub_kegiatan_id', $id)
                ->where('year', $request->year)
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

            $RealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $id)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->first();
            if (!$RealisasiSubKegiatan) {
                $RealisasiSubKegiatan = new RealisasiSubKegiatan();
                $RealisasiSubKegiatan->instance_id = $subKegiatan->instance_id;
                $RealisasiSubKegiatan->periode_id = $subKegiatan->periode_id;
                $RealisasiSubKegiatan->periode_id = $request->periode;
                $RealisasiSubKegiatan->year = $request->year;
                $RealisasiSubKegiatan->month = $request->month;
                $RealisasiSubKegiatan->urusan_id = $subKegiatan->urusan_id;
                $RealisasiSubKegiatan->bidang_urusan_id = $subKegiatan->bidang_id;
                $RealisasiSubKegiatan->program_id = $subKegiatan->program_id;
                $RealisasiSubKegiatan->kegiatan_id = $subKegiatan->kegiatan_id;
                $RealisasiSubKegiatan->sub_kegiatan_id = $id;
                $RealisasiSubKegiatan->status = 'draft';
                $RealisasiSubKegiatan->status_leader = 'draft';
                $RealisasiSubKegiatan->save();
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
                'instance_code' => $subKegiatan->Instance->code ?? 'Tidak Diketahui',
                'status' => $RealisasiStatus->status,
                'status_leader' => $RealisasiStatus->status_leader,
                'status_target' => $TargetKinerjaStatus->status,
                'tag_sumber_dana' => $tagSumberDana,
            ];

            $apbdSubKegiatan = $this->_GetDataAPBDSubKegiatan($id, $request->year, $request->month, $subKegiatan->instance_id);
            $datas['dataRincian'] = [
                'urusan_code' => $subKegiatan->Urusan->fullcode,
                'urusan_name' => $subKegiatan->Urusan->name,
                'bidang_urusan_code' => $subKegiatan->Bidang->fullcode,
                'bidang_urusan_name' => $subKegiatan->Bidang->name,
                'instance_code' => $subKegiatan->Instance->code,
                'instance_name' => $subKegiatan->Instance->name,
                'program_code' => $subKegiatan->Program->fullcode,
                'program_name' => $subKegiatan->Program->name,
                'kegiatan_code' => $subKegiatan->Kegiatan->fullcode,
                'kegiatan_name' => $subKegiatan->Kegiatan->name,
                'sub_kegiatan_code' => $subKegiatan->fullcode,
                'sub_kegiatan_name' => $subKegiatan->name,
                'indicators' => $apbdSubKegiatan,
            ];

            $datas['realisasiSubKegiatan'] = $RealisasiSubKegiatan;

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

                $arrRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->where('sub_kegiatan_id', $id)
                    ->get();
                $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');
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
                    'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                    'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
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

                    $arrRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                        ->where('year', $request->year)
                        ->where('month', $request->month)
                        ->where('sub_kegiatan_id', $id)
                        ->get();
                    $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                    $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');
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
                        'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                        'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
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

                        $arrRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                            ->where('year', $request->year)
                            ->where('month', $request->month)
                            ->where('sub_kegiatan_id', $id)
                            ->get();
                        $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                        $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');
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
                            'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                            'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
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

                            $arrRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                ->where('year', $request->year)
                                ->where('month', $request->month)
                                ->where('sub_kegiatan_id', $id)
                                ->get();
                            $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                            $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');
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
                                'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                                'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
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

                                $arrRealisasiAnggaran = Realisasi::whereIn('kode_rekening_id', $arrKodeRekenings->pluck('id'))
                                    ->where('year', $request->year)
                                    ->where('month', $request->month)
                                    ->where('sub_kegiatan_id', $id)
                                    ->get();
                                $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                                $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');
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
                                    'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                                    'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
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
                                            // 'realisasi_anggaran' => (int)$dataRealisasi->anggaran,
                                            'realisasi_anggaran' => (int)$dataRealisasi->anggaran - (int)$dataRealisasi->anggaran_bulan_ini,
                                            'realisasi_anggaran_bulan_ini' => (int)$dataRealisasi->anggaran_bulan_ini,
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

                                    $arrRealisasiAnggaran = Realisasi::where('kode_rekening_id', $rekening->id)
                                        ->where('year', $request->year)
                                        ->where('month', $request->month)
                                        ->where('sub_kegiatan_id', $id)
                                        ->get();
                                    $sumRealisasiAnggaran = $arrRealisasiAnggaran->sum('anggaran');
                                    $sumRealisasiAnggaranBulanIni = $arrRealisasiAnggaran->sum('anggaran_bulan_ini');

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
                                        // 'realisasi_anggaran' => (int)$sumRealisasiAnggaran ?? 0,
                                        'realisasi_anggaran' => (int)($sumRealisasiAnggaran ?? 0) - (int)($sumRealisasiAnggaranBulanIni ?? 0),
                                        'realisasi_anggaran_bulan_ini' => (int)$sumRealisasiAnggaranBulanIni ?? 0,
                                        'rincian_belanja' => [],
                                    ];

                                    foreach ($arrTargetKinerja as $targetKinerja) {
                                        $datas['data'][] = $targetKinerja;
                                        $dbTargetKinerja = TargetKinerja::find($targetKinerja['id']);
                                        $arrRincianBelanja = [];
                                        $arrRincianBelanja = TargetKinerjaRincian::where('target_kinerja_id', $targetKinerja['id_target'])
                                            ->get();
                                        foreach ($arrRincianBelanja as $keyRincianBelanja => $rincianBelanja) {
                                            $realisasiRincian = RealisasiRincian::where('realisasi_id', $targetKinerja['id'])
                                                ->where('sub_kegiatan_id', $id)
                                                ->where('kode_rekening_id', $dbTargetKinerja->kode_rekening_id)
                                                ->where('sumber_dana_id', $dbTargetKinerja->sumber_dana_id)
                                                // ->where('target_rincian_id', $rincianBelanja->id)
                                                ->first();
                                            if (!$realisasiRincian) {
                                                $realisasiRincian = new RealisasiRincian();
                                                $realisasiRincian->periode_id = $rincianBelanja->periode_id;
                                                $realisasiRincian->realisasi_id = $targetKinerja['id'];
                                                // $realisasiRincian->target_rincian_id = $rincianBelanja->id;
                                                $realisasiRincian->title = $rincianBelanja->title;
                                                $realisasiRincian->urusan_id = $rincianBelanja->urusan_id;
                                                $realisasiRincian->bidang_urusan_id = $rincianBelanja->bidang_urusan_id;
                                                $realisasiRincian->program_id = $rincianBelanja->program_id;
                                                $realisasiRincian->kegiatan_id = $rincianBelanja->kegiatan_id;
                                                $realisasiRincian->sub_kegiatan_id = $rincianBelanja->sub_kegiatan_id;
                                                $realisasiRincian->kode_rekening_id = $rincianBelanja->kode_rekening_id;
                                                $realisasiRincian->sumber_dana_id = $rincianBelanja->sumber_dana_id;
                                                $realisasiRincian->year = $request->year;
                                                $realisasiRincian->month = $request->month;
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
                                                // 'realisasi_anggaran' => (int)$realisasiRincian->anggaran,
                                                'realisasi_anggaran' => (int)$realisasiRincian->anggaran - (int)$realisasiRincian->anggaran_bulan_ini,
                                                'realisasi_anggaran_bulan_ini' => (int)$realisasiRincian->anggaran_bulan_ini,
                                                'keterangan_rincian' => [],
                                            ];

                                            $arrKeterangan = TargetKinerjaKeterangan::where('parent_id', $rincianBelanja->id)->get();
                                            foreach ($arrKeterangan as $targetKeterangan) {
                                                $realisasiKeterangan = RealisasiKeterangan::where('realisasi_id', $targetKinerja['id'])
                                                    // ->where('target_keterangan_id', $targetKeterangan->id)
                                                    ->where('parent_id', $realisasiRincian->id)
                                                    ->where('year', $request->year)
                                                    ->where('month', $request->month)
                                                    ->first();
                                                if (!$realisasiKeterangan) {
                                                    $realisasiKeterangan = new RealisasiKeterangan();
                                                    $realisasiKeterangan->periode_id = $targetKeterangan->periode_id;
                                                    $realisasiKeterangan->realisasi_id = $targetKinerja['id'];
                                                    // $realisasiKeterangan->target_keterangan_id = $targetKeterangan->id;
                                                    $realisasiKeterangan->parent_id = $realisasiRincian->id;
                                                    $realisasiKeterangan->title = $targetKeterangan->title;
                                                    $realisasiKeterangan->urusan_id = $targetKeterangan->urusan_id;
                                                    $realisasiKeterangan->bidang_urusan_id = $targetKeterangan->bidang_urusan_id;
                                                    $realisasiKeterangan->program_id = $targetKeterangan->program_id;
                                                    $realisasiKeterangan->kegiatan_id = $targetKeterangan->kegiatan_id;
                                                    $realisasiKeterangan->sub_kegiatan_id = $targetKeterangan->sub_kegiatan_id;
                                                    $realisasiKeterangan->kode_rekening_id = $targetKeterangan->kode_rekening_id;
                                                    $realisasiKeterangan->sumber_dana_id = $targetKeterangan->sumber_dana_id;
                                                    $realisasiKeterangan->year = $request->year;
                                                    $realisasiKeterangan->month = $request->month;
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

                                                    // 'realisasi_anggaran_keterangan' => (int)$realisasiKeterangan->anggaran,
                                                    'realisasi_anggaran_keterangan' => (int)$realisasiKeterangan->anggaran - (int)$realisasiKeterangan->anggaran_bulan_ini,
                                                    'realisasi_anggaran_bulan_ini' => (int)$realisasiKeterangan->anggaran_bulan_ini,

                                                    'target_persentase_kinerja' => $targetKeterangan->persentase_kinerja ?? 100,
                                                    'persentase_kinerja' => $realisasiKeterangan->persentase_kinerja,
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
        } catch (\Exception $e) {
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function getKeteranganSubKegiatan($idRealisasiSubKegiatan, Request $request)
    {
        try {
            $return = [];
            $RealisasiSubKegiatan = RealisasiSubKegiatan::find($idRealisasiSubKegiatan);
            if (!$RealisasiSubKegiatan) {
                return $this->errorResponse('Data Realisasi Sub Kegiatan tidak ditemukan', 200);
            }
            $subKegiatan = SubKegiatan::find($RealisasiSubKegiatan->sub_kegiatan_id);
            if (!$subKegiatan) {
                return $this->errorResponse('Data Sub Kegiatan tidak ditemukan', 200);
            }

            $return = $this->_GetDataSubKegiatanKeterangan($subKegiatan->id, $request->year, $request->month, $subKegiatan->instance_id);
            $return['files'] = $this->_GetDataSubKegiatanFiles($RealisasiSubKegiatan->id);
            $return['newFiles'] = null;

            return $this->successResponse($return, 'detail keterangan');
        } catch (\Exception $e) {
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
        }
    }

    function saveKeteranganSubKegiatan($idRealisasiSubKegiatan, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $dataRealisasiSubKegiatan = RealisasiSubKegiatan::find($idRealisasiSubKegiatan);
        if (!$dataRealisasiSubKegiatan) {
            return $this->errorResponse('Realisasi Sub Kegiatan tidak ditemukan');
        }
        // $subKegiatan = SubKegiatan::where('id', $dataRealisasiSubKegiatan->sub_kegiatan_id)->first();

        DB::beginTransaction();
        try {
            $dataKeterangan = RealisasiSubKegiatanKeterangan::find($request->id);
            if ($dataKeterangan) {
                $dataKeterangan->notes = $request->notes;
                $dataKeterangan->link_map = $request->link_map;
                $dataKeterangan->faktor_penghambat = $request->faktor_penghambat;
                $dataKeterangan->longitude = $request->longitude;
                $dataKeterangan->latitude = $request->latitude;
                $dataKeterangan->updated_by = auth()->user()->id;
                $dataKeterangan->save();
            }

            $files = $request->newFiles;
            if ($files && count($files) > 0) {
                foreach ($files as $key => $file) {
                    // $fileName = $idRealisasiSubKegiatan . $key . time() . $idRealisasiSubKegiatan;
                    $fileName = $key . $idRealisasiSubKegiatan . '-' . $file->getClientOriginalName();
                    $upload = $file->storeAs('images/realisasi/keterangan', $fileName, 'public');

                    $data = new RealisasiSubKegiatanFiles();
                    $data->parent_id = $idRealisasiSubKegiatan;
                    $data->type = 'bappeda';
                    $data->save_to = 'local';
                    $data->file = 'storage/' . $upload;
                    $data->filename = $file->getClientOriginalName();
                    $data->path = 'storage/' . $upload;
                    $data->size = $file->getSize();
                    $data->extension = $file->extension();
                    $data->mime_type = $file->getMimeType();
                    $data->created_by = auth()->user()->id;
                    $data->save();
                }

                // logs start
                if (auth()->check()) {
                    $newLogs = [];
                    $oldLogs = DB::table('log_users')
                        ->where('date', date('Y-m-d'))
                        ->where('user_id', auth()->id())
                        ->first();
                    if ($oldLogs) {
                        $newLogs = json_decode($oldLogs->logs);
                    }
                    $newLogs[] = [
                        'action' => 'realisasi-sub-kegiatan-files@update',
                        'description' => auth()->user()->fullname . ' menambahkan ' . count($files) . ' berkas keterangan realisasi ' . $dataKeterangan->SubKegiatan->name,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    DB::table('log_users')
                        ->updateOrInsert([
                            'date' => date('Y-m-d'),
                            'user_id' => auth()->id(),
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->header('User-Agent'),
                        ], [
                            'logs' => json_encode($newLogs),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
                // logs end
            }

            DB::commit();
            return $this->successResponse(null, 'Keterangan berhasil diupload');
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function deleteImageKeteranganSubKegiatan($id)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $data = RealisasiSubKegiatanFiles::find($id);
        DB::beginTransaction();
        try {
            if ($data) {
                $data->deleted_by = auth()->user()->id;
                $data->save();
                $data->delete();

                $newLogs = [];
                $oldLogs = DB::table('log_users')
                    ->where('date', date('Y-m-d'))
                    ->where('user_id', auth()->id())
                    ->first();
                if ($oldLogs) {
                    $newLogs = json_decode($oldLogs->logs);
                }
                $newLogs[] = [
                    'action' => 'realisasi-sub-kegiatan-keterangan@update',
                    'id' => $data->id,
                    'description' => 'Memperbarui Menghapus Berkas Pendukung Keterangan Realisasi ' . ($data->SubKegiatan->fullcode ?? '') . ' - ' . ($data->SubKegiatan->name ?? ''),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                DB::table('log_users')
                    ->updateOrInsert([
                        'date' => date('Y-m-d'),
                        'user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('User-Agent'),
                    ], [
                        'logs' => json_encode($newLogs),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                DB::commit();
                return $this->successResponse(null, 'Berhas telah berhasil dihapus!');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function saveRealisasi($id, Request $request)
    {
        // return $request->all();
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        $subKegiatan = SubKegiatan::find($id);
        if (!$subKegiatan) {
            return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
        }

        // check status realisasi sub kegiatan if verified
        $realisasiSubKegiatan = RealisasiStatus::where('sub_kegiatan_id', $id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->first();
        if ($realisasiSubKegiatan) {
            if ($realisasiSubKegiatan->status === 'verified') {
                return $this->errorResponse('Data Realisasi Sudah diverifikasi', 200);
            }
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
                        return $this->errorResponse('Target Kinerja Anggaran belum diverifikasi', 200);
                    }
                    // Check Target Kinerja Status End

                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];
                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasiLastMonth = Realisasi::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                            ->where('urusan_id', $realisasi->urusan_id)
                            ->where('bidang_urusan_id', $realisasi->bidang_urusan_id)
                            ->where('program_id', $realisasi->program_id)
                            ->where('kegiatan_id', $realisasi->kegiatan_id)
                            ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                            ->where('type', $realisasi->type)
                            ->where('is_detail', $realisasi->is_detail)
                            ->where('nama_paket', $realisasi->nama_paket)
                            ->where('year', $request->year)
                            ->where('month', $request->month - 1)
                            ->first();
                        $realisasi->anggaran = ($realisasiLastMonth->anggaran ?? 0) + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->updated_at = auth()->id();
                    $realisasi->save();

                    // Update to next month until December Start
                    $currentMonth = $request->month + 1;
                    $maxMonth = 12;
                    $prevRealisasi = Realisasi::find($data['id']);
                    for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                        $nextRealisasi = Realisasi::where('month', $i)
                            ->where('year', $request->year)
                            ->where('instance_id', $prevRealisasi->instance_id)
                            ->where('urusan_id', $prevRealisasi->urusan_id)
                            ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                            ->where('program_id', $prevRealisasi->program_id)
                            ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                            ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                            ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                            ->where('type', $prevRealisasi->type)
                            ->where('is_detail', $prevRealisasi->is_detail)
                            ->where('nama_paket', $prevRealisasi->nama_paket)
                            ->whereNotIn('status', ['verified'])
                            ->first();
                        if ($nextRealisasi) {
                            $nextRealisasi->anggaran = $realisasi->anggaran;
                            $nextRealisasi->updated_at = auth()->id();
                            $nextRealisasi->save();
                        }
                    }
                }

                if ($data['is_detail'] === true && count($data['rincian_belanja']) > 0) {
                    $realisasi = Realisasi::find($data['id']);
                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];

                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran'] + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->updated_at = auth()->id();
                    $realisasi->save();

                    foreach ($data['rincian_belanja'] as $rincian) {
                        $realisasiRincian = RealisasiRincian::find($rincian['id']);
                        $realisasiRincian->anggaran_bulan_ini = $rincian['realisasi_anggaran_bulan_ini'];
                        if ($request->month == 1) {
                            $realisasiRincian->anggaran = $rincian['realisasi_anggaran'];
                        } else if ($request->month > 1) {
                            $realisasiRincianLastMonth = RealisasiRincian::where('realisasi_id', $realisasi->id)
                                ->where('urusan_id', $realisasiRincian->urusan_id)
                                ->where('bidang_urusan_id', $realisasiRincian->bidang_urusan_id)
                                ->where('program_id', $realisasiRincian->program_id)
                                ->where('kegiatan_id', $realisasiRincian->kegiatan_id)
                                ->where('sub_kegiatan_id', $realisasiRincian->sub_kegiatan_id)
                                ->where('kode_rekening_id', $realisasiRincian->kode_rekening_id)
                                ->where('sumber_dana_id', $realisasiRincian->sumber_dana_id)
                                ->where('title', $realisasiRincian->title)
                                ->where('year', $request->year)
                                ->where('month', $request->month - 1)
                                ->first();
                            $realisasiRincian->anggaran = $realisasiRincianLastMonth->anggaran + $rincian['realisasi_anggaran_bulan_ini'];
                        }
                        $realisasiRincian->updated_at = auth()->id();
                        $realisasiRincian->save();

                        foreach ($rincian['keterangan_rincian'] as $keterangan) {
                            $realisasiKeterangan = RealisasiKeterangan::find($keterangan['id']);
                            $koefisien = $keterangan['koefisien_realisasi'];
                            $koefisien = str_replace(',', '.', $koefisien);
                            $realisasiKeterangan->koefisien = $koefisien;
                            // $realisasiKeterangan->koefisien = $keterangan['koefisien_realisasi'];

                            $realisasiKeterangan->anggaran_bulan_ini = $keterangan['realisasi_anggaran_bulan_ini'];
                            if ($request->month == 1) {
                                // $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_keterangan'];
                                $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_bulan_ini'];
                            } else if ($request->month > 1) {
                                $realisasiKeteranganLastMonth = RealisasiKeterangan::where('realisasi_id', $realisasi->id)
                                    ->where('sub_kegiatan_id', $realisasiKeterangan->sub_kegiatan_id)
                                    ->where('kode_rekening_id', $realisasiKeterangan->kode_rekening_id)
                                    ->where('sumber_dana_id', $realisasiKeterangan->sumber_dana_id)
                                    ->where('title', $realisasiKeterangan->title)
                                    ->where('year', $request->year)
                                    ->where('month', $request->month - 1)
                                    ->first();
                                $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran'] + $keterangan['realisasi_anggaran_bulan_ini'];
                            }
                            $realisasiKeterangan->persentase_kinerja = $keterangan['persentase_kinerja'];
                            $realisasiKeterangan->updated_at = auth()->id();
                            $realisasiKeterangan->save();
                        }


                        // Update to next month until December Start
                        $currentMonth = $request->month + 1;
                        $maxMonth = 12;
                        $prevRealisasi = Realisasi::find($data['id']);

                        for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                            $nextRealisasi = Realisasi::where('month', $i)
                                ->where('year', $request->year)
                                ->where('instance_id', $prevRealisasi->instance_id)
                                ->where('urusan_id', $prevRealisasi->urusan_id)
                                ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                                ->where('program_id', $prevRealisasi->program_id)
                                ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                                ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                                ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                                ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                                ->where('type', $prevRealisasi->type)
                                ->where('is_detail', $prevRealisasi->is_detail)
                                ->where('nama_paket', $prevRealisasi->nama_paket)
                                ->first();
                            if ($nextRealisasi) {
                                $nextRealisasiRincian = RealisasiRincian::where('realisasi_id', $nextRealisasi->id)
                                    // ->where('target_rincian_id', $realisasiRincian->target_rincian_id)
                                    ->where('urusan_id', $nextRealisasi->urusan_id)
                                    ->where('bidang_urusan_id', $nextRealisasi->bidang_urusan_id)
                                    ->where('program_id', $nextRealisasi->program_id)
                                    ->where('kegiatan_id', $nextRealisasi->kegiatan_id)
                                    ->where('sub_kegiatan_id', $nextRealisasi->sub_kegiatan_id)
                                    ->where('kode_rekening_id', $nextRealisasi->kode_rekening_id)
                                    ->where('sumber_dana_id', $nextRealisasi->sumber_dana_id)
                                    ->where('year', $nextRealisasi->year)
                                    ->where('month', $nextRealisasi->month)
                                    ->first();
                                if (!$nextRealisasiRincian) {
                                    $nextRealisasiRincian = new RealisasiRincian();
                                    $nextRealisasiRincian->periode_id = $realisasiRincian->periode_id;
                                    $nextRealisasiRincian->realisasi_id = $nextRealisasi->id;
                                    // $nextRealisasiRincian->target_rincian_id = $realisasiRincian->target_rincian_id;
                                    $nextRealisasiRincian->title = $realisasiRincian->title;
                                    $nextRealisasiRincian->urusan_id = $realisasiRincian->urusan_id;
                                    $nextRealisasiRincian->bidang_urusan_id = $realisasiRincian->bidang_urusan_id;
                                    $nextRealisasiRincian->program_id = $realisasiRincian->program_id;
                                    $nextRealisasiRincian->kegiatan_id = $realisasiRincian->kegiatan_id;
                                    $nextRealisasiRincian->sub_kegiatan_id = $realisasiRincian->sub_kegiatan_id;
                                    $nextRealisasiRincian->kode_rekening_id = $realisasiRincian->kode_rekening_id;
                                    $nextRealisasiRincian->sumber_dana_id = $realisasiRincian->sumber_dana_id;
                                    $nextRealisasiRincian->year = $nextRealisasi->year;
                                    $nextRealisasiRincian->month = $nextRealisasi->month;
                                    $nextRealisasiRincian->pagu_sipd = $realisasiRincian->pagu_sipd;
                                    $nextRealisasiRincian->created_by = auth()->user()->id;
                                }

                                if (!in_array($nextRealisasiRincian->status, ['verified'])) {
                                    $nextRealisasiRincian->anggaran = $rincian['realisasi_anggaran'];
                                    $nextRealisasiRincian->anggaran_bulan_ini = $rincian['realisasi_anggaran_bulan_ini'];
                                    $nextRealisasiRincian->updated_at = auth()->id();
                                    $nextRealisasiRincian->save();
                                }

                                foreach ($rincian['keterangan_rincian'] as $keterangan) {
                                    $nextRealisasiKeterangan = RealisasiKeterangan::where('realisasi_id', $nextRealisasi->id)
                                        // ->where('target_keterangan_id', $keterangan['id_target_keterangan'])
                                        ->where('parent_id', $nextRealisasiRincian->id)
                                        ->where('year', $nextRealisasi->year)
                                        ->where('month', $nextRealisasi->month)
                                        ->first();
                                    if (!$nextRealisasiKeterangan) {
                                        $nextRealisasiKeterangan = new RealisasiKeterangan();
                                        $nextRealisasiKeterangan->periode_id = $realisasiKeterangan->periode_id;
                                        $nextRealisasiKeterangan->realisasi_id = $nextRealisasi->id;
                                        // $nextRealisasiKeterangan->target_keterangan_id = $keterangan['id_target_keterangan'];
                                        $nextRealisasiKeterangan->parent_id = $nextRealisasiRincian->id;
                                        $nextRealisasiKeterangan->urusan_id = $realisasiKeterangan->urusan_id;
                                        $nextRealisasiKeterangan->bidang_urusan_id = $realisasiKeterangan->bidang_urusan_id;
                                        $nextRealisasiKeterangan->program_id = $realisasiKeterangan->program_id;
                                        $nextRealisasiKeterangan->kegiatan_id = $realisasiKeterangan->kegiatan_id;
                                        $nextRealisasiKeterangan->sub_kegiatan_id = $realisasiKeterangan->sub_kegiatan_id;
                                        $nextRealisasiKeterangan->kode_rekening_id = $realisasiKeterangan->kode_rekening_id;
                                        $nextRealisasiKeterangan->sumber_dana_id = $realisasiKeterangan->sumber_dana_id;
                                        $nextRealisasiKeterangan->title = $keterangan['title'] ?? null;
                                        $nextRealisasiKeterangan->year = $nextRealisasi->year;
                                        $nextRealisasiKeterangan->month = $nextRealisasi->month;
                                    }
                                    if (!in_array($nextRealisasiRincian->status, ['verified'])) {
                                        $nextRealisasiKeterangan->koefisien = $keterangan['koefisien_realisasi'];
                                        $nextRealisasiKeterangan->satuan_id = $keterangan['satuan_id'];
                                        $nextRealisasiKeterangan->satuan_name = $keterangan['satuan_name'];
                                        $nextRealisasiKeterangan->harga_satuan = $keterangan['harga_satuan'];
                                        $nextRealisasiKeterangan->ppn = $keterangan['ppn'];
                                        $nextRealisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_keterangan'];
                                        $nextRealisasiKeterangan->anggaran_bulan_ini = $keterangan['realisasi_anggaran_bulan_ini'];
                                        $nextRealisasiKeterangan->persentase_kinerja = $keterangan['persentase_kinerja'];
                                        $nextRealisasiKeterangan->kinerja = 0;
                                        $nextRealisasiKeterangan->persentase_kinerja = 0;
                                        $nextRealisasiKeterangan->created_by = auth()->user()->id;
                                        $nextRealisasiKeterangan->updated_at = auth()->id();
                                        $nextRealisasiKeterangan->save();
                                    }
                                    // return $nextRealisasiKeterangan;
                                }
                            }
                        }
                    }
                }

                if ($data['is_detail'] === true && count($data['rincian_belanja']) === 0) {
                    $realisasi = Realisasi::find($data['id']);

                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];
                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasiLastMonth = Realisasi::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                            ->where('urusan_id', $realisasi->urusan_id)
                            ->where('bidang_urusan_id', $realisasi->bidang_urusan_id)
                            ->where('program_id', $realisasi->program_id)
                            ->where('kegiatan_id', $realisasi->kegiatan_id)
                            ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                            ->where('type', $realisasi->type)
                            ->where('is_detail', $realisasi->is_detail)
                            ->where('nama_paket', $realisasi->nama_paket)
                            ->where('year', $request->year)
                            ->where('month', $request->month - 1)
                            ->first();
                        $realisasi->anggaran = ($realisasiLastMonth->anggaran ?? 0) + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->updated_at = auth()->id();
                    $realisasi->save();

                    // Update to next month until December Start
                    $currentMonth = $request->month + 1;
                    $maxMonth = 12;
                    $prevRealisasi = Realisasi::find($data['id']);
                    for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                        $nextRealisasi = Realisasi::where('month', $i)
                            ->where('year', $request->year)
                            ->where('instance_id', $prevRealisasi->instance_id)
                            ->where('urusan_id', $prevRealisasi->urusan_id)
                            ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                            ->where('program_id', $prevRealisasi->program_id)
                            ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                            ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                            ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                            ->where('type', $prevRealisasi->type)
                            ->where('is_detail', $prevRealisasi->is_detail)
                            ->where('nama_paket', $prevRealisasi->nama_paket)
                            ->whereNotIn('status', ['verified'])
                            ->first();
                        if ($nextRealisasi) {
                            $nextRealisasi->anggaran = $realisasi->anggaran;
                            $nextRealisasi->updated_at = auth()->id();
                            $nextRealisasi->save();
                        }
                    }
                }
            }


            $newLogs = [];
            $oldLogs = DB::table('log_users')
                ->where('date', date('Y-m-d'))
                ->where('user_id', auth()->id())
                ->first();
            if ($oldLogs) {
                $newLogs = json_decode($oldLogs->logs);
            }
            $newLogs[] = [
                'action' => 'realisasi@update',
                'id' => $subKegiatan->id,
                'description' => 'Memperbarui Realisasi ' . $subKegiatan->fullcode . ' - ' . $subKegiatan->name . ' Bulan ' . $request->month . ' - ' . $request->year,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            DB::table('log_users')
                ->updateOrInsert([
                    'date' => date('Y-m-d'),
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                ], [
                    'logs' => json_encode($newLogs),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            return $this->successResponse($return, 'Realisasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function saveDetailRealisasi($id, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        DB::beginTransaction();
        try {
            $subKegiatan = SubKegiatan::find($id);
            $datas = $request->data['indicators'];
            $return = null;

            // check status realisasi sub kegiatan if verified
            $realisasiSubKegiatan = RealisasiStatus::where('sub_kegiatan_id', $id)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->first();
            if ($realisasiSubKegiatan) {
                if ($realisasiSubKegiatan->status === 'verified') {
                    return $this->errorResponse('Data Realisasi Sudah diverifikasi', 200);
                }
            }

            foreach ($datas as $data) {
                $DataRealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->first();

                if ($data['type'] === 'anggaran') {
                    if ($DataRealisasiSubKegiatan) {
                        $DataRealisasiSubKegiatan->realisasi_anggaran = $data['realisasi'];
                    }
                }

                if ($data['type'] == 'kinerja') {
                    $DataRealisasiSubKegiatan->realisasi_kinerja_json = json_encode($datas);
                }

                if ($data['type'] === 'persentase-kinerja') {
                    if ($DataRealisasiSubKegiatan) {
                        $DataRealisasiSubKegiatan->persentase_realisasi_kinerja = $data['realisasi'];
                    }
                }

                $DataRealisasiSubKegiatan->updated_at = auth()->id();
                $DataRealisasiSubKegiatan->save();

                $nextDataRealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->whereBetween('month', [$request->month + 1, 12])
                    ->whereNotIn('status', ['verified'])
                    ->get();
                foreach ($nextDataRealisasiSubKegiatan as $nextData) {
                    if ($data['type'] === 'anggaran') {
                        $nextData->realisasi_anggaran = $data['realisasi'];
                    }
                    if ($data['type'] === 'persentase-kinerja') {
                        $nextData->persentase_realisasi_kinerja = $data['realisasi'];
                    }
                    $nextData->updated_by = auth()->id();
                    $nextData->save();
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Realisasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function syncRealisasi($id, Request $request)
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
                    // if ($targetKinerjaStatus->status !== 'verified') {
                    //     return $this->errorResponse('Data Target Kinerja belum diverifikasi', 200);
                    // }
                    // Check Target Kinerja Status End

                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];
                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasiLastMonth = Realisasi::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                            ->where('urusan_id', $realisasi->urusan_id)
                            ->where('bidang_urusan_id', $realisasi->bidang_urusan_id)
                            ->where('program_id', $realisasi->program_id)
                            ->where('kegiatan_id', $realisasi->kegiatan_id)
                            ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                            ->where('type', $realisasi->type)
                            ->where('is_detail', $realisasi->is_detail)
                            ->where('nama_paket', $realisasi->nama_paket)
                            ->where('year', $request->year)
                            ->where('month', $request->month - 1)
                            ->first();
                        $realisasi->anggaran = ($realisasiLastMonth->anggaran ?? 0) + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->save();

                    // Update to next month until December Start
                    $currentMonth = $request->month + 1;
                    $maxMonth = 12;
                    $prevRealisasi = Realisasi::find($data['id']);
                    for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                        $nextRealisasi = Realisasi::where('month', $i)
                            ->where('year', $request->year)
                            ->where('instance_id', $prevRealisasi->instance_id)
                            ->where('urusan_id', $prevRealisasi->urusan_id)
                            ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                            ->where('program_id', $prevRealisasi->program_id)
                            ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                            ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                            ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                            ->where('type', $prevRealisasi->type)
                            ->where('is_detail', $prevRealisasi->is_detail)
                            ->where('nama_paket', $prevRealisasi->nama_paket)
                            ->whereNotIn('status', ['verified'])
                            ->first();
                        if ($nextRealisasi) {
                            $nextRealisasi->anggaran = $realisasi->anggaran;
                            $nextRealisasi->save();
                        }
                    }
                }

                if ($data['is_detail'] === true && count($data['rincian_belanja']) > 0) {
                    $realisasi = Realisasi::find($data['id']);
                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];

                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran'] + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->save();

                    foreach ($data['rincian_belanja'] as $rincian) {
                        $realisasiRincian = RealisasiRincian::find($rincian['id']);
                        $realisasiRincian->anggaran_bulan_ini = $rincian['realisasi_anggaran_bulan_ini'];
                        if ($request->month == 1) {
                            $realisasiRincian->anggaran = $rincian['realisasi_anggaran'];
                        } else if ($request->month > 1) {
                            $realisasiRincianLastMonth = RealisasiRincian::where('realisasi_id', $realisasi->id)
                                ->where('urusan_id', $realisasiRincian->urusan_id)
                                ->where('bidang_urusan_id', $realisasiRincian->bidang_urusan_id)
                                ->where('program_id', $realisasiRincian->program_id)
                                ->where('kegiatan_id', $realisasiRincian->kegiatan_id)
                                ->where('sub_kegiatan_id', $realisasiRincian->sub_kegiatan_id)
                                ->where('kode_rekening_id', $realisasiRincian->kode_rekening_id)
                                ->where('sumber_dana_id', $realisasiRincian->sumber_dana_id)
                                ->where('title', $realisasiRincian->title)
                                ->where('year', $request->year)
                                ->where('month', $request->month - 1)
                                ->first();
                            $realisasiRincian->anggaran = $realisasiRincianLastMonth->anggaran + $rincian['realisasi_anggaran_bulan_ini'];
                        }
                        $realisasiRincian->save();

                        foreach ($rincian['keterangan_rincian'] as $keterangan) {
                            $realisasiKeterangan = RealisasiKeterangan::find($keterangan['id']);
                            $koefisien = $keterangan['koefisien_realisasi'];
                            $koefisien = str_replace(',', '.', $koefisien);
                            $realisasiKeterangan->koefisien = $koefisien;
                            // $realisasiKeterangan->koefisien = $keterangan['koefisien_realisasi'];

                            $realisasiKeterangan->anggaran_bulan_ini = $keterangan['realisasi_anggaran_bulan_ini'];
                            if ($request->month == 1) {
                                // $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_keterangan'];
                                $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_bulan_ini'];
                            } else if ($request->month > 1) {
                                $realisasiKeteranganLastMonth = RealisasiKeterangan::where('realisasi_id', $realisasi->id)
                                    ->where('sub_kegiatan_id', $realisasiKeterangan->sub_kegiatan_id)
                                    ->where('kode_rekening_id', $realisasiKeterangan->kode_rekening_id)
                                    ->where('sumber_dana_id', $realisasiKeterangan->sumber_dana_id)
                                    ->where('title', $realisasiKeterangan->title)
                                    ->where('year', $request->year)
                                    ->where('month', $request->month - 1)
                                    ->first();
                                $realisasiKeterangan->anggaran = $keterangan['realisasi_anggaran'] + $keterangan['realisasi_anggaran_bulan_ini'];
                            }
                            $realisasiKeterangan->persentase_kinerja = $keterangan['persentase_kinerja'];
                            $realisasiKeterangan->save();
                        }


                        // Update to next month until December Start
                        $currentMonth = $request->month + 1;
                        $maxMonth = 12;
                        $prevRealisasi = Realisasi::find($data['id']);

                        for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                            $nextRealisasi = Realisasi::where('month', $i)
                                ->where('year', $request->year)
                                ->where('instance_id', $prevRealisasi->instance_id)
                                ->where('urusan_id', $prevRealisasi->urusan_id)
                                ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                                ->where('program_id', $prevRealisasi->program_id)
                                ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                                ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                                ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                                ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                                ->where('type', $prevRealisasi->type)
                                ->where('is_detail', $prevRealisasi->is_detail)
                                ->where('nama_paket', $prevRealisasi->nama_paket)
                                ->first();
                            if ($nextRealisasi) {
                                $nextRealisasiRincian = RealisasiRincian::where('realisasi_id', $nextRealisasi->id)
                                    // ->where('target_rincian_id', $realisasiRincian->target_rincian_id)
                                    ->where('urusan_id', $nextRealisasi->urusan_id)
                                    ->where('bidang_urusan_id', $nextRealisasi->bidang_urusan_id)
                                    ->where('program_id', $nextRealisasi->program_id)
                                    ->where('kegiatan_id', $nextRealisasi->kegiatan_id)
                                    ->where('sub_kegiatan_id', $nextRealisasi->sub_kegiatan_id)
                                    ->where('kode_rekening_id', $nextRealisasi->kode_rekening_id)
                                    ->where('sumber_dana_id', $nextRealisasi->sumber_dana_id)
                                    ->where('year', $nextRealisasi->year)
                                    ->where('month', $nextRealisasi->month)
                                    ->first();
                                if (!$nextRealisasiRincian) {
                                    $nextRealisasiRincian = new RealisasiRincian();
                                    $nextRealisasiRincian->periode_id = $realisasiRincian->periode_id;
                                    $nextRealisasiRincian->realisasi_id = $nextRealisasi->id;
                                    // $nextRealisasiRincian->target_rincian_id = $realisasiRincian->target_rincian_id;
                                    $nextRealisasiRincian->title = $realisasiRincian->title;
                                    $nextRealisasiRincian->urusan_id = $realisasiRincian->urusan_id;
                                    $nextRealisasiRincian->bidang_urusan_id = $realisasiRincian->bidang_urusan_id;
                                    $nextRealisasiRincian->program_id = $realisasiRincian->program_id;
                                    $nextRealisasiRincian->kegiatan_id = $realisasiRincian->kegiatan_id;
                                    $nextRealisasiRincian->sub_kegiatan_id = $realisasiRincian->sub_kegiatan_id;
                                    $nextRealisasiRincian->kode_rekening_id = $realisasiRincian->kode_rekening_id;
                                    $nextRealisasiRincian->sumber_dana_id = $realisasiRincian->sumber_dana_id;
                                    $nextRealisasiRincian->year = $nextRealisasi->year;
                                    $nextRealisasiRincian->month = $nextRealisasi->month;
                                    $nextRealisasiRincian->pagu_sipd = $realisasiRincian->pagu_sipd;
                                    $nextRealisasiRincian->created_by = auth()->user()->id;
                                }

                                if (!in_array($nextRealisasiRincian->status, ['verified'])) {
                                    $nextRealisasiRincian->anggaran = $rincian['realisasi_anggaran'];
                                    $nextRealisasiRincian->anggaran_bulan_ini = $rincian['realisasi_anggaran_bulan_ini'];
                                    $nextRealisasiRincian->save();
                                }

                                foreach ($rincian['keterangan_rincian'] as $keterangan) {
                                    $nextRealisasiKeterangan = RealisasiKeterangan::where('realisasi_id', $nextRealisasi->id)
                                        // ->where('target_keterangan_id', $keterangan['id_target_keterangan'])
                                        ->where('parent_id', $nextRealisasiRincian->id)
                                        ->where('year', $nextRealisasi->year)
                                        ->where('month', $nextRealisasi->month)
                                        ->first();
                                    if (!$nextRealisasiKeterangan) {
                                        $nextRealisasiKeterangan = new RealisasiKeterangan();
                                        $nextRealisasiKeterangan->periode_id = $realisasiKeterangan->periode_id;
                                        $nextRealisasiKeterangan->realisasi_id = $nextRealisasi->id;
                                        // $nextRealisasiKeterangan->target_keterangan_id = $keterangan['id_target_keterangan'];
                                        $nextRealisasiKeterangan->parent_id = $nextRealisasiRincian->id;
                                        $nextRealisasiKeterangan->urusan_id = $realisasiKeterangan->urusan_id;
                                        $nextRealisasiKeterangan->bidang_urusan_id = $realisasiKeterangan->bidang_urusan_id;
                                        $nextRealisasiKeterangan->program_id = $realisasiKeterangan->program_id;
                                        $nextRealisasiKeterangan->kegiatan_id = $realisasiKeterangan->kegiatan_id;
                                        $nextRealisasiKeterangan->sub_kegiatan_id = $realisasiKeterangan->sub_kegiatan_id;
                                        $nextRealisasiKeterangan->kode_rekening_id = $realisasiKeterangan->kode_rekening_id;
                                        $nextRealisasiKeterangan->sumber_dana_id = $realisasiKeterangan->sumber_dana_id;
                                        $nextRealisasiKeterangan->title = $keterangan['title'] ?? null;
                                        $nextRealisasiKeterangan->year = $nextRealisasi->year;
                                        $nextRealisasiKeterangan->month = $nextRealisasi->month;
                                    }
                                    if (!in_array($nextRealisasiRincian->status, ['verified'])) {
                                        $nextRealisasiKeterangan->koefisien = $keterangan['koefisien_realisasi'];
                                        $nextRealisasiKeterangan->satuan_id = $keterangan['satuan_id'];
                                        $nextRealisasiKeterangan->satuan_name = $keterangan['satuan_name'];
                                        $nextRealisasiKeterangan->harga_satuan = $keterangan['harga_satuan'];
                                        $nextRealisasiKeterangan->ppn = $keterangan['ppn'];
                                        $nextRealisasiKeterangan->anggaran = $keterangan['realisasi_anggaran_keterangan'];
                                        $nextRealisasiKeterangan->anggaran_bulan_ini = $keterangan['realisasi_anggaran_bulan_ini'];
                                        $nextRealisasiKeterangan->persentase_kinerja = $keterangan['persentase_kinerja'];
                                        $nextRealisasiKeterangan->kinerja = 0;
                                        $nextRealisasiKeterangan->persentase_kinerja = 0;
                                        $nextRealisasiKeterangan->created_by = auth()->user()->id;
                                        $nextRealisasiKeterangan->save();
                                    }
                                    // return $nextRealisasiKeterangan;
                                }
                            }
                        }
                    }
                }
                if ($data['is_detail'] === true && count($data['rincian_belanja']) === 0) {
                    $realisasi = Realisasi::find($data['id']);

                    $realisasi->anggaran_bulan_ini = $data['realisasi_anggaran_bulan_ini'];
                    if ($request->month == 1) {
                        $realisasi->anggaran = $data['realisasi_anggaran_bulan_ini'];
                    } else if ($request->month > 1) {
                        $realisasiLastMonth = Realisasi::where('sub_kegiatan_id', $realisasi->sub_kegiatan_id)
                            ->where('urusan_id', $realisasi->urusan_id)
                            ->where('bidang_urusan_id', $realisasi->bidang_urusan_id)
                            ->where('program_id', $realisasi->program_id)
                            ->where('kegiatan_id', $realisasi->kegiatan_id)
                            ->where('kode_rekening_id', $realisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $realisasi->sumber_dana_id)
                            ->where('type', $realisasi->type)
                            ->where('is_detail', $realisasi->is_detail)
                            ->where('nama_paket', $realisasi->nama_paket)
                            ->where('year', $request->year)
                            ->where('month', $request->month - 1)
                            ->first();
                        $realisasi->anggaran = ($realisasiLastMonth->anggaran ?? 0) + $data['realisasi_anggaran_bulan_ini'];
                    }
                    $realisasi->save();

                    // Update to next month until December Start
                    $currentMonth = $request->month + 1;
                    $maxMonth = 12;
                    $prevRealisasi = Realisasi::find($data['id']);
                    for ($i = $currentMonth; $i <= $maxMonth; $i++) {
                        $nextRealisasi = Realisasi::where('month', $i)
                            ->where('year', $request->year)
                            ->where('instance_id', $prevRealisasi->instance_id)
                            ->where('urusan_id', $prevRealisasi->urusan_id)
                            ->where('bidang_urusan_id', $prevRealisasi->bidang_urusan_id)
                            ->where('program_id', $prevRealisasi->program_id)
                            ->where('kegiatan_id', $prevRealisasi->kegiatan_id)
                            ->where('sub_kegiatan_id', $prevRealisasi->sub_kegiatan_id)
                            ->where('kode_rekening_id', $prevRealisasi->kode_rekening_id)
                            ->where('sumber_dana_id', $prevRealisasi->sumber_dana_id)
                            ->where('type', $prevRealisasi->type)
                            ->where('is_detail', $prevRealisasi->is_detail)
                            ->where('nama_paket', $prevRealisasi->nama_paket)
                            ->whereNotIn('status', ['verified'])
                            ->first();
                        if ($nextRealisasi) {
                            $nextRealisasi->anggaran = $realisasi->anggaran;
                            $nextRealisasi->save();
                        }
                    }
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Realisasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function syncDetailRealisasi($id, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        DB::beginTransaction();
        try {
            $subKegiatan = SubKegiatan::find($id);
            $datas = $request->data['indicators'];
            $return = null;

            // check status realisasi sub kegiatan if verified
            $realisasiSubKegiatan = RealisasiStatus::where('sub_kegiatan_id', $id)
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->first();
            // if ($realisasiSubKegiatan) {
            //     if ($realisasiSubKegiatan->status === 'verified') {
            //         return $this->errorResponse('Data Realisasi Sudah diverifikasi', 200);
            //     }
            // }

            foreach ($datas as $data) {
                $DataRealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('month', $request->month)
                    ->first();

                if ($data['type'] === 'anggaran') {
                    if ($DataRealisasiSubKegiatan) {
                        $DataRealisasiSubKegiatan->realisasi_anggaran = $data['realisasi'];
                    }
                }

                if ($data['type'] == 'kinerja') {
                    $DataRealisasiSubKegiatan->realisasi_kinerja_json = json_encode($datas);
                }

                if ($data['type'] === 'persentase-kinerja') {
                    if ($DataRealisasiSubKegiatan) {
                        $DataRealisasiSubKegiatan->persentase_realisasi_kinerja = $data['realisasi'];
                    }
                }

                $DataRealisasiSubKegiatan->save();

                $nextDataRealisasiSubKegiatan = RealisasiSubKegiatan::where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->whereBetween('month', [$request->month + 1, 12])
                    ->whereIn('status', ['draft', 'return'])
                    ->get();
                foreach ($nextDataRealisasiSubKegiatan as $nextData) {
                    if ($data['type'] === 'anggaran') {
                        $nextData->realisasi_anggaran = $data['realisasi'];
                    }
                    if ($data['type'] === 'persentase-kinerja') {
                        $nextData->persentase_realisasi_kinerja = $data['realisasi'];
                    }
                    $nextData->save();
                }
            }
            DB::commit();
            return $this->successResponse($return, 'Realisasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // DB::commit();
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
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
            ->latest()
            ->limit(8)
            ->get();
        foreach ($logs as $log) {
            $log->created_by_name = User::find($log->user_id)->fullname;
        }
        $return['logs'] = $logs;

        return $this->successResponse($return, 'Logs Realisasi');
    }

    function postLogsRealisasi($id, Request $request)
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

        if ($request->status == 'sent' || $request->status == 'draft') {
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
                $data->status = $request->status;
                $data->save();

                $subKegiatan = SubKegiatan::find($id);

                DB::table('notes_realisasi')->insert([
                    'data_id' => $data->id,
                    'user_id' => auth()->user()->id,
                    'status' => $request->status,
                    'type' => 'request',
                    'message' => $request->message,
                    'created_at' => now(),
                ]);

                DB::table('data_realisasi')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => 'sent']);

                DB::table('data_realisasi_sub_kegiatan')->where('sub_kegiatan_id', $id)
                    ->where('month', $request->month)
                    ->where('year', $request->year)
                    ->update(['status' => 'sent']);

                // send notification
                $users = User::whereIn('role_id', [6])
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $data->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/realisasi/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $subKegiatan->periode_id,
                    'Permintaan Verifikasi Realisasi',
                    auth()->user()->fullname . ' : ' . $request->message,
                    [
                        'method' => 'sent',
                        'uri' => '/realisasi/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $subKegiatan->periode_id,
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

                $subKegiatan = SubKegiatan::find($id);

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

                DB::table('data_realisasi_sub_kegiatan')->where('sub_kegiatan_id', $id)
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
                    '/realisasi/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $subKegiatan->periode_id,
                    'Verifikasi Realisasi',
                    auth()->user()->fullname . ' : ' . $request->message,
                    [
                        'method' => 'return',
                        'uri' => '/realisasi/' . $id . '?month=' . $request->month . '&year=' . $request->year . '&periode=' . $subKegiatan->periode_id,
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
        return $this->errorResponse('Status tidak valid', 200);
    }

    function _GetDataAPBDSubKegiatan($idSubKegiatan, $year, $month, $instanceId)
    {
        $return = [];
        $data = ApbdSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            // ->where('status', 'verified')
            ->first();
        if (!$data) {
            return 'error';
        }
        $renstra = RenstraSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->first();
        $renja = RenjaSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->first();
        $indicators = [];
        $indikatorCons = DB::table('con_indikator_kinerja_sub_kegiatan')
            ->where('instance_id', $instanceId)
            ->where('program_id', $data->program_id)
            ->where('kegiatan_id', $data->kegiatan_id)
            ->where('sub_kegiatan_id', $idSubKegiatan)
            ->first();

        $indikators = IndikatorSubKegiatan::where('pivot_id', $indikatorCons->id ?? null)
            ->where('status', 'active')
            ->get();

        $dataRealisasi = RealisasiSubKegiatan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $indicators[] = [
            'type' => 'anggaran',
            'name' => 'Dana yang dibutuhkan',
            'target' => $data->total_anggaran,
            'satuan_id' => null,
            'satuan_name' => null,
            'realisasi' => $dataRealisasi->realisasi_anggaran ?? 0,
            'target_renja' => $renja->total_anggaran ?? 0,
            'satuan_id_renja' => null,
            'satuan_name_renja' => null,
            'target_renstra' => $renstra->total_anggaran ?? 0,
            'satuan_id_renstra' => null,
            'satuan_name_renstra' => null,
        ];
        foreach ($indikators as $key => $indikator) {
            $satuanId = null;
            $satuanName = null;
            $satuanIds = $data->satuan_json ?? null;
            if ($satuanIds) {
                $satuanId = json_decode($data->satuan_json, true)[$key] ?? null;
                $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
            }
            $targetKinerja = $data->kinerja_json ?? null;
            if ($targetKinerja) {
                $targetKinerja = json_decode($data->kinerja_json, true)[$key] ?? null;
            }

            $satuanIdRenja = null;
            $satuanNameRenja = null;
            $satuanIdsRenja = $renja->satuan_json ?? null;
            if ($satuanIdsRenja) {
                $satuanIdRenja = json_decode($renja->satuan_json, true)[$key] ?? null;
                $satuanNameRenja = Satuan::where('id', $satuanIdRenja)->first()->name ?? null;
            }
            $targetKinerjaRenja = $renja->kinerja_json ?? null;
            if ($targetKinerjaRenja) {
                $targetKinerjaRenja = json_decode($renja->kinerja_json, true)[$key] ?? null;
            }

            $satuanIdRenstra = null;
            $satuanNameRenstra = null;
            $satuanIdsRenstra = $renstra->satuan_json ?? null;
            if ($satuanIdsRenstra) {
                $satuanIdRenstra = json_decode($renstra->satuan_json, true)[$key] ?? null;
                $satuanNameRenstra = Satuan::where('id', $satuanIdRenstra)->first()->name ?? null;
            }
            $targetKinerjaRenstra = $renstra->kinerja_json ?? null;
            if ($targetKinerjaRenstra) {
                $targetKinerjaRenstra = json_decode($renstra->kinerja_json, true)[$key] ?? null;
            }

            $realisasiKinerja = null;
            if ($dataRealisasi->realisasi_kinerja_json) {
                $realisasiKinerja = json_decode($dataRealisasi->realisasi_kinerja_json, true);
                if ($realisasiKinerja) {
                    $realisasiKinerja = $realisasiKinerja[$key + 1]['realisasi'] ?? null;
                }
            }

            $indicators[] = [
                'type' => 'kinerja',
                'name' => $indikator->name,
                // 'target' => $targetKinerja,
                // 'satuan_id' => $satuanId,
                // 'satuan_name' => $satuanName,
                'target' => null,
                'satuan_id' => $satuanIdRenja,
                'satuan_name' => $satuanNameRenja,
                'realisasi' => $realisasiKinerja ?? 0,
                'target_renja' => $targetKinerjaRenja,
                'satuan_id_renja' => $satuanIdRenja,
                'satuan_name_renja' => $satuanNameRenja,
                'target_renstra' => $targetKinerjaRenstra,
                'satuan_id_renstra' => $satuanIdRenstra,
                'satuan_name_renstra' => $satuanNameRenstra,
            ];
        }
        $indicators[] = [
            'type' => 'persentase-kinerja',
            'name' => 'Persentase Kinerja',
            'target' => $data->percent_kinerja,
            'satuan_id' => null,
            'satuan_name' => '%',
            'realisasi' => $dataRealisasi->persentase_realisasi_kinerja ?? 0,
            'target_renja' => $renja->percent_kinerja ?? 0,
            'satuan_id_renja' => null,
            'satuan_name_renja' => '%',
            'target_renstra' => $renstra->percent_kinerja ?? 0,
            'satuan_id_renstra' => null,
            'satuan_name_renstra' => '%',
        ];

        $return = $indicators;
        return $return;
    }

    function _GetDataSubKegiatanKeterangan($idSubKegiatan, $year, $month, $instanceId)
    {
        $return = [
            'id' => null,
            'notes' => null,
            'link_map' => null,
            'faktor_penghambat' => null,
            'longitude' => null,
            'latitude' => null,
            'latitude' => null,
        ];

        $data = RealisasiSubKegiatanKeterangan::where('sub_kegiatan_id', $idSubKegiatan)
            ->where('year', $year)
            ->where('month', $month)
            ->where('instance_id', $instanceId)
            ->first();
        if (!$data) {
            $subKegiatan = SubKegiatan::find($idSubKegiatan);
            if (!$subKegiatan) {
                return $return;
            }
            $data = new RealisasiSubKegiatanKeterangan();
            $data->periode_id = $subKegiatan->periode_id;
            $data->instance_id = $instanceId;
            $data->year = $year;
            $data->month = $month;
            $data->urusan_id = $subKegiatan->urusan_id;
            $data->bidang_urusan_id = $subKegiatan->bidang_id;
            $data->program_id = $subKegiatan->program_id;
            $data->kegiatan_id = $subKegiatan->kegiatan_id;
            $data->sub_kegiatan_id = $idSubKegiatan;
            $data->notes = null;
            $data->link_map = null;
            $data->faktor_penghambat = null;
            $data->longitude = null;
            $data->latitude = null;
            $data->save();
        }
        if ($data) {
            $return = [
                'id' => $data->id,
                'notes' => $data->notes,
                'link_map' => $data->link_map,
                'faktor_penghambat' => $data->faktor_penghambat,
                'longitude' => $data->longitude,
                'latitude' => $data->latitude,
                'latitude' => $data->latitude,
            ];
        }

        return $return;
    }

    function _GetDataSubKegiatanFiles($idRealisasiSubKegiatan)
    {
        $datas = RealisasiSubKegiatanFiles::where('parent_id', $idRealisasiSubKegiatan)->get();
        $return = [];
        foreach ($datas as $data) {
            $return[] = [
                'id' => $data->id,
                'type' => $data->type,
                'save_to' => $data->save_to,
                'file' => asset($data->file),
                'filename' => $data->filename,
                'path' => $data->path,
                'size' => $data->size,
                'extension' => $data->extension,
                'mime_type' => $data->mime_type,
                'created_at' => $data->created_at,
                'createdBy' => $data->CreatedBy->fullname ?? null,
            ];
        }

        return $return;
    }

    function fetchSpseKontrak(Request $request)
    {
        // $request->year = 2023;
        if (!$request->year) {
            return $this->errorResponse('Tahun tidak boleh kosong');
        }

        try {
            $uri = 'https://isb.lkpp.go.id/isb-2/api/03a97c6f-829b-4459-883a-fea9cfc5d3c2/json/5457/SPSE-TenderEkontrak-Kontrak/tipe/4:4/parameter/' . $request->year . ':357';
            $response = Http::get($uri);
            $data = $response->json();
            $data = collect($data);

            $search = $request->search;
            $kodeSatker = $request->kode_satker;
            // $search = "01/KONTRAK/PPK/DAK-FISIK/DPK-OI/2024";
            // $search = "pupr";

            if ($kodeSatker) {
                $data = $data->filter(function ($item) use ($kodeSatker) {
                    // return false !== stristr($item['kode_satker'], $kodeSatker);
                    return false !== stristr($item['kd_satker_str'], $kodeSatker);
                });
                $data = $data->values();
            }

            // return $this->successResponse($data, 'Data berhasil diambil');

            if ($search) {
                $data = $data->filter(function ($item) use ($search) {
                    return false !== stristr($item['no_kontrak'], $search);
                });
                $data = $data->values();
            }

            return $this->successResponse($data, 'Data berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function getActiveContract(Request $request)
    {
        sleep(2);
        $return = [];
        $datas = RealisasiSubKegiatanKontrak::where('sub_kegiatan_id', $request->subKegiatanId)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->get();

        foreach ($datas as $data) {
            $return[] = [
                'id' => $data->id,
                'sub_kegiatan_id' => $data->sub_kegiatan_id,
                'no_kontrak' => $data->no_kontrak,
                'kd_tender' => $data->kd_tender,
                'data_spse' => json_decode($data->data_spse, true),
            ];
        }

        return $this->successResponse($return, 'Data Kontrak');
    }

    function addKontrak(Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        DB::beginTransaction();
        try {

            $subKegiatan = SubKegiatan::find($request->id);
            if (!$subKegiatan) {
                return $this->errorResponse('Sub Kegiatan tidak ditemukan', 200);
            }

            for ($month = 1; $month <= 12; $month++) {
                $data = RealisasiSubKegiatanKontrak::firstOrNew([
                    'instance_id' => $subKegiatan->instance_id,
                    'urusan_id' => $subKegiatan->urusan_id,
                    'bidang_urusan_id' => $subKegiatan->bidang_id,
                    'program_id' => $subKegiatan->program_id,
                    'kegiatan_id' => $subKegiatan->kegiatan_id,
                    'sub_kegiatan_id' => $subKegiatan->id,
                    'year' => $request->year,
                    'month' => $month,
                    'no_kontrak' => $request->data['no_kontrak'],
                    'kd_tender' => $request->data['kd_tender'],
                ], [
                    'data_spse' => json_encode($request->data, true),
                    'created_by' => auth()->user()->id,
                    'updated_by' => auth()->user()->id,
                ]);
                $data->save();
            }

            $newLogs = [];
            $oldLogs = DB::table('log_users')
                ->where('date', date('Y-m-d'))
                ->where('user_id', auth()->id())
                ->first();
            if ($oldLogs) {
                $newLogs = json_decode($oldLogs->logs);
            }
            $newLogs[] = [
                'action' => 'realisasi-kontrak@update',
                'id' => $subKegiatan->id,
                'description' => 'Memperbarui Kontrak Realisasi ' . ($subKegiatan->fullcode ?? '') . ' - ' . ($subKegiatan->name ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            DB::table('log_users')
                ->updateOrInsert([
                    'date' => date('Y-m-d'),
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                ], [
                    'logs' => json_encode($newLogs),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            return $this->successResponse(null, 'Kontrak berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteKontrak($subKegiatanId, Request $request)
    {
        if ($this->isAbleToInput == false) {
            return $this->errorResponse($this->globalMessage, 200);
        }

        DB::beginTransaction();
        try {
            $data = RealisasiSubKegiatanKontrak::where('sub_kegiatan_id', $subKegiatanId)
                ->where('year', $request->year)
                // ->where('month', $request->month)
                ->where('no_kontrak', $request->no_kontrak)
                ->get();
            foreach ($data as $d) {
                $d->deleted_by = auth()->user()->id;
                $d->save();
                $d->delete();
            }


            $newLogs = [];
            $oldLogs = DB::table('log_users')
                ->where('date', date('Y-m-d'))
                ->where('user_id', auth()->id())
                ->first();
            if ($oldLogs) {
                $newLogs = json_decode($oldLogs->logs);
            }
            $subKegiatan = SubKegiatan::find($subKegiatanId);
            $newLogs[] = [
                'action' => 'realisasi-kontrak@delete',
                'id' => $subKegiatan->id,
                'description' => 'Memperbarui Kontrak Realisasi ' . ($subKegiatan->fullcode ?? '') . ' - ' . ($subKegiatan->name ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            DB::table('log_users')
                ->updateOrInsert([
                    'date' => date('Y-m-d'),
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                ], [
                    'logs' => json_encode($newLogs),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
