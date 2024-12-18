<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Instance;
use Carbon\CarbonPeriod;
use App\Models\Caram\Apbd;
use App\Models\Ref\Bidang;
use App\Models\Ref\Satuan;
use App\Models\Ref\Urusan;
use App\Models\Caram\Renja;
use App\Models\Caram\RPJMD;
use App\Models\Ref\Periode;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Renstra;
use App\Models\Data\Realisasi;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Ref\KodeRekening1;
use App\Models\Ref\KodeRekening2;
use App\Models\Ref\KodeRekening3;
use App\Models\Ref\KodeRekening4;
use App\Models\Ref\KodeRekening5;
use App\Models\Ref\KodeRekening6;
use App\Models\Caram\ApbdKegiatan;
use App\Models\Data\TargetKinerja;
use App\Models\Ref\KodeSumberDana;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RenjaKegiatan;
use App\Models\Caram\RPJMDAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Caram\RPJMDIndikator;
use App\Models\Caram\ApbdSubKegiatan;
use App\Models\Caram\RenstraKegiatan;
use App\Models\Ref\IndikatorKegiatan;
use App\Models\Caram\RenjaSubKegiatan;
use Illuminate\Support\Facades\Storage;
use App\Models\Caram\RenstraSubKegiatan;
use App\Models\Ref\IndikatorSubKegiatan;
use App\Notifications\GlobalNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class MasterCaramController extends Controller
{
    use JsonReturner;

    function listRefUrusan(Request $request)
    {
        try {
            $datas = [];
            $gets = Urusan::search($request->search)
                ->where('periode_id', $request->periode ?? null)
                ->get();

            foreach ($gets as $dt) {
                $datas[] = [
                    'id' => $dt->id,
                    'name' => $dt->name,
                    'code' => $dt->code,
                    'fullcode' => $dt->fullcode,
                    'status' => $dt->status,
                    'created_at' => $dt->created_at,
                    'updated_at' => $dt->updated_at,
                    'created_by' => $dt->CreatedBy->fullname ?? '-',
                    'updated_by' => $dt->UpdatedBy->fullname ?? '-',
                ];
            }
            return $this->successResponse($datas, 'List master urusan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function createRefUrusan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
        ], [], [
            'name' => 'Nama',
            'code' => 'Kode',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = Urusan::search($request->search)->insert([
                'name' => $request->name,
                'code' => $request->code,
                'fullcode' => $request->code,
                'description' => $request->description,
                'periode_id' => $request->periode_id,
                'status' => 'active',
                'created_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return $this->successResponse($data, 'Master urusan berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function detailRefUrusan($id, Request $request)
    {
        try {
            $data = Urusan::search($request->search)
                ->where('id', $id)
                ->first();
            return $this->successResponse($data, 'Detail master urusan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefUrusan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
        ], [], [
            'name' => 'Nama',
            'code' => 'Kode',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $data = Urusan::find($id);
            $data->name = $request->name;
            $data->code = $request->code;
            $data->fullcode = $request->code;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->updated_by = auth()->user()->id ?? null;
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master urusan berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine() . ' - ' . $th->getFile());
        }
    }

    function deleteRefUrusan($id)
    {
        DB::beginTransaction();
        try {
            $data = Urusan::where('id', $id)
                ->delete();
            DB::commit();
            return $this->successResponse($data, 'Master urusan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }



    function listRefBidang(Request $request)
    {
        try {
            $datas = [];
            $urusans = Urusan::where('periode_id', $request->periode ?? null)
                ->get();
            foreach ($urusans as $urusan) {
                $bidangs = Bidang::search($request->search)
                    ->where('periode_id', $request->periode ?? null)
                    ->where('urusan_id', $urusan->id)
                    ->get();
                if (count($bidangs) > 0 || !$request->search) {
                    $datas[] = [
                        'id' => $urusan->id,
                        'type' => 'urusan',
                        'name' => $urusan->name,
                        'code' => $urusan->code,
                        'fullcode' => $urusan->fullcode,
                        'description' => $urusan->description,
                        'periode_id' => $urusan->periode_id,
                        'status' => $urusan->status,
                        'created_by' => $urusan->CreatedBy->fullname ?? '-',
                        'updated_by' => $urusan->UpdatedBy->fullname ?? '-',
                        'created_at' => $urusan->created_at,
                        'updated_at' => $urusan->updated_at,
                    ];
                }
                foreach ($bidangs as $bidang) {
                    $datas[] = [
                        'id' => $bidang->id,
                        'type' => 'bidang',
                        'name' => $bidang->name,
                        'code' => $bidang->code,
                        'parent_code' => $urusan->fullcode,
                        'fullcode' => $bidang->fullcode,
                        'description' => $bidang->description,
                        'periode_id' => $bidang->periode_id,
                        'status' => $bidang->status,
                        'created_by' => $bidang->CreatedBy->fullname ?? '-',
                        'updated_by' => $bidang->UpdatedBy->fullname ?? '-',
                        'created_at' => $bidang->created_at,
                        'updated_at' => $bidang->updated_at,
                    ];
                }
            }
            return $this->successResponse($datas, 'List master bidang');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function createRefBidang(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'code' => 'Kode',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $urusan = Urusan::find($request->urusan_id);
            $data = Bidang::search($request->search)->insert([
                'name' => $request->name,
                'code' => $request->code,
                'fullcode' => $urusan->fullcode . '.' . $request->code,
                'description' => $request->description,
                'periode_id' => $request->periode_id,
                'urusan_id' => $request->urusan_id,
                'status' => 'active',
                'created_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return $this->successResponse($data, 'Master Bidang berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function detailRefBidang($id, Request $request)
    {
        try {
            $data = Bidang::search($request->search)
                ->where('id', $id)
                ->first();
            $data = [
                'id' => $data->id,
                'type' => 'bidang',
                'name' => $data->name,
                'urusan_id' => $data->urusan_id,
                'code' => $data->code,
                'parent_code' => $data->Urusan->fullcode,
                'fullcode' => $data->fullcode,
                'description' => $data->description,
                'periode_id' => $data->periode_id,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master Bidang');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefBidang($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'code' => 'Kode',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $urusan = Urusan::find($request->urusan_id);
            $data = Bidang::find($id);
            $data->name = $request->name;
            $data->code = $request->code;
            $data->fullcode = $urusan->fullcode . '.' . $request->code;
            $data->urusan_id = $request->urusan_id;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->updated_by = auth()->user()->id ?? null;
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Bidang berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function deleteRefBidang($id)
    {
        DB::beginTransaction();
        try {
            $data = Bidang::where('id', $id)
                ->delete();
            DB::commit();
            return $this->successResponse($data, 'Master Bidang berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }



    function listRefProgram(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
        ], [], [
            // 'bidang_id' => 'Bidang',
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $datas = [];
            $urusans = Urusan::where('periode_id', $request->periode ?? null)
                ->get();
            foreach ($urusans as $urusan) {
                $bidangs = Bidang::where('periode_id', $request->periode ?? null)
                    ->where('urusan_id', $urusan->id)
                    ->get();
                foreach ($bidangs as $bidang) {
                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $progs = auth()->user()->MyPermissions()->pluck('program_id');
                        $progs = collect($progs)->unique()->values();

                        $programs = Program::search($request->search)
                            ->whereIn('id', $progs)
                            ->where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->get();
                    } else {
                        $programs = Program::search($request->search)
                            ->where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->get();
                    }
                    if (count($programs) > 0) {
                        $datas[] = [
                            'id' => $bidang->id,
                            'type' => 'bidang',
                            'name' => $bidang->name,
                            'code' => $bidang->code,
                            'parent_code' => $urusan->fullcode,
                            'fullcode' => $bidang->fullcode,
                            'description' => $bidang->description,
                            'periode_id' => $bidang->periode_id,
                            'status' => $bidang->status,
                            'created_by' => $bidang->CreatedBy->fullname ?? '-',
                            'updated_by' => $bidang->UpdatedBy->fullname ?? '-',
                            'created_at' => $bidang->created_at,
                            'updated_at' => $bidang->updated_at,
                        ];
                    }
                    foreach ($programs as $program) {
                        $datas[] = [
                            'id' => $program->id,
                            'type' => 'program',
                            'name' => $program->name,
                            'code' => $program->code,
                            'urusan_id' => $program->urusan_id,
                            'bidang_id' => $program->bidang_id,
                            'instance_id' => $program->instance_id,
                            'parent_code' => $bidang->fullcode,
                            'fullcode' => $program->fullcode,
                            'description' => $program->description,
                            'periode_id' => $program->periode_id,
                            'status' => $program->status,
                            'created_by' => $program->CreatedBy->fullname ?? '-',
                            'updated_by' => $program->UpdatedBy->fullname ?? '-',
                            'created_at' => $program->created_at,
                            'updated_at' => $program->updated_at,
                        ];
                    }
                }
            }

            return $this->successResponse($datas, 'List master program');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function createRefProgram(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code' => 'Kode',
            // 'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $bidang = Bidang::where('id', $request->bidang_id)->firstOrFail();
            $data = Program::search($request->search)->insert([
                'name' => $request->name,
                'code' => $request->code,
                'fullcode' => $bidang->fullcode . '.' . $request->code,
                'description' => $request->description,
                'periode_id' => $request->periode_id,
                'urusan_id' => $bidang->urusan_id,
                'bidang_id' => $request->bidang_id,
                'instance_id' => $request->instance_id,
                'status' => 'active',
                'created_by' => auth()->user()->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return $this->successResponse($data, 'Master Program berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function detailRefProgram($id, Request $request)
    {
        try {
            $data = Program::search($request->search)
                ->where('id', $id)
                ->first();
            $data = [
                'id' => $data->id,
                'type' => 'program',
                'name' => $data->name,
                'code' => $data->code,
                'urusan_id' => $data->urusan_id,
                'bidang_id' => $data->bidang_id,
                'instance_id' => $data->instance_id,
                'parent_code' => $data->Bidang->fullcode,
                'fullcode' => $data->fullcode,
                'description' => $data->description,
                'periode_id' => $data->periode_id,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master Program');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefProgram($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code' => 'Kode',
            // 'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $bidang = Bidang::where('id', $request->bidang_id)->firstOrFail();
            $data = Program::where('id', $id)->firstOrFail();
            $data->name = $request->name;
            $data->code = $request->code;
            $data->fullcode = $bidang->fullcode . '.' . $request->code;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->urusan_id = $bidang->urusan_id;
            $data->bidang_id = $request->bidang_id;
            $data->instance_id = $request->instance_id;
            $data->updated_by = auth()->user()->id ?? null;
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Program berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function deleteRefProgram($id)
    {
        DB::beginTransaction();
        try {
            $data = Program::where('id', $id)
                ->delete();
            DB::commit();
            return $this->successResponse($data, 'Master Program berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }


    function listRefKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
        ], [], [
            // 'bidang_id' => 'Bidang',
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $datas = [];
            $urusans = Urusan::where('periode_id', $request->periode ?? null)
                ->get();
            foreach ($urusans as $urusan) {
                $bidangs = Bidang::where('periode_id', $request->periode ?? null)
                    ->where('urusan_id', $urusan->id)
                    ->get();

                foreach ($bidangs as $bidang) {
                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $progs = auth()->user()->MyPermissions()->pluck('program_id');
                        $progs = collect($progs)->unique()->values();

                        $programs = Program::whereIn('id', $progs)
                            ->where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->orderBy('fullcode', 'asc')
                            ->get();
                    } else {
                        $programs = Program::where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->get();
                    }
                    if (count($programs) > 0) {
                        foreach ($programs as $program) {
                            if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                                $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                                $kegs = collect($kegs)->unique()->values();
                                $kegiatans = Kegiatan::search($request->search)
                                    ->whereIn('id', $kegs)
                                    ->where('instance_id', $request->instance)
                                    ->where('periode_id', $request->periode ?? null)
                                    ->where('urusan_id', $urusan->id)
                                    ->where('bidang_id', $bidang->id)
                                    ->where('program_id', $program->id)
                                    ->get();
                            } else {
                                $kegiatans = Kegiatan::search($request->search)
                                    ->where('instance_id', $request->instance)
                                    ->where('periode_id', $request->periode ?? null)
                                    ->where('urusan_id', $urusan->id)
                                    ->where('bidang_id', $bidang->id)
                                    ->where('program_id', $program->id)
                                    ->get();
                            }
                            if (count($kegiatans) > 0) {
                                $datas[] = [
                                    'id' => $program->id,
                                    'type' => 'program',
                                    'name' => $program->name,
                                    'code' => $program->code,
                                    'urusan_id' => $program->urusan_id,
                                    'bidang_id' => $program->bidang_id,
                                    'instance_id' => $program->instance_id,
                                    'parent_code' => $bidang->fullcode,
                                    'fullcode' => $program->fullcode,
                                    'description' => $program->description,
                                    'periode_id' => $program->periode_id,
                                    'status' => $program->status,
                                    'created_by' => $program->CreatedBy->fullname ?? '-',
                                    'updated_by' => $program->UpdatedBy->fullname ?? '-',
                                    'created_at' => $program->created_at,
                                    'updated_at' => $program->updated_at,
                                ];
                            }
                            foreach ($kegiatans as $kegiatan) {
                                $datas[] = [
                                    'id' => $kegiatan->id,
                                    'type' => 'kegiatan',
                                    'name' => $kegiatan->name,
                                    'code_1' => $kegiatan->code_1,
                                    'code_2' => $kegiatan->code_2,
                                    'urusan_id' => $kegiatan->urusan_id,
                                    'bidang_id' => $kegiatan->bidang_id,
                                    'program_id' => $kegiatan->program_id,
                                    'instance_id' => $kegiatan->instance_id,
                                    'parent_code' => $program->fullcode,
                                    'fullcode' => $kegiatan->fullcode,
                                    'description' => $kegiatan->description,
                                    'periode_id' => $kegiatan->periode_id,
                                    'status' => $kegiatan->status,
                                    'created_by' => $kegiatan->CreatedBy->fullname ?? '-',
                                    'updated_by' => $kegiatan->UpdatedBy->fullname ?? '-',
                                    'created_at' => $kegiatan->created_at,
                                    'updated_at' => $kegiatan->updated_at,
                                ];
                            }
                        }
                    }
                }
            }

            return $this->successResponse($datas, 'List master kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function createRefKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            'program_id' => 'required|integer|exists:ref_program,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code_1' => 'required|string|max:255',
            'code_2' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'program_id' => 'Program',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code_1' => 'Kode 1',
            'code_2' => 'Kode 2',
            // 'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $program = Program::where('id', $request->program_id)->firstOrFail();
            $data = new Kegiatan();
            $data->name = $request->name;
            $data->code_1 = $request->code_1;
            $data->code_2 = $request->code_2;
            $data->fullcode = $program->fullcode . '.' . $request->code_1 . '.' . $request->code_2;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->urusan_id = $program->urusan_id;
            $data->bidang_id = $program->bidang_id;
            $data->program_id = $request->program_id;
            $data->instance_id = $request->instance_id;
            $data->status = 'active';
            $data->created_by = auth()->user()->id ?? null;
            $data->created_at = now();
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Kegiatan berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function detailRefKegiatan($id, Request $request)
    {
        try {
            $data = Kegiatan::search($request->search)
                ->where('id', $id)
                ->first();
            $data = [
                'id' => $data->id,
                'type' => 'kegiatan',
                'name' => $data->name,
                'code_1' => $data->code_1,
                'code_2' => $data->code_2,
                'urusan_id' => $data->urusan_id,
                'bidang_id' => $data->bidang_id,
                'program_id' => $data->program_id,
                'instance_id' => $data->instance_id,
                'parent_code' => $data->Program->fullcode,
                'fullcode' => $data->fullcode,
                'description' => $data->description,
                'periode_id' => $data->periode_id,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master Kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            'program_id' => 'required|integer|exists:ref_program,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code_1' => 'required|string|max:255',
            'code_2' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'program_id' => 'Program',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code_1' => 'Kode 1',
            'code_2' => 'Kode 2',
            // 'urusan_id' => 'Urusan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $program = Program::where('id', $request->program_id)->firstOrFail();
            $data = Kegiatan::where('id', $id)->firstOrFail();
            $data->name = $request->name;
            $data->code_1 = $request->code_1;
            $data->code_2 = $request->code_2;
            $data->fullcode = $program->fullcode . '.' . $request->code_1 . '.' . $request->code_2;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->urusan_id = $program->urusan_id;
            $data->bidang_id = $program->bidang_id;
            $data->program_id = $request->program_id;
            $data->instance_id = $request->instance_id;
            $data->updated_by = auth()->user()->id ?? null;
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Kegiatan berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function deleteRefKegiatan($id)
    {
        DB::beginTransaction();
        try {
            $data = Kegiatan::where('id', $id)
                ->delete();
            DB::commit();
            return $this->successResponse($data, 'Master Kegiatan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }


    function listRefSubKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
        ], [], [
            // 'bidang_id' => 'Bidang',
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $datas = [];
            $urusans = Urusan::where('periode_id', $request->periode ?? null)
                ->orderBy('fullcode', 'asc')
                ->get();
            foreach ($urusans as $urusan) {
                $bidangs = Bidang::where('periode_id', $request->periode ?? null)
                    ->where('urusan_id', $urusan->id)
                    ->orderBy('fullcode', 'asc')
                    ->get();

                foreach ($bidangs as $bidang) {
                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $progs = auth()->user()->MyPermissions()->pluck('program_id');
                        $progs = collect($progs)->unique()->values();

                        $programs = Program::whereIn('id', $progs)
                            ->where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->get();
                    } else {
                        $programs = Program::where('instance_id', $request->instance)
                            ->where('periode_id', $request->periode ?? null)
                            ->where('urusan_id', $urusan->id)
                            ->where('bidang_id', $bidang->id)
                            ->orderBy('fullcode', 'asc')
                            ->get();
                    }
                    if (count($programs) > 0) {
                        foreach ($programs as $program) {
                            if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                                $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                                $kegs = collect($kegs)->unique()->values();
                                $kegiatans = Kegiatan::whereIn('id', $kegs)
                                    ->where('instance_id', $request->instance)
                                    ->where('periode_id', $request->periode ?? null)
                                    ->where('urusan_id', $urusan->id)
                                    ->where('bidang_id', $bidang->id)
                                    ->where('program_id', $program->id)
                                    ->get();
                            } else {
                                $kegiatans = Kegiatan::where('instance_id', $request->instance)
                                    ->where('periode_id', $request->periode ?? null)
                                    ->where('urusan_id', $urusan->id)
                                    ->where('bidang_id', $bidang->id)
                                    ->where('program_id', $program->id)
                                    ->orderBy('fullcode', 'asc')
                                    ->get();
                            }
                            if (count($kegiatans) > 0) {
                                foreach ($kegiatans as $kegiatan) {
                                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                                        $subKegs = auth()->user()->MyPermissions()->pluck('sub_kegiatan_id');
                                        $subKegs = collect($subKegs)->unique()->values();
                                        $subkegiatans = SubKegiatan::search($request->search)
                                            ->whereIn('id', $subKegs)
                                            ->where('instance_id', $request->instance)
                                            ->where('periode_id', $request->periode ?? null)
                                            ->where('urusan_id', $urusan->id)
                                            ->where('bidang_id', $bidang->id)
                                            ->where('program_id', $program->id)
                                            ->where('kegiatan_id', $kegiatan->id)
                                            ->orderBy('fullcode', 'asc')
                                            ->get();
                                    } else {
                                        $subkegiatans = SubKegiatan::search($request->search)
                                            ->where('instance_id', $request->instance)
                                            ->where('periode_id', $request->periode ?? null)
                                            ->where('urusan_id', $urusan->id)
                                            ->where('bidang_id', $bidang->id)
                                            ->where('program_id', $program->id)
                                            ->where('kegiatan_id', $kegiatan->id)
                                            ->orderBy('fullcode', 'asc')
                                            ->get();
                                    }
                                    if (count($subkegiatans) > 0) {
                                        $datas[] = [
                                            'id' => $kegiatan->id,
                                            'type' => 'kegiatan',
                                            'name' => $kegiatan->name,
                                            'code_1' => $kegiatan->code_1,
                                            'code_2' => $kegiatan->code_2,
                                            'urusan_id' => $kegiatan->urusan_id,
                                            'bidang_id' => $kegiatan->bidang_id,
                                            'program_id' => $kegiatan->program_id,
                                            'instance_id' => $kegiatan->instance_id,
                                            'parent_code' => $program->fullcode,
                                            'fullcode' => $kegiatan->fullcode,
                                            'description' => $kegiatan->description,
                                            'periode_id' => $kegiatan->periode_id,
                                            'status' => $kegiatan->status,
                                            'created_by' => $kegiatan->CreatedBy->fullname ?? '-',
                                            'updated_by' => $kegiatan->UpdatedBy->fullname ?? '-',
                                            'created_at' => $kegiatan->created_at,
                                            'updated_at' => $kegiatan->updated_at,
                                        ];
                                    }
                                    foreach ($subkegiatans as $subkegiatan) {
                                        $datas[] = [
                                            'id' => $subkegiatan->id,
                                            'type' => 'sub-kegiatan',
                                            'name' => $subkegiatan->name,
                                            'code' => $subkegiatan->code,
                                            'urusan_id' => $subkegiatan->urusan_id,
                                            'bidang_id' => $subkegiatan->bidang_id,
                                            'program_id' => $subkegiatan->program_id,
                                            'kegiatan_id' => $subkegiatan->kegiatan_id,
                                            'instance_id' => $subkegiatan->instance_id,
                                            'parent_code' => $kegiatan->fullcode,
                                            'fullcode' => $subkegiatan->fullcode,
                                            'description' => $subkegiatan->description,
                                            'periode_id' => $subkegiatan->periode_id,
                                            'status' => $subkegiatan->status,
                                            'created_by' => $subkegiatan->CreatedBy->fullname ?? '-',
                                            'updated_by' => $subkegiatan->UpdatedBy->fullname ?? '-',
                                            'created_at' => $subkegiatan->created_at,
                                            'updated_at' => $subkegiatan->updated_at,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $this->successResponse($datas, 'List master subkegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function createRefSubKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            // 'program_id' => 'required|integer|exists:ref_program,id',
            'kegiatan_id' => 'required|integer|exists:ref_kegiatan,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'program_id' => 'Program',
            'kegiatan_id' => 'Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code' => 'Kode',
            // 'urusan_id' => 'Urusan',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::where('id', $request->kegiatan_id)->firstOrFail();
            $data = new SubKegiatan();
            $data->name = $request->name;
            $data->code = $request->code;
            $data->fullcode = $kegiatan->fullcode . '.' . $request->code;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->urusan_id = $kegiatan->urusan_id;
            $data->bidang_id = $kegiatan->bidang_id;
            $data->program_id = $kegiatan->program_id;
            $data->kegiatan_id = $request->kegiatan_id;
            $data->instance_id = $request->instance_id;
            $data->status = 'active';
            $data->created_by = auth()->user()->id ?? null;
            $data->created_at = now();
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Sub Kegiatan berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }

    function detailRefSubKegiatan($id, Request $request)
    {
        try {
            $data = SubKegiatan::where('id', $id)
                ->firstOrFail();
            $data = [
                'id' => $data->id,
                'type' => 'sub-kegiatan',
                'name' => $data->name,
                'code' => $data->code,
                'urusan_id' => $data->urusan_id,
                'bidang_id' => $data->bidang_id,
                'program_id' => $data->program_id,
                'kegiatan_id' => $data->kegiatan_id,
                'instance_id' => $data->instance_id,
                'parent_code' => $data->Kegiatan->fullcode,
                'fullcode' => $data->fullcode,
                'description' => $data->description,
                'periode_id' => $data->periode_id,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master Sub Kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            // 'bidang_id' => 'required|integer|exists:ref_bidang_urusan,id',
            // 'program_id' => 'required|integer|exists:ref_program,id',
            'kegiatan_id' => 'required|integer|exists:ref_kegiatan,id',
            'description' => 'nullable|string|max:255',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            'code' => 'required|string|max:255',
            // 'urusan_id' => 'required|integer|exists:ref_urusan,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'bidang_id' => 'Bidang',
            'program_id' => 'Program',
            'kegiatan_id' => 'Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'code' => 'Kode',
            // 'urusan_id' => 'Urusan',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::where('id', $request->kegiatan_id)->firstOrFail();
            $data = SubKegiatan::where('id', $id)->firstOrFail();
            $data->name = $request->name;
            $data->code = $request->code;
            $data->description = $request->description;
            $data->periode_id = $request->periode_id;
            $data->urusan_id = $kegiatan->urusan_id;
            $data->bidang_id = $kegiatan->bidang_id;
            $data->program_id = $kegiatan->program_id;
            $data->kegiatan_id = $request->kegiatan_id;
            $data->instance_id = $request->instance_id;
            $data->updated_by = auth()->user()->id ?? null;
            $data->updated_at = now();
            $data->save();

            DB::commit();
            return $this->successResponse($data, 'Master Sub Kegiatan berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine() . ' - ' . $th->getFile());
        }
    }

    function deleteRefSubKegiatan($id)
    {
        DB::beginTransaction();
        try {
            $data = SubKegiatan::where('id', $id)
                ->delete();
            DB::commit();
            return $this->successResponse($data, 'Master Sub Kegiatan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage());
        }
    }



    function listRefIndikatorKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            'kegiatan' => 'required|integer|exists:ref_kegiatan,id',
        ], [], [
            // 'bidang_id' => 'Bidang',
            'instance' => 'Perangkat Daerah',
            'kegiatan' => 'Kegiatan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $kegiatan = Kegiatan::where('id', $request->kegiatan)->firstOrFail();
            $pivots = DB::table('con_indikator_kinerja_kegiatan')
                ->where('instance_id', $request->instance)
                ->where('program_id', $kegiatan->program_id)
                ->where('kegiatan_id', $request->kegiatan)
                ->get();
            $indikators = DB::table('ref_indikator_kinerja_kegiatan')
                ->where('deleted_at', null)
                ->whereIn('pivot_id', $pivots->pluck('id')->toArray())
                ->orderBy('created_at', 'asc')
                ->get();
            $datas = [];
            foreach ($indikators as $indi) {
                $datas[] = [
                    'id' => $indi->id,
                    'name' => $indi->name,
                    'status' => $indi->status,
                    'kegiatan_name' => $kegiatan->name,
                    'created_by' => $indi->created_by,
                    'updated_by' => $indi->updated_by,
                    'created_at' => $indi->created_at,
                    'updated_at' => $indi->updated_at,
                ];
            }

            return $this->successResponse($datas, 'List master indikator kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function createRefIndikatorKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'kegiatan_id' => 'required|integer|exists:ref_kegiatan,id',
            'periode_id' => 'required|integer|exists:ref_periode,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'kegiatan_id' => 'Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'status' => 'Status',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::where('id', $request->kegiatan_id)->firstOrFail();
            $pivots = DB::table('con_indikator_kinerja_kegiatan')
                ->where('instance_id', $request->instance_id)
                ->where('program_id', $kegiatan->program_id)
                ->where('kegiatan_id', $request->kegiatan_id)
                ->first()
                ->id ?? null;
            if (!$pivots) {
                $pivots = DB::table('con_indikator_kinerja_kegiatan')
                    ->insertGetId([
                        'instance_id' => $request->instance_id,
                        'program_id' => $kegiatan->program_id,
                        'kegiatan_id' => $request->kegiatan_id,
                        'status' => 'active',
                        'created_by' => auth()->user()->id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            $data = DB::table('ref_indikator_kinerja_kegiatan')
                ->insertGetId([
                    'name' => str()->squish($request->name),
                    'pivot_id' => $pivots,
                    'status' => 'active',
                    'created_by' => auth()->user()->id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse($data ?? [], 'Master indikator kegiatan berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }

    function detailRefIndikatorKegiatan($id, Request $request)
    {
        try {
            $data = DB::table('ref_indikator_kinerja_kegiatan')
                ->where('id', $id)
                ->first();
            $pivots = DB::table('con_indikator_kinerja_kegiatan')
                ->where('id', $data->pivot_id)
                ->first();
            $kegiatan = Kegiatan::where('id', $pivots->kegiatan_id)->firstOrFail();
            $data = [
                'id' => $data->id,
                'name' => $data->name,
                'kegiatan_id' => $kegiatan->id,
                'kegiatan_name' => $kegiatan->name,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master indikator kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefIndikatorKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'kegiatan_id' => 'required|integer|exists:ref_kegiatan,id',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            // 'status' => 'required|string|max:255',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'kegiatan_id' => 'Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'status' => 'Status',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            DB::table('ref_indikator_kinerja_kegiatan')
                ->where('id', $id)
                ->update([
                    'name' => str()->squish($request->name),
                    'updated_by' => auth()->user()->id ?? null,
                    'updated_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse([], 'Master indikator kegiatan berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }

    function deleteRefIndikatorKegiatan($id)
    {
        DB::beginTransaction();
        try {
            DB::table('ref_indikator_kinerja_kegiatan')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                ]);
            // ->delete();
            DB::commit();
            return $this->successResponse([], 'Master indikator kegiatan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }


    function listRefIndikatorSubKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|numeric|exists:instances,id',
            'subkegiatan' => 'required|integer|exists:ref_sub_kegiatan,id',
        ], [], [
            // 'bidang_id' => 'Bidang',
            'instance' => 'Perangkat Daerah',
            'subkegiatan' => 'Sub Kegiatan',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            $subkegiatan = SubKegiatan::where('id', $request->subkegiatan)->firstOrFail();
            $pivots = DB::table('con_indikator_kinerja_sub_kegiatan')
                ->where('instance_id', $request->instance)
                ->where('program_id', $subkegiatan->program_id)
                ->where('kegiatan_id', $subkegiatan->kegiatan_id)
                ->where('sub_kegiatan_id', $request->subkegiatan)
                ->get();
            $indikators = DB::table('ref_indikator_kinerja_sub_kegiatan')
                ->where('deleted_at', null)
                ->whereIn('pivot_id', $pivots->pluck('id')->toArray())
                // ->sortByDesc('created_at')
                ->orderBy('created_at', 'asc')
                ->get();
            $datas = [];
            foreach ($indikators as $indi) {
                $datas[] = [
                    'id' => $indi->id,
                    'name' => $indi->name,
                    'status' => $indi->status,
                    'subkegiatan_name' => $subkegiatan->name,
                    'created_by' => $indi->created_by,
                    'updated_by' => $indi->updated_by,
                    'created_at' => $indi->created_at,
                    'updated_at' => $indi->updated_at,
                ];
            }

            return $this->successResponse($datas, 'List master indikator sub kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function createRefIndikatorSubKegiatan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'sub_kegiatan_id' => 'required|integer|exists:ref_sub_kegiatan,id',
            'periode_id' => 'required|integer|exists:ref_periode,id',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'sub_kegiatan_id' => 'Sub Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'status' => 'Status',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            $subkegiatan = SubKegiatan::where('id', $request->sub_kegiatan_id)->firstOrFail();
            $pivots = DB::table('con_indikator_kinerja_sub_kegiatan')
                ->where('instance_id', $request->instance_id)
                ->where('program_id', $subkegiatan->program_id)
                ->where('kegiatan_id', $subkegiatan->kegiatan_id)
                ->where('sub_kegiatan_id', $request->sub_kegiatan_id)
                ->first()
                ->id ?? null;
            if (!$pivots) {
                $pivots = DB::table('con_indikator_kinerja_sub_kegiatan')
                    ->insertGetId([
                        'instance_id' => $request->instance_id,
                        'program_id' => $subkegiatan->program_id,
                        'kegiatan_id' => $subkegiatan->kegiatan_id,
                        'sub_kegiatan_id' => $request->sub_kegiatan_id,
                        'status' => 'active',
                        'created_by' => auth()->user()->id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            $data = DB::table('ref_indikator_kinerja_sub_kegiatan')
                ->insertGetId([
                    'name' => str()->squish($request->name),
                    'pivot_id' => $pivots,
                    'status' => 'active',
                    'created_by' => auth()->user()->id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse($data ?? [], 'Master indikator sub kegiatan berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }

    function detailRefIndikatorSubKegiatan($id, Request $request)
    {
        try {
            $data = DB::table('ref_indikator_kinerja_sub_kegiatan')
                ->where('id', $id)
                ->first();
            $pivots = DB::table('con_indikator_kinerja_sub_kegiatan')
                ->where('id', $data->pivot_id)
                ->first();
            $subkegiatan = SubKegiatan::where('id', $pivots->sub_kegiatan_id)->firstOrFail();
            $data = [
                'id' => $data->id,
                'name' => $data->name,
                'sub_kegiatan_id' => $subkegiatan->id,
                'sub_kegiatan_name' => $subkegiatan->name,
                'status' => $data->status,
                'created_by' => $data->created_by,
                'updated_by' => $data->updated_by,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];
            return $this->successResponse($data, 'Detail master indikator sub kegiatan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefIndikatorSubKegiatan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'instance_id' => 'required|integer|exists:instances,id',
            'sub_kegiatan_id' => 'required|integer|exists:ref_sub_kegiatan,id',
            'periode_id' => 'required|integer|exists:ref_periode,id',
            // 'status' => 'required|string|max:255',
        ], [], [
            'name' => 'Nama',
            'instance_id' => 'Perangkat Daerah',
            'sub_kegiatan_id' => 'Sub Kegiatan',
            'description' => 'Deskripsi',
            'periode_id' => 'Periode',
            'status' => 'Status',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            DB::table('ref_indikator_kinerja_sub_kegiatan')
                ->where('id', $id)
                ->update([
                    'name' => str()->squish($request->name),
                    'updated_by' => auth()->user()->id ?? null,
                    'updated_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse([], 'Master indikator sub kegiatan berhasil diubah');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }

    function deleteRefIndikatorSubKegiatan($id)
    {
        DB::beginTransaction();
        try {
            DB::table('ref_indikator_kinerja_sub_kegiatan')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse([], 'Master indikator sub kegiatan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' -> ' . $th->getLine() . ' -> ' . $th->getFile());
        }
    }


    function listCaramRPJMD(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $rpjmd = RPJMD::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->latest('id') // latest id karena Ada Duplikat dengan Program ID yang sama
                ->first();
            if (!$rpjmd) {
                $rpjmd = new RPJMD();
                $rpjmd->periode_id = $request->periode;
                $rpjmd->instance_id = $request->instance;
                $rpjmd->program_id = $request->program;
                $rpjmd->status = 'active';
                $rpjmd->save();
            }

            $periode = Periode::where('id', $request->periode)->first();
            $range = [];
            $anggaran = [];
            if ($periode) {
                $start = Carbon::parse($periode->start_date);
                $end = Carbon::parse($periode->end_date);
                for ($i = $start->year; $i <= $end->year; $i++) {
                    $range[] = $i;
                    $anggaran[$i] = null;
                }
            }

            foreach ($range as $year) {
                $rpjmdAnggaran = RPJMDAnggaran::where('rpjmd_id', $rpjmd->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->first();

                if (!$rpjmdAnggaran) {
                    $rpjmdAnggaran = new RPJMDAnggaran();
                    $rpjmdAnggaran->rpjmd_id = $rpjmd->id;
                    $rpjmdAnggaran->year = $year;
                    $rpjmdAnggaran->status = 'active';
                    $rpjmdAnggaran->save();
                }

                $anggaran[$year] = [
                    'id' => $rpjmdAnggaran->id,
                    'anggaran' => $rpjmdAnggaran->anggaran,
                    'year' => $rpjmdAnggaran->year,
                    'status' => $rpjmdAnggaran->status,
                    'created_by' => $rpjmdAnggaran->created_by,
                    'updated_by' => $rpjmdAnggaran->updated_by,
                    'created_at' => $rpjmdAnggaran->created_at,
                    'updated_at' => $rpjmdAnggaran->updated_at,
                ];

                $rpjmdIndikator = RPJMDIndikator::where('rpjmd_id', $rpjmd->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get();

                if (count($rpjmdIndikator) == 0) {
                    $rpjmdIndikator = new RPJMDIndikator();
                    $rpjmdIndikator->rpjmd_id = $rpjmd->id;
                    $rpjmdIndikator->year = $year;
                    $rpjmdIndikator->status = 'active';
                    $rpjmdIndikator->save();

                    $rpjmdIndikator = RPJMDIndikator::where('rpjmd_id', $rpjmd->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get();
                }

                foreach ($rpjmdIndikator as $key => $value) {
                    $indikator[$year][] = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'value' => $value->value,
                        'satuan_id' => $value->satuan_id,
                        'satuan_name' => $value->Satuan->name ?? null,
                        'year' => $value->year,
                        'status' => $value->status,
                        'created_by' => $value->CreatedBy->fullname ?? '-',
                        'updated_by' => $value->UpdatedBy->fullname ?? '-',
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
                    ];
                }
            }

            DB::commit();
            return $this->successResponse([
                'rpjmd' => $rpjmd,
                'range' => $range,
                'anggaran' => $anggaran,
                'indikator' => $indikator,
            ], 'Detail RPJMD');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function storeCaramRPJMD(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            'rpjmd' => 'required|numeric|exists:data_rpjmd,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            'rpjmd' => 'RPJMD',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $rpjmd = RPJMD::find($request->rpjmd);

            $arrAnggaran = [];
            $arrAnggaran = $request->data['anggaran'];
            foreach ($arrAnggaran as $input) {
                $anggaran = RPJMDAnggaran::find($input['id']);
                if (!$anggaran) {
                    $anggaran = new RPJMDAnggaran();
                    $anggaran->rpjmd_id = $rpjmd->id;
                    $anggaran->year = $input['year'];
                    $anggaran->status = 'active';
                }
                $anggaran->anggaran = $input['anggaran'];
                $anggaran->updated_by = auth()->user()->id ?? null;
                $anggaran->save();
            }

            $arrIndicators = $request->data['indikator'];
            foreach ($arrIndicators as $year => $inputs) {
                $indicatorsIds = collect($inputs)->pluck('id');

                // Delete from Deleted Data frontend
                RPJMDIndikator::where('year', $year)
                    ->where('rpjmd_id', $request->rpjmd)
                    ->whereNotIn('id', $indicatorsIds)
                    ->delete();
                // Ends

                foreach ($inputs as $input) {
                    $indikator = RPJMDIndikator::find($input['id'] ?? null);
                    if (!$indikator) {
                        $indikator = new RPJMDIndikator();
                        $indikator->rpjmd_id = $rpjmd->id;
                        $indikator->year = $year;
                        $indikator->status = 'active';
                        $indikator->created_by = auth()->id();
                    }
                    $indikator->name = $input['name'];
                    $indikator->value = $input['value'];
                    $indikator->satuan_id = $input['satuan_id'];
                    $indikator->updated_by = auth()->id();
                    $indikator->save();
                }
            }

            DB::commit();
            return $this->successResponse(null, 'RPJMD Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }




    function listCaramRenstra(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renstra' => 'required|numeric|exists:data_renstra,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renstra' => 'Renstra',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $datas = [];
            $renstra = Renstra::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->first();
            $rpjmd = RPJMD::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->latest('id') // latest id karena Ada Duplikat dengan Program ID yang sama
                ->first();
            if (!$renstra) {
                $renstra = new Renstra();
                $renstra->periode_id = $request->periode;
                $renstra->instance_id = $request->instance;
                $renstra->program_id = $request->program;
                $renstra->total_anggaran = 0;
                $renstra->total_kinerja = 0;
                $renstra->percent_anggaran = 0;
                $renstra->percent_kinerja = 0;
                $renstra->status = 'draft';
                $renstra->status_leader = 'draft';
                $renstra->created_by = auth()->user()->id ?? null;
            }
            $renstra->rpjmd_id = $rpjmd->id;
            $renstra->save();

            $periode = Periode::where('id', $request->periode)->first();
            $range = [];
            if ($periode) {
                $start = Carbon::parse($periode->start_date);
                $end = Carbon::parse($periode->end_date);
                for ($i = $start->year; $i <= $end->year; $i++) {
                    $range[] = $i;
                    $anggaran[$i] = null;
                }
            }

            foreach ($range as $year) {
                $program = Program::find($request->program);
                $indicators = [];
                $rpjmdIndicators = RPJMDIndikator::where('rpjmd_id', $rpjmd->id)
                    ->where('year', $year)
                    ->get();
                foreach ($rpjmdIndicators as $ind) {
                    $indicators[] = [
                        'id' => $ind->id,
                        'name' => $ind->name,
                        'value' => $ind->value,
                        'satuan_id' => $ind->satuan_id,
                        'satuan_name' => $ind->Satuan->name ?? null,
                    ];
                }
                $RenstraKegiatan = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get();
                $anggaranModal = $RenstraKegiatan->sum('anggaran_modal');
                $anggaranOperasi  = $RenstraKegiatan->sum('anggaran_operasi');
                $anggaranTransfer = $RenstraKegiatan->sum('anggaran_transfer');
                $anggaranTidakTerduga  = $RenstraKegiatan->sum('anggaran_tidak_terduga');
                $totalAnggaran = $RenstraKegiatan->sum('total_anggaran');

                $renstra->total_anggaran = $totalAnggaran;
                $renstra->percent_anggaran = 100;

                $averagePercentKinerja = $RenstraKegiatan->avg('percent_kinerja') ?? 0;
                $renstra->percent_kinerja = $averagePercentKinerja;
                $renstra->save();

                $datas[$year][] = [
                    'id' => $program->id,
                    'type' => 'program',
                    'rpjmd_id' => $renstra->rpjmd_id,
                    'rpjmd_data' => $renstra->RPJMD,
                    'indicators' => $indicators ?? null,

                    'anggaran_modal' => $anggaranModal,
                    'anggaran_operasi' => $anggaranOperasi,
                    'anggaran_transfer' => $anggaranTransfer,
                    'anggaran_tidak_terduga' => $anggaranTidakTerduga,

                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'program_fullcode' => $program->fullcode,
                    'total_anggaran' => $totalAnggaran,
                    'total_kinerja' => $renstra->total_kinerja,
                    'percent_anggaran' => $renstra->percent_anggaran,
                    'percent_kinerja' => $renstra->percent_kinerja,
                    'status' => $renstra->status,
                    'created_by' => $renstra->CreatedBy->fullname ?? '-',
                    'updated_by' => $renstra->UpdatedBy->fullname ?? '-',
                ];

                if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                    $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                    $kegs = collect($kegs)->unique()->values();
                    $kegiatans = Kegiatan::where('program_id', $program->id)
                        ->whereIn('id', $kegs)
                        ->where('status', 'active')
                        ->get();
                } else {
                    $kegiatans = Kegiatan::where('program_id', $program->id)
                        ->where('status', 'active')
                        ->get();
                }
                foreach ($kegiatans as $kegiatan) {
                    $renstraKegiatan = RenstraKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->first();

                    if (!$renstraKegiatan) {
                        $renstraKegiatan = new RenstraKegiatan();
                        $renstraKegiatan->renstra_id = $renstra->id;
                        $renstraKegiatan->program_id = $program->id;
                        $renstraKegiatan->kegiatan_id = $kegiatan->id;
                        $renstraKegiatan->year = $year;
                        $renstraKegiatan->anggaran_json = null;
                        $renstraKegiatan->kinerja_json = null;
                        $renstraKegiatan->satuan_json = null;
                        $renstraKegiatan->anggaran_modal = 0;
                        $renstraKegiatan->anggaran_operasi = 0;
                        $renstraKegiatan->anggaran_transfer = 0;
                        $renstraKegiatan->anggaran_tidak_terduga = 0;
                        $renstraKegiatan->total_anggaran = 0;
                        $renstraKegiatan->total_kinerja = 0;
                        $renstraKegiatan->percent_anggaran = 0;
                        $renstraKegiatan->percent_kinerja = 0;
                        $renstraKegiatan->status = 'active';
                        $renstraKegiatan->created_by = auth()->user()->id ?? null;
                        $renstraKegiatan->save();
                    }

                    // delete if duplicated data
                    RenstraKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->where('id', '!=', $renstraKegiatan->id)
                        ->delete();

                    $indicators = [];
                    $indikatorCons = DB::table('con_indikator_kinerja_kegiatan')
                        ->where('instance_id', $request->instance)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->first();
                    if ($indikatorCons) {
                        $indikators = IndikatorKegiatan::where('pivot_id', $indikatorCons->id)
                            ->get();
                        foreach ($indikators as $key => $indi) {
                            if ($renstraKegiatan->satuan_json) {
                                $satuanId = json_decode($renstraKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                            }
                            $indicators[] = [
                                'id' => $indi->id,
                                'name' => $indi->name,
                                'value' => json_decode($renstraKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id' => $satuanId ?? null,
                                'satuan_name' => $satuanName ?? null,
                            ];
                        }
                    }

                    $anggaranModal = 0;
                    $anggaranOperasi = 0;
                    $anggaranTransfer = 0;
                    $anggaranTidakTerduga = 0;
                    $anggaranModal = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_modal');
                    $anggaranOperasi = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_operasi');
                    $anggaranTransfer = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_transfer');
                    $anggaranTidakTerduga = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_tidak_terduga');
                    $totalAnggaran = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');

                    $renstraKegiatan->anggaran_modal = $anggaranModal;
                    $renstraKegiatan->anggaran_operasi = $anggaranOperasi;
                    $renstraKegiatan->anggaran_transfer = $anggaranTransfer;
                    $renstraKegiatan->anggaran_tidak_terduga = $anggaranTidakTerduga;
                    $renstraKegiatan->total_anggaran = $totalAnggaran;
                    $renstraKegiatan->save();


                    $datas[$year][] = [
                        'id' => $kegiatan->id,
                        'type' => 'kegiatan',
                        'program_id' => $renstraKegiatan->program_id,
                        'program_name' => $program->name,
                        'program_fullcode' => $program->fullcode,
                        'kegiatan_id' => $kegiatan->id,
                        'kegiatan_name' => $kegiatan->name,
                        'kegiatan_fullcode' => $kegiatan->fullcode,
                        'indicators' => $indicators,
                        'anggaran_json' => $renstraKegiatan->anggaran_json,
                        'kinerja_json' => $renstraKegiatan->kinerja_json,
                        'satuan_json' => $renstraKegiatan->satuan_json,

                        'anggaran_modal' => $renstraKegiatan->anggaran_modal,
                        'anggaran_operasi' => $renstraKegiatan->anggaran_operasi,
                        'anggaran_transfer' => $renstraKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga' => $renstraKegiatan->anggaran_tidak_terduga,

                        'total_anggaran' => $renstraKegiatan->total_anggaran,

                        'total_kinerja' => $renstraKegiatan->total_kinerja,
                        'percent_anggaran' => $renstraKegiatan->percent_anggaran,
                        'percent_kinerja' => $renstraKegiatan->percent_kinerja,
                        'year' => $renstraKegiatan->year,
                        'status' => $renstraKegiatan->status,
                        'created_by' => $renstraKegiatan->created_by,
                        'updated_by' => $renstraKegiatan->updated_by,
                        'created_by' => $renstraKegiatan->CreatedBy->fullname ?? '-',
                        'updated_by' => $renstraKegiatan->UpdatedBy->fullname ?? '-',
                    ];

                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $subKegs = auth()->user()->MyPermissions()->pluck('sub_kegiatan_id');
                        $subKegs = collect($subKegs)->unique()->values();

                        $subKegiatans = SubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->whereIn('id', $subKegs)
                            ->where('status', 'active')
                            ->get();
                    } else {
                        $subKegiatans = SubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('status', 'active')
                            ->get();
                    }
                    foreach ($subKegiatans as $subKegiatan) {
                        $renstraSubKegiatan = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            // ->where('parent_id', $renstraKegiatan->id)
                            ->where('year', $year)
                            ->where('status', 'active')
                            ->first();

                        if (!$renstraSubKegiatan) {
                            $renstraSubKegiatan = new RenstraSubKegiatan();
                            $renstraSubKegiatan->renstra_id = $renstra->id;
                            $renstraSubKegiatan->parent_id = $renstraKegiatan->id;
                            $renstraSubKegiatan->program_id = $program->id;
                            $renstraSubKegiatan->kegiatan_id = $kegiatan->id;
                            $renstraSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $renstraSubKegiatan->year = $year;
                            $renstraSubKegiatan->anggaran_json = null;
                            $renstraSubKegiatan->kinerja_json = null;
                            $renstraSubKegiatan->satuan_json = null;
                            $renstraSubKegiatan->anggaran_modal = 0;
                            $renstraSubKegiatan->anggaran_operasi = 0;
                            $renstraSubKegiatan->anggaran_transfer = 0;
                            $renstraSubKegiatan->anggaran_tidak_terduga = 0;
                            $renstraSubKegiatan->total_anggaran = 0;
                            $renstraSubKegiatan->total_kinerja = 0;
                            $renstraSubKegiatan->percent_anggaran = 0;
                            $renstraSubKegiatan->percent_kinerja = 0;
                            $renstraSubKegiatan->status = 'active';
                            $renstraSubKegiatan->created_by = auth()->user()->id ?? null;
                            $renstraSubKegiatan->save();
                        }
                        $renstraSubKegiatan->parent_id = $renstraKegiatan->id;
                        $renstraSubKegiatan->save();

                        // delete if duplicated data
                        RenstraSubKegiatan::where('renstra_id', $renstra->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('year', $year)
                            ->where('status', 'active')
                            ->where('id', '!=', $renstraSubKegiatan->id)
                            ->delete();

                        $indicators = [];
                        $indikatorCons = DB::table('con_indikator_kinerja_sub_kegiatan')
                            ->where('instance_id', $request->instance)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->first();
                        if ($indikatorCons) {
                            $indikators = IndikatorSubKegiatan::where('pivot_id', $indikatorCons->id)
                                ->get();
                            foreach ($indikators as $key => $indi) {

                                $arrSatuanIds = $renstraSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIds) {
                                    $satuanId = json_decode($renstraSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                                }

                                $arrKinerjaValues = $renstraSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValues) {
                                    $value = json_decode($renstraSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }
                                $indicators[] = [
                                    'id' => $indi->id,
                                    'name' => $indi->name,
                                    'value' => $value ?? null,
                                    'satuan_id' => $satuanId ?? null,
                                    'satuan_name' => $satuanName ?? null,
                                ];
                            }
                        }
                        $datas[$year][] = [
                            'id' => $subKegiatan->id,
                            'type' => 'sub-kegiatan',
                            'program_id' => $program->id,
                            'program_name' => $program->name ?? null,
                            'program_fullcode' => $program->fullcode,
                            'kegiatan_id' => $kegiatan->id,
                            'kegiatan_name' => $kegiatan->name ?? null,
                            'kegiatan_fullcode' => $kegiatan->fullcode,
                            'sub_kegiatan_id' => $subKegiatan->id,
                            'sub_kegiatan_name' => $subKegiatan->name ?? null,
                            'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                            'indicators' => $indicators,
                            'anggaran_modal' => $renstraSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi' => $renstraSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer' => $renstraSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga' => $renstraSubKegiatan->anggaran_tidak_terduga ?? null,
                            'total_anggaran' => $renstraSubKegiatan->total_anggaran ?? null,
                            'total_kinerja' => $renstraSubKegiatan->total_kinerja ?? null,
                            'percent_anggaran' => $renstraSubKegiatan->percent_anggaran,
                            'percent_kinerja' => $renstraSubKegiatan->percent_kinerja,
                            'year' => $renstraSubKegiatan->year ?? null,
                            'status' => $renstraSubKegiatan->status ?? null,
                            'created_by' => $renstraSubKegiatan->created_by ?? null,
                            'updated_by' => $renstraSubKegiatan->updated_by ?? null,
                            'created_by' => $renstraSubKegiatan->CreatedBy->fullname ?? '-',
                            'updated_by' => $renstraSubKegiatan->UpdatedBy->fullname ?? '-',
                        ];
                    }
                }
            }
            $renstra = [
                'id' => $renstra->id,
                'rpjmd_id' => $renstra->rpjmd_id,
                'rpjmd_data' => $renstra->RPJMD,
                'program_id' => $renstra->program_id,
                'program_name' => $renstra->Program->name ?? null,
                'program_fullcode' => $renstra->Program->fullcode ?? null,
                'total_anggaran' => $renstra->total_anggaran,
                'total_kinerja' => $renstra->total_kinerja,
                'percent_anggaran' => $renstra->percent_anggaran,
                'percent_kinerja' => $renstra->percent_kinerja,
                'status' => $renstra->status,
                'status_leader' => $renstra->status_leader,
                'notes_verificator' => $renstra->notes_verificator,
                'created_by' => $renstra->created_by,
                'CreatedBy' => $renstra->CreatedBy->fullname ?? null,
                'updated_by' => $renstra->updated_by,
                'UpdatedBy' => $renstra->UpdatedBy->fullname ?? null,
                'created_at' => $renstra->created_at,
                'updated_at' => $renstra->updated_at,
            ];
            DB::commit();
            return $this->successResponse([
                'renstra' => $renstra,
                'datas' => $datas,
                'range' => $range,
            ], 'List Renstra');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getFile() . ' - ' . $th->getLine());
        }
    }

    function detailCaramRenstra($id, Request $request)
    {
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $kegiatan = Kegiatan::find($id);
                if (!$kegiatan) {
                    return $this->errorResponse('Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->first();

                $arrIndikator = IndikatorKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $renstraDetail = RenstraKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {

                    if ($renstraDetail->kinerja_json) {
                        $value = json_decode($renstraDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($renstraDetail->satuan_json) {
                        $satuanId = json_decode($renstraDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $renstraDetail->total_anggaran,
                    'anggaran_modal' => $renstraDetail->anggaran_modal,
                    'anggaran_operasi' => $renstraDetail->anggaran_operasi,
                    'anggaran_transfer' => $renstraDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $renstraDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $renstraDetail->percent_anggaran,
                    'percent_kinerja' => $renstraDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $kegiatan->id,
                    'id_renstra_detail' => $renstraDetail->id,
                    'type' => 'kegiatan',
                    'program_id' => $renstraDetail->program_id,
                    'program_name' => $kegiatan->Program->name ?? null,
                    'program_fullcode' => $kegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_name' => $kegiatan->name ?? null,
                    'kegiatan_fullcode' => $kegiatan->fullcode,
                    'year' => $renstraDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $renstraDetail->total_anggaran,
                    'percent_anggaran' => $renstraDetail->percent_anggaran,
                    'percent_kinerja' => $renstraDetail->percent_kinerja,
                ];


                // DB::commit();
                return $this->successResponse($datas, 'Detail Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $subKegiatan = SubKegiatan::find($id);
                if (!$subKegiatan) {
                    return $this->errorResponse('Sub Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_sub_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->first();
                $arrIndikator = IndikatorSubKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $renstraDetail = RenstraSubKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {
                    if ($renstraDetail->kinerja_json) {
                        $value = json_decode($renstraDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($renstraDetail->satuan_json) {
                        $satuanId = json_decode($renstraDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $renstraDetail->total_anggaran,
                    'anggaran_modal' => $renstraDetail->anggaran_modal,
                    'anggaran_operasi' => $renstraDetail->anggaran_operasi,
                    'anggaran_transfer' => $renstraDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $renstraDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $renstraDetail->percent_anggaran,
                    'percent_kinerja' => $renstraDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $subKegiatan->id,
                    'id_renstra_detail' => $renstraDetail->id,
                    'type' => 'sub-kegiatan',
                    'program_id' => $renstraDetail->program_id,
                    'program_name' => $subKegiatan->Program->name ?? null,
                    'program_fullcode' => $subKegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $renstraDetail->kegiatan_id,
                    'kegiatan_name' => $subKegiatan->Kegiatan->name ?? null,
                    'kegiatan_fullcode' => $subKegiatan->Kegiatan->fullcode,
                    'sub_kegiatan_id' => $subKegiatan->id,
                    'sub_kegiatan_name' => $subKegiatan->name ?? null,
                    'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                    'year' => $renstraDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $renstraDetail->total_anggaran,
                    'percent_anggaran' => $renstraDetail->percent_anggaran,
                    'percent_kinerja' => $renstraDetail->percent_kinerja,
                ];

                // DB::commit();
                return $this->successResponse($datas, 'Detail Sub Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        return $this->errorResponse('Tipe tidak ditemukan');
    }

    function saveCaramRenstra($id, Request $request)
    {
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = RenstraKegiatan::find($request->data['id_renstra_detail']);
                $renstra = Renstra::find($data->renstra_id);
                if ($renstra->status == 'verified') {
                    return $this->errorResponse('Renstra sudah diverifikasi');
                }

                $data->anggaran_modal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $data->anggaran_operasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $data->anggaran_transfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $data->anggaran_tidak_terduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $data->total_anggaran = $request->data['total_anggaran'] ?? 0;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'] ?? null;
                    $satuanArray[] = $indi['satuan_id'] ?? null;
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);

                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->save();

                $renstra = Renstra::find($data->renstra_id);
                $renstra->updated_by = auth()->user()->id ?? null;
                $renstra->updated_at = Carbon::now();
                $renstra->save();

                DB::commit();
                return $this->successResponse($data, 'Data Renstra Berhasil disimpan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = RenstraSubKegiatan::find($request->data['id_renstra_detail']);
                $renstra = Renstra::find($data->renstra_id);
                if ($renstra->status == 'verified') {
                    return $this->errorResponse('Renstra sudah diverifikasi');
                }

                $data->anggaran_modal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $data->anggaran_operasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $data->anggaran_transfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $data->anggaran_tidak_terduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $data->total_anggaran = $request->data['total_anggaran'] ?? 0;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'] ?? null;
                    $satuanArray[] = $indi['satuan_id'] ?? null;
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);
                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->save();

                $renstraKegiatan = RenstraKegiatan::find($data->parent_id);
                $renstraKegiatan->total_anggaran = $renstraKegiatan->dataSubKegiatan->sum('total_anggaran');
                $renstraKegiatan->save();

                $renstra->updated_by = auth()->user()->id ?? null;
                $renstra->updated_at = Carbon::now();
                $renstra->save();

                DB::commit();
                return $this->successResponse($data, 'Data Renstra Berhasil disimpan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }
    }

    function listCaramRenstraNotes($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renstra' => 'required|numeric|exists:data_renstra,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renstra' => 'Renstra',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $datas = [];
        $notes = DB::table('notes_renstra')
            ->where('renstra_id', $id)
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
        foreach ($notes as $note) {
            $user = User::find($note->user_id);
            $datas[] = [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'user_name' => $user->fullname ?? null,
                'user_photo' => asset($user->photo) ?? null,
                'message' => $note->message,
                'status' => $note->status,
                'type' => $note->type,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
            ];
        }

        return $this->successResponse($datas, 'List Renstra');
    }

    function postCaramRenstraNotes($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renstra' => 'required|numeric|exists:data_renstra,id',
            'message' => 'required|string',
            'status' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renstra' => 'Renstra',
            'message' => 'Pesan',
            'status' => 'Status',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $renstra = Renstra::find($id);
            if (!$renstra) {
                return $this->errorResponse('Renstra tidak ditemukan');
            }
            if (auth()->user()->role_id == 9) {
                $type = 'request';
                $renstra->status = $request->status;
                $renstra->save();

                // send notification
                $users = User::where('role_id', 6)->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $renstra->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/renstra?instance=' . $renstra->instance_id . '&program=' . $renstra->program_id,
                    'Permintaan Verifikasi Renstra',
                    'Permintaan Verifikasi Renstra dari ' . auth()->user()->fullname,
                    [
                        'type' => 'renstra',
                        'renstra_id' => $renstra->id,
                        'instance_id' => $renstra->instance_id,
                        'program_id' => $renstra->program_id,
                        'uri' => '/renstra?instance=' . $renstra->instance_id . '&program=' . $renstra->program_id,
                    ]
                ));
            } else {
                $type = 'return';
                $renstra->status = $request->status;
                $renstra->notes_verificator = $request->message;
                $renstra->save();

                // send notification
                $users = User::where('role_id', 9)
                    ->where('instance_id', $renstra->instance_id)
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $renstra->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/renstra?instance=' . $renstra->instance_id . '&program=' . $renstra->program_id,
                    'Verifikasi Renstra',
                    auth()->user()->fullname . ' telah memberikan verifikasi Renstra',
                    [
                        'type' => 'renstra',
                        'renstra_id' => $renstra->id,
                        'instance_id' => $renstra->instance_id,
                        'program_id' => $renstra->program_id,
                        'uri' => '/renstra?instance=' . $renstra->instance_id . '&program=' . $renstra->program_id,
                    ]
                ));
            }

            $note = DB::table('notes_renstra')
                ->insert([
                    'renstra_id' => $id,
                    'user_id' => auth()->user()->id,
                    'message' => $request->message,
                    'status' => $request->status,
                    'type' => $type ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return $this->successResponse($note, 'Verifikasi Renstra Berhasil dikirim');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }



    function listCaramRenja(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renstra' => 'required|numeric|exists:data_renstra,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renstra' => 'Renstra',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $datas = [];
            $renstra = Renstra::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->first();
            if (!$renstra) {
                $renstra = new Renstra();
                $renstra->periode_id = $request->periode;
                $renstra->instance_id = $request->instance;
                $renstra->program_id = $request->program;
                $renstra->rpjmd_id = RPJMD::where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->where('program_id', $request->program)
                    ->first()->id ?? null;
                $renstra->total_anggaran = 0;
                $renstra->total_kinerja = 0;
                $renstra->percent_anggaran = 0;
                $renstra->percent_kinerja = 0;
                $renstra->status = 'draft';
                $renstra->status_leader = 'draft';
                $renstra->created_by = auth()->user()->id ?? null;
                $renstra->save();
            }
            $renja = Renja::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('renstra_id', $renstra->id)
                ->first();
            if (!$renja) {
                $renja = new Renja();
                $renja->periode_id = $request->periode;
                $renja->instance_id = $request->instance;
                $renja->renstra_id = $renstra->id;
                $renja->program_id = $request->program;
                $renja->rpjmd_id = RPJMD::where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->where('program_id', $request->program)
                    ->first()->id ?? null;
                $renja->total_anggaran = 0;
                $renja->total_kinerja = 0;
                $renja->percent_anggaran = 0;
                $renja->percent_kinerja = 0;
                $renja->status = 'draft';
                $renja->status_leader = 'draft';
                $renja->created_by = auth()->user()->id ?? null;
                $renja->save();
            }

            $periode = Periode::where('id', $request->periode)->first();
            $range = [];
            if ($periode) {
                $start = Carbon::parse($periode->start_date);
                $end = Carbon::parse($periode->end_date);
                for ($i = $start->year; $i <= $end->year; $i++) {
                    $range[] = $i;
                    $anggaran[$i] = null;
                }
            }

            foreach ($range as $year) {
                $program = Program::find($request->program);
                $indicators = [];
                $rpjmdIndicators = RPJMDIndikator::where('rpjmd_id', $renstra->rpjmd_id)
                    ->where('year', $year)
                    ->get();
                foreach ($rpjmdIndicators as $ind) {
                    $indicators[] = [
                        'id' => $ind->id,
                        'name' => $ind->name,
                        'value' => $ind->value,
                        'satuan_id' => $ind->satuan_id,
                        'satuan_name' => $ind->Satuan->name ?? null,
                    ];
                }
                $anggaranModal = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_modal');
                $anggaranModalRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_modal');
                $anggaranOperasi  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranOperasiRenja  = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranTransfer = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTransferRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTidakTerduga  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $anggaranTidakTerdugaRenja  = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $totalAnggaranRenstra = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');
                $totalAnggaranRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');

                $renja->total_anggaran = $totalAnggaranRenja;
                $renja->percent_anggaran = 100;
                $averagePercentKinerja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->get()->avg('percent_kinerja');
                $renja->percent_kinerja = $averagePercentKinerja ?? 0;
                $renja->save();


                $datas[$year][] = [
                    'id' => $program->id,
                    'type' => 'program',
                    'rpjmd_id' => $renstra->rpjmd_id,
                    'rpjmd_data' => $renstra->RPJMD,
                    'indicators' => $indicators ?? null,

                    'anggaran_modal_renstra' => $anggaranModal,
                    'anggaran_operasi_renstra' => $anggaranOperasi,
                    'anggaran_transfer_renstra' => $anggaranTransfer,
                    'anggaran_tidak_terduga_renstra' => $anggaranTidakTerduga,

                    'anggaran_modal_renja' => $anggaranModalRenja,
                    'anggaran_operasi_renja' => $anggaranOperasiRenja,
                    'anggaran_transfer_renja' => $anggaranTransferRenja,
                    'anggaran_tidak_terduga_renja' => $anggaranTidakTerdugaRenja,

                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'program_fullcode' => $program->fullcode,

                    'total_anggaran_renstra' => $totalAnggaranRenstra,
                    'total_anggaran_renja' => $totalAnggaranRenja,

                    'total_kinerja_renstra' => $renstra->total_kinerja,
                    'percent_anggaran_renstra' => $renstra->percent_anggaran,
                    'percent_kinerja_renstra' => $renstra->percent_kinerja,
                    'percent_anggaran_renja' => $renja->percent_anggaran,
                    'percent_kinerja_renja' => $renja->percent_kinerja,

                    'status_renstra' => $renja->status,
                    'status_renja' => $renja->status,
                    'created_by' => $renja->CreatedBy->fullname ?? '-',
                    'updated_by' => $renja->UpdatedBy->fullname ?? '-',
                    'created_at' => $renja->created_at,
                    'updated_at' => $renja->updated_at,
                ];


                if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                    $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                    $kegs = collect($kegs)->unique()->values();
                    $kegiatans = Kegiatan::where('program_id', $program->id)
                        ->whereIn('id', $kegs)
                        ->where('status', 'active')
                        ->get();
                } else {
                    $kegiatans = Kegiatan::where('program_id', $program->id)
                        ->where('status', 'active')
                        ->get();
                }

                foreach ($kegiatans as $kegiatan) {
                    $renstraKegiatan = RenstraKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->first();
                    if (!$renstraKegiatan) {
                        $renstraKegiatan = new RenstraKegiatan();
                        $renstraKegiatan->renstra_id = $renstra->id;
                        $renstraKegiatan->program_id = $program->id;
                        $renstraKegiatan->kegiatan_id = $kegiatan->id;
                        $renstraKegiatan->year = $year;
                        $renstraKegiatan->anggaran_json = null;
                        $renstraKegiatan->kinerja_json = null;
                        $renstraKegiatan->satuan_json = null;
                        $renstraKegiatan->anggaran_modal = 0;
                        $renstraKegiatan->anggaran_operasi = 0;
                        $renstraKegiatan->anggaran_transfer = 0;
                        $renstraKegiatan->anggaran_tidak_terduga = 0;
                        $renstraKegiatan->total_anggaran = 0;
                        $renstraKegiatan->total_kinerja = 0;
                        $renstraKegiatan->percent_anggaran = 0;
                        $renstraKegiatan->percent_kinerja = 0;
                        $renstraKegiatan->status = 'active';
                        $renstraKegiatan->created_by = auth()->user()->id ?? null;
                        $renstraKegiatan->save();
                    }
                    $renjaKegiatan = RenjaKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->first();

                    if (!$renjaKegiatan) {
                        $renjaKegiatan = new RenjaKegiatan();
                        $renjaKegiatan->renstra_id = $renstra->id;
                        $renjaKegiatan->renja_id = $renja->id;
                        $renjaKegiatan->program_id = $program->id;
                        $renjaKegiatan->kegiatan_id = $kegiatan->id;
                        $renjaKegiatan->year = $year;
                        $renjaKegiatan->anggaran_json = null;
                        $renjaKegiatan->kinerja_json = null;
                        $renjaKegiatan->satuan_json = null;
                        $renjaKegiatan->anggaran_modal = 0;
                        $renjaKegiatan->anggaran_operasi = 0;
                        $renjaKegiatan->anggaran_transfer = 0;
                        $renjaKegiatan->anggaran_tidak_terduga = 0;
                        $renjaKegiatan->total_anggaran = 0;
                        $renjaKegiatan->total_kinerja = 0;
                        $renjaKegiatan->percent_anggaran = 0;
                        $renjaKegiatan->percent_kinerja = 0;
                        $renjaKegiatan->status = 'active';
                        $renjaKegiatan->created_by = auth()->user()->id ?? null;
                        $renjaKegiatan->save();
                    }

                    $indicators = [];
                    $indikatorCons = DB::table('con_indikator_kinerja_kegiatan')
                        ->where('instance_id', $request->instance)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->first();
                    if ($indikatorCons) {
                        $indikators = IndikatorKegiatan::where('pivot_id', $indikatorCons->id)
                            ->get();
                        foreach ($indikators as $key => $indi) {
                            if ($renstraKegiatan->satuan_json) {
                                $satuanIdRenstra = json_decode($renstraKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanNameRenstra = Satuan::where('id', $satuanIdRenstra)->first()->name ?? null;
                            }
                            if ($renjaKegiatan->satuan_json) {
                                $satuanIdRenja = json_decode($renjaKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanNameRenja = Satuan::where('id', $satuanIdRenja)->first()->name ?? null;
                            }
                            $indicators[] = [
                                'id' => $indi->id,
                                'name' => $indi->name,
                                'value_renstra' => json_decode($renstraKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id_renstra' => $satuanIdRenstra ?? null,
                                'satuan_name_renstra' => $satuanNameRenstra ?? null,
                                'value_renja' => json_decode($renjaKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id_renja' => $satuanIdRenja ?? null,
                                'satuan_name_renja' => $satuanNameRenja ?? null,
                            ];
                        }
                    }

                    $anggaranModalRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_modal');
                    $anggaranOperasiRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_operasi');
                    $anggaranTransferRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_transfer');
                    $anggaranTidakTerdugaRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_tidak_terduga');
                    $totalAnggaranRenstra = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');
                    $totalAnggaranRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');

                    $renjaKegiatan->anggaran_modal = $anggaranModalRenja;
                    $renjaKegiatan->anggaran_operasi = $anggaranOperasiRenja;
                    $renjaKegiatan->anggaran_transfer = $anggaranTransferRenja;
                    $renjaKegiatan->anggaran_tidak_terduga = $anggaranTidakTerdugaRenja;
                    $renjaKegiatan->total_anggaran = $totalAnggaranRenja;
                    $renjaKegiatan->save();

                    $datas[$year][] = [
                        'id' => $kegiatan->id,
                        'type' => 'kegiatan',
                        'program_id' => $renstraKegiatan->program_id,
                        'program_name' => $program->name,
                        'program_fullcode' => $program->fullcode,
                        'kegiatan_id' => $kegiatan->id,
                        'kegiatan_name' => $kegiatan->name,
                        'kegiatan_fullcode' => $kegiatan->fullcode,
                        'indicators' => $indicators,

                        'anggaran_json' => $renstraKegiatan->anggaran_json,
                        'kinerja_json' => $renstraKegiatan->kinerja_json,
                        'satuan_json' => $renstraKegiatan->satuan_json,

                        'anggaran_modal_renstra' => $renstraKegiatan->anggaran_modal,
                        'anggaran_operasi_renstra' => $renstraKegiatan->anggaran_operasi,
                        'anggaran_transfer_renstra' => $renstraKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga_renstra' => $renstraKegiatan->anggaran_tidak_terduga,

                        'anggaran_modal_renja' => $renjaKegiatan->anggaran_modal,
                        'anggaran_operasi_renja' => $renjaKegiatan->anggaran_operasi,
                        'anggaran_transfer_renja' => $renjaKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga_renja' => $renjaKegiatan->anggaran_tidak_terduga,

                        'total_anggaran_renstra' => $renstraKegiatan->total_anggaran,
                        'total_anggaran_renja' => $renjaKegiatan->total_anggaran,

                        'total_kinerja' => $renstraKegiatan->total_kinerja,

                        'percent_anggaran_renstra' => $renstraKegiatan->percent_anggaran,
                        'percent_kinerja_renstra' => $renstraKegiatan->percent_kinerja,
                        'percent_anggaran_renja' => $renjaKegiatan->percent_anggaran,
                        'percent_kinerja_renja' => $renjaKegiatan->percent_kinerja,

                        'year' => $renjaKegiatan->year,
                        'status' => $renjaKegiatan->status,
                        'created_by' => $renjaKegiatan->CreatedBy->fullname ?? '-',
                        'updated_by' => $renjaKegiatan->UpdatedBy->fullname ?? '-',
                        'created_at' => $renjaKegiatan->created_at,
                        'updated_at' => $renjaKegiatan->updated_at,
                    ];


                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $subKegs = auth()->user()->MyPermissions()->pluck('sub_kegiatan_id');
                        $subKegs = collect($subKegs)->unique()->values();

                        $subKegiatans = SubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->whereIn('id', $subKegs)
                            ->where('status', 'active')
                            ->get();
                    } else {
                        $subKegiatans = SubKegiatan::where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('status', 'active')
                            ->get();
                    }
                    foreach ($subKegiatans as $subKegiatan) {
                        $renstraSubKegiatan = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            // ->where('parent_id', $renstraKegiatan->id)
                            ->where('year', $year)
                            // ->where('status', 'active')
                            ->first();
                        if (!$renstraSubKegiatan) {
                            $renstraSubKegiatan = new RenstraSubKegiatan();
                            $renstraSubKegiatan->renstra_id = $renstra->id;
                            $renstraSubKegiatan->parent_id = $renstraKegiatan->id;
                            $renstraSubKegiatan->program_id = $program->id;
                            $renstraSubKegiatan->kegiatan_id = $kegiatan->id;
                            $renstraSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $renstraSubKegiatan->year = $year;
                            $renstraSubKegiatan->anggaran_json = null;
                            $renstraSubKegiatan->kinerja_json = null;
                            $renstraSubKegiatan->satuan_json = null;
                            $renstraSubKegiatan->anggaran_modal = 0;
                            $renstraSubKegiatan->anggaran_operasi = 0;
                            $renstraSubKegiatan->anggaran_transfer = 0;
                            $renstraSubKegiatan->anggaran_tidak_terduga = 0;
                            $renstraSubKegiatan->total_anggaran = 0;
                            $renstraSubKegiatan->total_kinerja = 0;
                            $renstraSubKegiatan->percent_anggaran = 0;
                            $renstraSubKegiatan->percent_kinerja = 0;
                            $renstraSubKegiatan->status = 'active';
                            $renstraSubKegiatan->created_by = auth()->user()->id ?? null;
                            $renstraSubKegiatan->save();
                        }

                        $renjaSubKegiatan = RenjaSubKegiatan::where('renja_id', $renja->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('year', $year)
                            ->first();

                        if (!$renjaSubKegiatan) {
                            $renjaSubKegiatan = new RenjaSubKegiatan();
                            $renjaSubKegiatan->renstra_id = $renstra->id;
                            $renjaSubKegiatan->renja_id = $renja->id;
                            $renjaSubKegiatan->parent_id = $renjaKegiatan->id;
                            $renjaSubKegiatan->program_id = $program->id;
                            $renjaSubKegiatan->kegiatan_id = $kegiatan->id;
                            $renjaSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $renjaSubKegiatan->year = $year;
                            $renjaSubKegiatan->anggaran_json = null;
                            $renjaSubKegiatan->kinerja_json = null;
                            $renjaSubKegiatan->satuan_json = null;
                            $renjaSubKegiatan->anggaran_modal = 0;
                            $renjaSubKegiatan->anggaran_operasi = 0;
                            $renjaSubKegiatan->anggaran_transfer = 0;
                            $renjaSubKegiatan->anggaran_tidak_terduga = 0;
                            $renjaSubKegiatan->total_anggaran = 0;
                            $renjaSubKegiatan->total_kinerja = 0;
                            $renjaSubKegiatan->percent_anggaran = 0;
                            $renjaSubKegiatan->percent_kinerja = 0;
                            $renjaSubKegiatan->status = 'active';
                            $renjaSubKegiatan->created_by = auth()->user()->id ?? null;
                            $renjaSubKegiatan->save();
                        }

                        $indicators = [];
                        $indikatorCons = DB::table('con_indikator_kinerja_sub_kegiatan')
                            ->where('instance_id', $request->instance)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->first();
                        if ($indikatorCons) {
                            $indikators = IndikatorSubKegiatan::where('pivot_id', $indikatorCons->id)
                                ->get();
                            foreach ($indikators as $key => $indi) {

                                $arrSatuanIdsRenstra = $renstraSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIdsRenstra) {
                                    $satuanIdRenstra = json_decode($renstraSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanNameRenstra = Satuan::where('id', $satuanIdRenstra)->first()->name ?? null;
                                }
                                $arrSatuanIdsRenja = $renjaSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIdsRenja) {
                                    $satuanIdRenja = json_decode($renjaSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanNameRenja = Satuan::where('id', $satuanIdRenja)->first()->name ?? null;
                                }

                                $arrKinerjaValues = $renstraSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValues) {
                                    $value = json_decode($renstraSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }
                                $arrKinerjaValuesRenja = $renjaSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValuesRenja) {
                                    $valueRenja = json_decode($renjaSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }
                                $indicators[] = [
                                    'id' => $indi->id,
                                    'name' => $indi->name,
                                    'value_renstra' => $value ?? null,
                                    'value_renja' => $valueRenja ?? null,
                                    'satuan_id_renstra' => $satuanIdRenstra ?? null,
                                    'satuan_name_renstra' => $satuanNameRenstra ?? null,
                                    'satuan_id_renja' => $satuanIdRenja ?? null,
                                    'satuan_name_renja' => $satuanNameRenja ?? null,
                                ];
                            }
                        }
                        $datas[$year][] = [
                            'id' => $subKegiatan->id,
                            'type' => 'sub-kegiatan',
                            'program_id' => $program->id,
                            'program_name' => $program->name ?? null,
                            'program_fullcode' => $program->fullcode,
                            'kegiatan_id' => $kegiatan->id,
                            'kegiatan_name' => $kegiatan->name ?? null,
                            'kegiatan_fullcode' => $kegiatan->fullcode,
                            'sub_kegiatan_id' => $subKegiatan->id,
                            'sub_kegiatan_name' => $subKegiatan->name ?? null,
                            'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                            'indicators' => $indicators,

                            'anggaran_modal_renstra' => $renstraSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi_renstra' => $renstraSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer_renstra' => $renstraSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga_renstra' => $renstraSubKegiatan->anggaran_tidak_terduga ?? null,

                            'anggaran_modal_renja' => $renjaSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi_renja' => $renjaSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer_renja' => $renjaSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga_renja' => $renjaSubKegiatan->anggaran_tidak_terduga ?? null,

                            'total_anggaran_renstra' => $renstraSubKegiatan->total_anggaran ?? null,
                            'total_anggaran_renja' => $renjaSubKegiatan->total_anggaran ?? null,

                            'percent_anggaran_renstra' => $renstraSubKegiatan->percent_anggaran,
                            'percent_kinerja_renstra' => $renstraSubKegiatan->percent_kinerja,
                            'percent_anggaran_renja' => $renjaSubKegiatan->percent_anggaran,
                            'percent_kinerja_renja' => $renjaSubKegiatan->percent_kinerja,


                            'year' => $renjaSubKegiatan->year ?? null,
                            'status' => $renjaSubKegiatan->status ?? null,
                            'created_by' => $renjaSubKegiatan->CreatedBy->fullname ?? '-',
                            'updated_by' => $renjaSubKegiatan->UpdatedBy->fullname ?? '-',
                            'created_at' => $renjaSubKegiatan->created_at ?? null,
                            'updated_at' => $renjaSubKegiatan->updated_at ?? null,
                        ];
                    }
                }
            }
            $renstra = [
                'id' => $renstra->id,
                'rpjmd_id' => $renstra->rpjmd_id,
                'rpjmd_data' => $renstra->RPJMD,
                'program_id' => $renstra->program_id,
                'program_name' => $renstra->Program->name ?? null,
                'program_fullcode' => $renstra->Program->fullcode ?? null,
                'total_anggaran' => $renstra->total_anggaran,
                'total_kinerja' => $renstra->total_kinerja,
                'percent_anggaran' => $renstra->percent_anggaran,
                'percent_kinerja' => $renstra->percent_kinerja,
                'status' => $renstra->status,
                'status_leader' => $renstra->status_leader,
                'notes_verificator' => $renstra->notes_verificator,
                'created_by' => $renstra->created_by,
                'CreatedBy' => $renstra->CreatedBy->fullname ?? null,
                'updated_by' => $renstra->updated_by,
                'UpdatedBy' => $renstra->UpdatedBy->fullname ?? null,
                'created_at' => $renstra->created_at,
                'updated_at' => $renstra->updated_at,
            ];
            $renja = [
                'id' => $renja->id,
                'periode_id' => $renja->periode_id,
                'periode_data' => $renja->Periode->name ?? null,
                'instance_id' => $renja->instance_id,
                'instance_data' => $renja->Instance->name ?? null,
                'renstra_id' => $renja->renstra_id,
                'renstra_data' => $renja->Renstra->Program->name ?? null,
                'program_id' => $renja->program_id,
                'program_name' => $renja->Program->name ?? null,
                'program_fullcode' => $renja->Program->fullcode ?? null,
                'total_anggaran' => $renja->total_anggaran,
                'total_kinerja' => $renja->total_kinerja,
                'percent_anggaran' => $renja->percent_anggaran,
                'percent_kinerja' => $renja->percent_kinerja,
                'status' => $renja->status,
                'status_leader' => $renja->status_leader,
                'notes_verificator' => $renja->notes_verificator,
                'created_by' => $renja->created_by,
                'CreatedBy' => $renja->CreatedBy->fullname ?? null,
                'updated_by' => $renja->updated_by,
                'UpdatedBy' => $renja->UpdatedBy->fullname ?? null,
                'created_at' => $renja->created_at,
                'updated_at' => $renja->updated_at,
            ];
            DB::commit();
            return $this->successResponse([
                'renstra' => $renstra,
                'renja' => $renja,
                'datas' => $datas,
                'range' => $range,
            ], 'List Renstra');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine() . ' - ' . $th->getFile());
        }
    }

    function detailCaramRenja($id, Request $request)
    {
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $kegiatan = Kegiatan::find($id);
                if (!$kegiatan) {
                    return $this->errorResponse('Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->first();
                $arrIndikator = IndikatorKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $renjaDetail = RenjaKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {

                    if ($renjaDetail->kinerja_json) {
                        $value = json_decode($renjaDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($renjaDetail->satuan_json) {
                        $satuanId = json_decode($renjaDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $renjaDetail->total_anggaran,
                    'anggaran_modal' => $renjaDetail->anggaran_modal,
                    'anggaran_operasi' => $renjaDetail->anggaran_operasi,
                    'anggaran_transfer' => $renjaDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $renjaDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $renjaDetail->percent_anggaran,
                    'percent_kinerja' => $renjaDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $kegiatan->id,
                    'id_renja_detail' => $renjaDetail->id,
                    'type' => 'kegiatan',
                    'program_id' => $renjaDetail->program_id,
                    'program_name' => $kegiatan->Program->name ?? null,
                    'program_fullcode' => $kegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_name' => $kegiatan->name ?? null,
                    'kegiatan_fullcode' => $kegiatan->fullcode,
                    'year' => $renjaDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $renjaDetail->total_anggaran,
                    'percent_anggaran' => $renjaDetail->percent_anggaran,
                    'percent_kinerja' => $renjaDetail->percent_kinerja,
                ];


                // DB::commit();
                return $this->successResponse($datas, 'Detail Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $subKegiatan = SubKegiatan::find($id);
                if (!$subKegiatan) {
                    return $this->errorResponse('Sub Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_sub_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->first();
                $arrIndikator = IndikatorSubKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $renjaDetail = RenjaSubKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {

                    if ($renjaDetail->kinerja_json) {
                        $value = json_decode($renjaDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($renjaDetail->satuan_json) {
                        $satuanId = json_decode($renjaDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $renjaDetail->total_anggaran,
                    'anggaran_modal' => $renjaDetail->anggaran_modal,
                    'anggaran_operasi' => $renjaDetail->anggaran_operasi,
                    'anggaran_transfer' => $renjaDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $renjaDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $renjaDetail->percent_anggaran,
                    'percent_kinerja' => $renjaDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $subKegiatan->id,
                    'id_renja_detail' => $renjaDetail->id,
                    'type' => 'sub-kegiatan',
                    'program_id' => $renjaDetail->program_id,
                    'program_name' => $subKegiatan->Program->name ?? null,
                    'program_fullcode' => $subKegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $renjaDetail->kegiatan_id,
                    'kegiatan_name' => $subKegiatan->Kegiatan->name ?? null,
                    'kegiatan_fullcode' => $subKegiatan->Kegiatan->fullcode,
                    'sub_kegiatan_id' => $subKegiatan->id,
                    'sub_kegiatan_name' => $subKegiatan->name ?? null,
                    'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                    'year' => $renjaDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $renjaDetail->total_anggaran,
                    'percent_anggaran' => $renjaDetail->percent_anggaran,
                    'percent_kinerja' => $renjaDetail->percent_kinerja,
                ];

                // DB::commit();
                return $this->successResponse($datas, 'Detail Sub Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        return $this->errorResponse('Tipe tidak ditemukan');
    }

    function saveCaramRenja($id, Request $request)
    {
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = RenjaKegiatan::find($request->data['id_renja_detail']);
                $renja = Renja::find($data->renja_id);
                if ($renja->status == 'verified') {
                    return $this->errorResponse('Renja sudah diverifikasi');
                }

                $data->anggaran_modal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $data->anggaran_operasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $data->anggaran_transfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $data->anggaran_tidak_terduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $data->total_anggaran = $request->data['total_anggaran'] ?? 0;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'] ?? null;
                    $satuanArray[] = $indi['satuan_id'] ?? null;
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);

                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->updated_by = auth()->user()->id ?? null;
                $data->save();

                $renja->updated_by = auth()->user()->id ?? null;
                $renja->updated_at = Carbon::now();
                $renja->save();

                DB::commit();
                return $this->successResponse($data, 'Data Renstra Berhasil disimpan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = RenjaSubKegiatan::find($request->data['id_renja_detail']);
                $renja = Renja::find($data->renja_id);
                if ($renja->status == 'verified') {
                    return $this->errorResponse('Renja sudah diverifikasi');
                }

                $data->anggaran_modal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $data->anggaran_operasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $data->anggaran_transfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $data->anggaran_tidak_terduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $data->total_anggaran = $request->data['total_anggaran'] ?? 0;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'];
                    $satuanArray[] = $indi['satuan_id'];
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);

                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->updated_by = auth()->user()->id ?? null;
                $data->save();

                $renja->updated_by = auth()->user()->id ?? null;
                $renja->updated_at = Carbon::now();
                $renja->save();

                DB::commit();
                return $this->successResponse($data, 'Data Renja Berhasil disimpan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }
    }

    function listCaramRenjaNotes($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renja' => 'required|numeric|exists:data_renja,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renja' => 'Renja',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $datas = [];
        $notes = DB::table('notes_renja')
            ->where('renja_id', $id)
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
        foreach ($notes as $note) {
            $user = User::find($note->user_id);
            $datas[] = [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'user_name' => $user->fullname ?? null,
                'user_photo' => asset($user->photo) ?? null,
                'message' => $note->message,
                'status' => $note->status,
                'type' => $note->type,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
            ];
        }

        return $this->successResponse($datas, 'List Renstra');
    }

    function postCaramRenjaNotes($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renja' => 'required|numeric|exists:data_renja,id',
            'message' => 'required|string',
            'status' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renja' => 'Renja',
            'message' => 'Pesan',
            'status' => 'Status',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $renja = Renja::find($id);
            if (!$renja) {
                return $this->errorResponse('Renja tidak ditemukan');
            }
            if (auth()->user()->role_id == 9) {
                $type = 'request';
                $renja->status = $request->status;
                $renja->save();

                // send notification
                $users = User::where('role_id', 6)->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $renja->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/renja?instance=' . $renja->instance_id . '&program=' . $renja->program_id,
                    'Permintaan Verifikasi Renstra Perubahan',
                    'Permintaan Verifikasi Renstra Perubahan dari ' . auth()->user()->fullname,
                    [
                        'type' => 'renja',
                        'renja_id' => $renja->id,
                        'instance_id' => $renja->instance_id,
                        'program_id' => $renja->program_id,
                        'uri' => '/renja?instance=' . $renja->instance_id . '&program=' . $renja->program_id,
                    ]
                ));
            } else {
                $type = 'return';
                $renja->status = $request->status;
                $renja->notes_verificator = $request->message;
                $renja->save();

                // send notification
                $users = User::where('role_id', 9)
                    ->where('instance_id', $renja->instance_id)
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $renja->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    '/renja?instance=' . $renja->instance_id . '&program=' . $renja->program_id,
                    'Verifikasi Renstra Perubahan',
                    auth()->user()->fullname . ' telah memberikan verifikasi Renstra Perubahan',
                    [
                        'type' => 'renja',
                        'renja_id' => $renja->id,
                        'instance_id' => $renja->instance_id,
                        'program_id' => $renja->program_id,
                        'uri' => '/renja?instance=' . $renja->instance_id . '&program=' . $renja->program_id,
                    ]
                ));
            }
            $note = DB::table('notes_renja')
                ->insert([
                    'renja_id' => $id,
                    'user_id' => auth()->user()->id,
                    'message' => $request->message,
                    'status' => $request->status,
                    'type' => $type ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return $this->successResponse($note, 'Pesan Berhasil dikirim');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }




    function listPickProgramForApbd(Request $request)
    {
        return $this->errorResponse('Fitur ini sedang dalam pengembangan');
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $datas = [];
            $programs = Program::where('instance_id', $request->instance)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($programs as $program) {
                $apbd = Apbd::where('periode_id', $request->periode)
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $program->id)
                    ->first();

                $datas[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'fullcode' => $program->fullcode,
                    'total_anggaran' => $apbd->total_anggaran ?? 0,
                    'total_kinerja' => $apbd->total_kinerja ?? 0,
                    'percent_anggaran' => $apbd->percent_anggaran ?? 0,
                    'percent_kinerja' => $apbd->percent_kinerja ?? 0,
                    'status' => $apbd->status ?? null,
                    'status_leader' => $apbd->status_leader ?? null,
                ];
            }

            DB::commit();
            return $this->successResponse($datas, 'List Program');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function listCaramAPBD(Request $request)
    {
        return $this->errorResponse('Fitur ini sedang dalam pengembangan');
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $datas = [];

            $renstra = Renstra::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->first();
            if (!$renstra) {
                $renstra = new Renstra();
                $renstra->periode_id = $request->periode;
                $renstra->instance_id = $request->instance;
                $renstra->program_id = $request->program;
                $renstra->rpjmd_id = RPJMD::where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->where('program_id', $request->program)
                    ->first()->id ?? null;
                $renstra->total_anggaran = 0;
                $renstra->total_kinerja = 0;
                $renstra->percent_anggaran = 0;
                $renstra->percent_kinerja = 0;
                $renstra->status = 'draft';
                $renstra->status_leader = 'draft';
                $renstra->created_by = auth()->user()->id ?? null;
                $renstra->save();
            }
            $renja = Renja::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('renstra_id', $renstra->id)
                ->first();
            if (!$renja) {
                $renja = new Renja();
                $renja->periode_id = $request->periode;
                $renja->instance_id = $request->instance;
                $renja->renstra_id = $renstra->id;
                $renja->program_id = $request->program;
                $renja->rpjmd_id = RPJMD::where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->where('program_id', $request->program)
                    ->first()->id ?? null;
                $renja->total_anggaran = 0;
                $renja->total_kinerja = 0;
                $renja->percent_anggaran = 0;
                $renja->percent_kinerja = 0;
                $renja->status = 'draft';
                $renja->status_leader = 'draft';
                $renja->created_by = auth()->user()->id ?? null;
                $renja->save();
            }

            $apbd = Apbd::where('periode_id', $request->periode)
                ->where('instance_id', $request->instance)
                ->where('program_id', $request->program)
                ->where('renstra_id', $renstra->id)
                ->where('renja_id', $renja->id)
                ->first();

            if (!$apbd) {
                $apbd = new Apbd();
                $apbd->periode_id = $request->periode;
                $apbd->instance_id = $request->instance;
                $apbd->renstra_id = $renstra->id;
                $apbd->renja_id = $renja->id;
                $apbd->rpjmd_id = RPJMD::where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->where('program_id', $request->program)
                    ->first()->id ?? null;
                $apbd->program_id = $request->program;
                $apbd->total_anggaran = 0;
                $apbd->total_kinerja = 0;
                $apbd->percent_anggaran = 0;
                $apbd->percent_kinerja = 0;
                $apbd->status = 'draft';
                $apbd->status_leader = 'draft';
                $apbd->created_by = auth()->user()->id ?? null;
                $apbd->save();
            }

            $periode = Periode::where('id', $request->periode)->first();
            $range = [];
            if ($periode) {
                $start = Carbon::parse($periode->start_date);
                $end = Carbon::parse($periode->end_date);
                for ($i = $start->year; $i <= $end->year; $i++) {
                    $range[] = $i;
                    $anggaran[$i] = null;
                }
            }

            foreach ($range as $year) {
                $program = Program::find($request->program);
                $indicators = [];
                $rpjmdIndicators = RPJMDIndikator::where('rpjmd_id', $renstra->rpjmd_id)
                    ->where('year', $year)
                    ->get();
                foreach ($rpjmdIndicators as $ind) {
                    $indicators[] = [
                        'id' => $ind->id,
                        'name' => $ind->name,
                        'value' => $ind->value,
                        'satuan_id' => $ind->satuan_id,
                        'satuan_name' => $ind->Satuan->name ?? null,
                    ];
                }

                $anggaranModal = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_modal');
                $anggaranModalRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_modal');
                $anggaranModalApbd = ApbdKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_modal');
                $anggaranOperasi  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranOperasiRenja  = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranOperasiApbd = ApbdKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranTransfer = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTransferRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTransferApbd = ApbdKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTidakTerduga  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $anggaranTidakTerdugaRenja  = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $anggaranTidakTerdugaApbd = ApbdKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $totalAnggaranRenstra = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');
                $totalAnggaranRenja = RenjaKegiatan::where('renja_id', $renja->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');
                $totalAnggaranApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');

                $apbd->total_anggaran = $apbd->detailKegiatan->sum('total_anggaran');
                $apbd->percent_anggaran = 100;
                $averagePercentKinerja = ApbdKegiatan::where('apbd_id', $apbd->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->get()->avg('percent_kinerja');
                $apbd->percent_kinerja = $averagePercentKinerja ?? 0;
                $apbd->save();

                $datas[$year][] = [
                    'id' => $program->id,
                    'type' => 'program',
                    'rpjmd_id' => $apbd->rpjmd_id,
                    'indicators' => $indicators ?? null,

                    'anggaran_modal_renstra' => $anggaranModal,
                    'anggaran_operasi_renstra' => $anggaranOperasi,
                    'anggaran_transfer_renstra' => $anggaranTransfer,
                    'anggaran_tidak_terduga_renstra' => $anggaranTidakTerduga,

                    'anggaran_modal_renja' => $anggaranModalRenja,
                    'anggaran_operasi_renja' => $anggaranOperasiRenja,
                    'anggaran_transfer_renja' => $anggaranTransferRenja,
                    'anggaran_tidak_terduga_renja' => $anggaranTidakTerdugaRenja,

                    'anggaran_modal_apbd' => $anggaranModalApbd,
                    'anggaran_operasi_apbd' => $anggaranOperasiApbd,
                    'anggaran_transfer_apbd' => $anggaranTransferApbd,
                    'anggaran_tidak_terduga_apbd' => $anggaranTidakTerdugaApbd,

                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'program_fullcode' => $program->fullcode,

                    'total_anggaran_renstra' => $totalAnggaranRenstra,
                    'total_anggaran_renja' => $totalAnggaranRenja,
                    'total_anggaran_apbd' => $totalAnggaranApbd,

                    'total_kinerja_renstra' => $renstra->total_kinerja,
                    'percent_anggaran_renstra' => $renstra->percent_anggaran,
                    'percent_kinerja_renstra' => $renstra->percent_kinerja,
                    'percent_anggaran_renja' => $renja->percent_anggaran,
                    'percent_kinerja_renja' => $renja->percent_kinerja,
                    'percent_anggaran_apbd' => $apbd->percent_anggaran,
                    'percent_kinerja_apbd' => $apbd->percent_kinerja,

                    'status_renstra' => $renja->status,
                    'status_renja' => $renja->status,
                    'status_apbd' => $apbd->status,
                    'created_by' => $apbd->CreatedBy->fullname ?? '-',
                    'updated_by' => $apbd->UpdatedBy->fullname ?? '-',
                    'created_at' => $apbd->created_at,
                    'updated_at' => $apbd->updated_at,
                ];

                $kegiatans = Kegiatan::where('program_id', $program->id)
                    ->where('status', 'active')
                    ->orderBy('fullcode')
                    ->get();
                foreach ($kegiatans as $kegiatan) {
                    $renstraKegiatan = RenstraKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->first();
                    if (!$renstraKegiatan) {
                        $renstraKegiatan = new RenstraKegiatan();
                        $renstraKegiatan->renstra_id = $renstra->id;
                        $renstraKegiatan->program_id = $program->id;
                        $renstraKegiatan->kegiatan_id = $kegiatan->id;
                        $renstraKegiatan->year = $year;
                        $renstraKegiatan->anggaran_json = null;
                        $renstraKegiatan->kinerja_json = null;
                        $renstraKegiatan->satuan_json = null;
                        $renstraKegiatan->anggaran_modal = 0;
                        $renstraKegiatan->anggaran_operasi = 0;
                        $renstraKegiatan->anggaran_transfer = 0;
                        $renstraKegiatan->anggaran_tidak_terduga = 0;
                        $renstraKegiatan->total_anggaran = 0;
                        $renstraKegiatan->total_kinerja = 0;
                        $renstraKegiatan->percent_anggaran = 0;
                        $renstraKegiatan->percent_kinerja = 0;
                        $renstraKegiatan->status = 'active';
                        $renstraKegiatan->created_by = auth()->user()->id ?? null;
                        $renstraKegiatan->save();
                    }
                    $renjaKegiatan = RenjaKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->first();

                    if (!$renjaKegiatan) {
                        $renjaKegiatan = new RenjaKegiatan();
                        $renjaKegiatan->renstra_id = $renstra->id;
                        $renjaKegiatan->renja_id = $renja->id;
                        $renjaKegiatan->program_id = $program->id;
                        $renjaKegiatan->kegiatan_id = $kegiatan->id;
                        $renjaKegiatan->year = $year;
                        $renjaKegiatan->anggaran_json = null;
                        $renjaKegiatan->kinerja_json = null;
                        $renjaKegiatan->satuan_json = null;
                        $renjaKegiatan->anggaran_modal = 0;
                        $renjaKegiatan->anggaran_operasi = 0;
                        $renjaKegiatan->anggaran_transfer = 0;
                        $renjaKegiatan->anggaran_tidak_terduga = 0;
                        $renjaKegiatan->total_anggaran = 0;
                        $renjaKegiatan->total_kinerja = 0;
                        $renjaKegiatan->percent_anggaran = 0;
                        $renjaKegiatan->percent_kinerja = 0;
                        $renjaKegiatan->status = 'active';
                        $renjaKegiatan->created_by = auth()->user()->id ?? null;
                        $renjaKegiatan->save();
                    }

                    $apbdKegiatan = ApbdKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->first();
                    if (!$apbdKegiatan) {
                        $apbdKegiatan = new ApbdKegiatan();
                        $apbdKegiatan->apbd_id = $apbd->id;
                        $apbdKegiatan->renstra_id = $renstra->id;
                        $apbdKegiatan->renja_id = $renja->id;
                        $apbdKegiatan->program_id = $program->id;
                        $apbdKegiatan->kegiatan_id = $kegiatan->id;
                        $apbdKegiatan->year = $year;
                        $apbdKegiatan->anggaran_json = null;
                        $apbdKegiatan->kinerja_json = null;
                        $apbdKegiatan->satuan_json = null;
                        $apbdKegiatan->anggaran_modal = 0;
                        $apbdKegiatan->anggaran_operasi = 0;
                        $apbdKegiatan->anggaran_transfer = 0;
                        $apbdKegiatan->anggaran_tidak_terduga = 0;
                        $apbdKegiatan->total_anggaran = 0;
                        $apbdKegiatan->total_kinerja = 0;
                        $apbdKegiatan->percent_anggaran = 0;
                        $apbdKegiatan->percent_kinerja = 0;
                        $apbdKegiatan->status = 'active';
                        $apbdKegiatan->created_by = auth()->user()->id ?? null;
                        $apbdKegiatan->save();
                    }

                    $indicators = [];
                    $indikatorCons = DB::table('con_indikator_kinerja_kegiatan')
                        ->where('instance_id', $request->instance)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->first();
                    if ($indikatorCons) {
                        $indikators = IndikatorKegiatan::where('pivot_id', $indikatorCons->id)
                            ->get();
                        foreach ($indikators as $key => $indi) {
                            if ($renstraKegiatan->satuan_json) {
                                $satuanIdRenstra = json_decode($renstraKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanNameRenstra = Satuan::where('id', $satuanIdRenstra)->first()->name ?? null;
                            }
                            if ($renjaKegiatan->satuan_json) {
                                $satuanIdRenja = json_decode($renjaKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanNameRenja = Satuan::where('id', $satuanIdRenja)->first()->name ?? null;
                            }
                            if ($apbdKegiatan->satuan_json) {
                                $satuanIdApbd = json_decode($apbdKegiatan->satuan_json, true)[$key] ?? null;
                                $satuanNameApbd = Satuan::where('id', $satuanIdApbd)->first()->name ?? null;
                            }
                            $indicators[] = [
                                'id' => $indi->id,
                                'name' => $indi->name,
                                'value_renstra' => json_decode($renstraKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id_renstra' => $satuanIdRenstra ?? null,
                                'satuan_name_renstra' => $satuanNameRenstra ?? null,
                                'value_renja' => json_decode($renjaKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id_renja' => $satuanIdRenja ?? null,
                                'satuan_name_renja' => $satuanNameRenja ?? null,
                                'value_apbd' => json_decode($apbdKegiatan->kinerja_json, true)[$key] ?? null,
                                'satuan_id_apbd' => $satuanIdApbd ?? null,
                                'satuan_name_apbd' => $satuanNameApbd ?? null,
                            ];
                        }
                    }

                    $anggaranModalApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $apbdKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_modal');
                    $anggaranOperasiApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $apbdKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_operasi');
                    $anggaranTransferApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $apbdKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_transfer');
                    $anggaranTidakTerdugaApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $apbdKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('anggaran_tidak_terduga');
                    $totalAnggaranRenstra = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renstraKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');
                    $totalAnggaranRenja = RenjaSubKegiatan::where('renja_id', $renja->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $renjaKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');
                    $totalAnggaranApbd = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('parent_id', $apbdKegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->get()->sum('total_anggaran');

                    $apbdKegiatan->anggaran_modal = $anggaranModalApbd;
                    $apbdKegiatan->anggaran_operasi = $anggaranOperasiApbd;
                    $apbdKegiatan->anggaran_transfer = $anggaranTransferApbd;
                    $apbdKegiatan->anggaran_tidak_terduga = $anggaranTidakTerdugaApbd;
                    $apbdKegiatan->total_anggaran = $totalAnggaranApbd;
                    $apbdKegiatan->save();

                    $datas[$year][] = [
                        'id' => $kegiatan->id,
                        'type' => 'kegiatan',
                        'program_id' => $program->id,
                        'program_name' => $program->name,
                        'program_fullcode' => $program->fullcode,
                        'kegiatan_id' => $kegiatan->id,
                        'kegiatan_name' => $kegiatan->name,
                        'kegiatan_fullcode' => $kegiatan->fullcode,
                        'indicators' => $indicators,

                        'anggaran_json' => $apbdKegiatan->anggaran_json,
                        'kinerja_json' => $apbdKegiatan->kinerja_json,
                        'satuan_json' => $apbdKegiatan->satuan_json,

                        'anggaran_modal_renstra' => $renstraKegiatan->anggaran_modal,
                        'anggaran_operasi_renstra' => $renstraKegiatan->anggaran_operasi,
                        'anggaran_transfer_renstra' => $renstraKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga_renstra' => $renstraKegiatan->anggaran_tidak_terduga,

                        'anggaran_modal_renja' => $renjaKegiatan->anggaran_modal,
                        'anggaran_operasi_renja' => $renjaKegiatan->anggaran_operasi,
                        'anggaran_transfer_renja' => $renjaKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga_renja' => $renjaKegiatan->anggaran_tidak_terduga,

                        'anggaran_modal_apbd' => $apbdKegiatan->anggaran_modal,
                        'anggaran_operasi_apbd' => $apbdKegiatan->anggaran_operasi,
                        'anggaran_transfer_apbd' => $apbdKegiatan->anggaran_transfer,
                        'anggaran_tidak_terduga_apbd' => $apbdKegiatan->anggaran_tidak_terduga,

                        'total_anggaran_renstra' => $renstraKegiatan->total_anggaran,
                        'total_anggaran_renja' => $renjaKegiatan->total_anggaran,
                        'total_anggaran_apbd' => $apbdKegiatan->total_anggaran,

                        'total_kinerja' => $apbdKegiatan->total_kinerja,

                        'percent_anggaran_renstra' => $renstraKegiatan->percent_anggaran,
                        'percent_kinerja_renstra' => $renstraKegiatan->percent_kinerja,
                        'percent_anggaran_renja' => $renjaKegiatan->percent_anggaran,
                        'percent_kinerja_renja' => $renjaKegiatan->percent_kinerja,
                        'percent_anggaran_apbd' => $apbdKegiatan->percent_anggaran,
                        'percent_kinerja_apbd' => $apbdKegiatan->percent_kinerja,

                        'year' => $apbdKegiatan->year,
                        'status' => $apbdKegiatan->status,
                        'created_by' => $apbdKegiatan->CreatedBy->fullname ?? '-',
                        'updated_by' => $apbdKegiatan->UpdatedBy->fullname ?? '-',
                        'created_at' => $apbdKegiatan->created_at,
                        'updated_at' => $apbdKegiatan->updated_at,
                    ];


                    $subKegiatans = SubKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('status', 'active')
                        ->orderBy('fullcode')
                        ->get();
                    foreach ($subKegiatans as $subKegiatan) {
                        $renstraSubKegiatan = RenstraSubKegiatan::where('renstra_id', $renstra->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            // ->where('parent_id', $renstraKegiatan->id)
                            ->where('year', $year)
                            // ->where('status', 'active')
                            ->first();
                        if (!$renstraSubKegiatan) {
                            $renstraSubKegiatan = new RenstraSubKegiatan();
                            $renstraSubKegiatan->renstra_id = $renstra->id;
                            $renstraSubKegiatan->parent_id = $renstraKegiatan->id;
                            $renstraSubKegiatan->program_id = $program->id;
                            $renstraSubKegiatan->kegiatan_id = $kegiatan->id;
                            $renstraSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $renstraSubKegiatan->year = $year;
                            $renstraSubKegiatan->anggaran_json = null;
                            $renstraSubKegiatan->kinerja_json = null;
                            $renstraSubKegiatan->satuan_json = null;
                            $renstraSubKegiatan->anggaran_modal = 0;
                            $renstraSubKegiatan->anggaran_operasi = 0;
                            $renstraSubKegiatan->anggaran_transfer = 0;
                            $renstraSubKegiatan->anggaran_tidak_terduga = 0;
                            $renstraSubKegiatan->total_anggaran = 0;
                            $renstraSubKegiatan->total_kinerja = 0;
                            $renstraSubKegiatan->percent_anggaran = 0;
                            $renstraSubKegiatan->percent_kinerja = 0;
                            $renstraSubKegiatan->status = 'active';
                            $renstraSubKegiatan->created_by = auth()->user()->id ?? null;
                            $renstraSubKegiatan->save();
                        }

                        $renjaSubKegiatan = RenjaSubKegiatan::where('renja_id', $renja->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('year', $year)
                            ->first();

                        if (!$renjaSubKegiatan) {
                            $renjaSubKegiatan = new RenjaSubKegiatan();
                            $renjaSubKegiatan->renstra_id = $renstra->id;
                            $renjaSubKegiatan->renja_id = $renja->id;
                            $renjaSubKegiatan->parent_id = $renjaKegiatan->id;
                            $renjaSubKegiatan->program_id = $program->id;
                            $renjaSubKegiatan->kegiatan_id = $kegiatan->id;
                            $renjaSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $renjaSubKegiatan->year = $year;
                            $renjaSubKegiatan->anggaran_json = null;
                            $renjaSubKegiatan->kinerja_json = null;
                            $renjaSubKegiatan->satuan_json = null;
                            $renjaSubKegiatan->anggaran_modal = 0;
                            $renjaSubKegiatan->anggaran_operasi = 0;
                            $renjaSubKegiatan->anggaran_transfer = 0;
                            $renjaSubKegiatan->anggaran_tidak_terduga = 0;
                            $renjaSubKegiatan->total_anggaran = 0;
                            $renjaSubKegiatan->total_kinerja = 0;
                            $renjaSubKegiatan->percent_anggaran = 0;
                            $renjaSubKegiatan->percent_kinerja = 0;
                            $renjaSubKegiatan->status = 'active';
                            $renjaSubKegiatan->created_by = auth()->user()->id ?? null;
                            $renjaSubKegiatan->save();
                        }

                        $apbdSubKegiatan = ApbdSubKegiatan::where('apbd_id', $apbd->id)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->where('parent_id', $apbdKegiatan->id)
                            ->where('year', $year)
                            ->first();
                        if (!$apbdSubKegiatan) {
                            $apbdSubKegiatan = new ApbdSubKegiatan();
                            $apbdSubKegiatan->apbd_id = $apbd->id;
                            $apbdSubKegiatan->renstra_id = $renstra->id;
                            $apbdSubKegiatan->renja_id = $renja->id;
                            $apbdSubKegiatan->parent_id = $apbdKegiatan->id;
                            $apbdSubKegiatan->program_id = $program->id;
                            $apbdSubKegiatan->kegiatan_id = $kegiatan->id;
                            $apbdSubKegiatan->sub_kegiatan_id = $subKegiatan->id;
                            $apbdSubKegiatan->year = $year;
                            $apbdSubKegiatan->anggaran_json = null;
                            $apbdSubKegiatan->kinerja_json = null;
                            $apbdSubKegiatan->satuan_json = null;
                            $apbdSubKegiatan->anggaran_modal = 0;
                            $apbdSubKegiatan->anggaran_operasi = 0;
                            $apbdSubKegiatan->anggaran_transfer = 0;
                            $apbdSubKegiatan->anggaran_tidak_terduga = 0;
                            $apbdSubKegiatan->total_anggaran = 0;
                            $apbdSubKegiatan->total_kinerja = 0;
                            $apbdSubKegiatan->percent_anggaran = 0;
                            $apbdSubKegiatan->percent_kinerja = 0;
                            $apbdSubKegiatan->status = 'active';
                            $apbdSubKegiatan->created_by = auth()->user()->id ?? null;
                            $apbdSubKegiatan->save();
                        }

                        $indicators = [];
                        $indikatorCons = DB::table('con_indikator_kinerja_sub_kegiatan')
                            ->where('instance_id', $request->instance)
                            ->where('program_id', $program->id)
                            ->where('kegiatan_id', $kegiatan->id)
                            ->where('sub_kegiatan_id', $subKegiatan->id)
                            ->first();
                        if ($indikatorCons) {
                            $indikators = IndikatorSubKegiatan::where('pivot_id', $indikatorCons->id)
                                ->get();
                            foreach ($indikators as $key => $indi) {

                                $arrSatuanIdsRenstra = $renstraSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIdsRenstra) {
                                    $satuanIdRenstra = json_decode($renstraSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanNameRenstra = Satuan::where('id', $satuanIdRenstra)->first()->name ?? null;
                                }
                                $arrSatuanIdsRenja = $renjaSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIdsRenja) {
                                    $satuanIdRenja = json_decode($renjaSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanNameRenja = Satuan::where('id', $satuanIdRenja)->first()->name ?? null;
                                }
                                $arrSatuanIdsApbd = $apbdSubKegiatan->satuan_json ?? null;
                                if ($arrSatuanIdsApbd) {
                                    $satuanIdApbd = json_decode($apbdSubKegiatan->satuan_json, true)[$key] ?? null;
                                    $satuanNameApbd = Satuan::where('id', $satuanIdApbd)->first()->name ?? null;
                                }

                                $arrKinerjaValues = $renstraSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValues) {
                                    $value = json_decode($renstraSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }
                                $arrKinerjaValuesRenja = $renjaSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValuesRenja) {
                                    $valueRenja = json_decode($renjaSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }
                                $arrKinerjaValuesApbd = $apbdSubKegiatan->kinerja_json ?? null;
                                if ($arrKinerjaValuesApbd) {
                                    $valueApbd = json_decode($apbdSubKegiatan->kinerja_json, true)[$key] ?? null;
                                }

                                $indicators[] = [
                                    'id' => $indi->id,
                                    'name' => $indi->name,
                                    'value_renstra' => $value ?? null,
                                    'value_renja' => $valueRenja ?? null,
                                    'value_apbd' => $valueApbd ?? null,
                                    'satuan_id_renstra' => $satuanIdRenstra ?? null,
                                    'satuan_name_renstra' => $satuanNameRenstra ?? null,
                                    'satuan_id_renja' => $satuanIdRenja ?? null,
                                    'satuan_name_renja' => $satuanNameRenja ?? null,
                                    'satuan_id_apbd' => $satuanIdApbd ?? null,
                                    'satuan_name_apbd' => $satuanNameApbd ?? null,
                                ];
                            }
                        }
                        $datas[$year][] = [
                            'id' => $subKegiatan->id,
                            'type' => 'sub-kegiatan',
                            'program_id' => $program->id,
                            'program_name' => $program->name ?? null,
                            'program_fullcode' => $program->fullcode,
                            'kegiatan_id' => $kegiatan->id,
                            'kegiatan_name' => $kegiatan->name ?? null,
                            'kegiatan_fullcode' => $kegiatan->fullcode,
                            'sub_kegiatan_id' => $subKegiatan->id,
                            'sub_kegiatan_name' => $subKegiatan->name ?? null,
                            'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                            'indicators' => $indicators,

                            'anggaran_modal_renstra' => $renstraSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi_renstra' => $renstraSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer_renstra' => $renstraSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga_renstra' => $renstraSubKegiatan->anggaran_tidak_terduga ?? null,

                            'anggaran_modal_renja' => $renjaSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi_renja' => $renjaSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer_renja' => $renjaSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga_renja' => $renjaSubKegiatan->anggaran_tidak_terduga ?? null,

                            'anggaran_modal_apbd' => $apbdSubKegiatan->anggaran_modal ?? null,
                            'anggaran_operasi_apbd' => $apbdSubKegiatan->anggaran_operasi ?? null,
                            'anggaran_transfer_apbd' => $apbdSubKegiatan->anggaran_transfer ?? null,
                            'anggaran_tidak_terduga_apbd' => $apbdSubKegiatan->anggaran_tidak_terduga ?? null,

                            'total_anggaran_renstra' => $renstraSubKegiatan->total_anggaran ?? null,
                            'total_anggaran_renja' => $renjaSubKegiatan->total_anggaran ?? null,
                            'total_anggaran_apbd' => $apbdSubKegiatan->total_anggaran ?? null,

                            'percent_anggaran_renstra' => $renstraSubKegiatan->percent_anggaran,
                            'percent_kinerja_renstra' => $renstraSubKegiatan->percent_kinerja,
                            'percent_anggaran_renja' => $renjaSubKegiatan->percent_anggaran,
                            'percent_kinerja_renja' => $renjaSubKegiatan->percent_kinerja,
                            'percent_anggaran_apbd' => $apbdSubKegiatan->percent_anggaran,
                            'percent_kinerja_apbd' => $apbdSubKegiatan->percent_kinerja,


                            'year' => $apbdSubKegiatan->year ?? null,
                            'status' => $apbdSubKegiatan->status ?? null,
                            'created_by' => $apbdSubKegiatan->CreatedBy->fullname ?? '-',
                            'updated_by' => $apbdSubKegiatan->UpdatedBy->fullname ?? '-',
                            'created_at' => $apbdSubKegiatan->created_at ?? null,
                            'updated_at' => $apbdSubKegiatan->updated_at ?? null,
                        ];
                    }
                }
            }

            $renstra = [
                'id' => $renstra->id,
                'rpjmd_id' => $renstra->rpjmd_id,
                'rpjmd_data' => $renstra->RPJMD,
                'program_id' => $renstra->program_id,
                'program_name' => $renstra->Program->name ?? null,
                'program_fullcode' => $renstra->Program->fullcode ?? null,
                'total_anggaran' => $renstra->total_anggaran,
                'total_kinerja' => $renstra->total_kinerja,
                'percent_anggaran' => $renstra->percent_anggaran,
                'percent_kinerja' => $renstra->percent_kinerja,
                'status' => $renstra->status,
                'status_leader' => $renstra->status_leader,
                'notes_verificator' => $renstra->notes_verificator,
                'created_by' => $renstra->created_by,
                'CreatedBy' => $renstra->CreatedBy->fullname ?? null,
                'updated_by' => $renstra->updated_by,
                'UpdatedBy' => $renstra->UpdatedBy->fullname ?? null,
                'created_at' => $renstra->created_at,
                'updated_at' => $renstra->updated_at,
            ];
            $renja = [
                'id' => $renja->id,
                'periode_id' => $renja->periode_id,
                'periode_data' => $renja->Periode->name ?? null,
                'instance_id' => $renja->instance_id,
                'instance_data' => $renja->Instance->name ?? null,
                'renstra_id' => $renja->renstra_id,
                'renstra_data' => $renja->Renstra->Program->name ?? null,
                'program_id' => $renja->program_id,
                'program_name' => $renja->Program->name ?? null,
                'program_fullcode' => $renja->Program->fullcode ?? null,
                'total_anggaran' => $renja->total_anggaran,
                'total_kinerja' => $renja->total_kinerja,
                'percent_anggaran' => $renja->percent_anggaran,
                'percent_kinerja' => $renja->percent_kinerja,
                'status' => $renja->status,
                'status_leader' => $renja->status_leader,
                'notes_verificator' => $renja->notes_verificator,
                'created_by' => $renja->created_by,
                'CreatedBy' => $renja->CreatedBy->fullname ?? null,
                'updated_by' => $renja->updated_by,
                'UpdatedBy' => $renja->UpdatedBy->fullname ?? null,
                'created_at' => $renja->created_at,
                'updated_at' => $renja->updated_at,
            ];
            $apbd = [
                'id' => $apbd->id,
                'periode_id' => $apbd->periode_id,
                'periode_data' => $apbd->Periode->name ?? null,
                'instance_id' => $apbd->instance_id,
                'instance_data' => $apbd->Instance->name ?? null,
                'program_id' => $apbd->program_id,
                'program_name' => $apbd->Program->name ?? null,
                'program_fullcode' => $apbd->Program->fullcode ?? null,
                'total_anggaran' => $apbd->total_anggaran,
                'total_kinerja' => $apbd->total_kinerja,
                'percent_anggaran' => $apbd->percent_anggaran,
                'percent_kinerja' => $apbd->percent_kinerja,
                'status' => $apbd->status,
                'status_leader' => $apbd->status_leader,
                'notes_verificator' => $apbd->notes_verificator,
                'created_by' => $apbd->created_by,
                'CreatedBy' => $apbd->CreatedBy->fullname ?? null,
                'updated_by' => $apbd->updated_by,
                'UpdatedBy' => $apbd->UpdatedBy->fullname ?? null,
                'created_at' => $apbd->created_at,
                'updated_at' => $apbd->updated_at,
            ];

            DB::commit();
            return $this->successResponse([
                'datas' => $datas,
                'renstra' => $renstra,
                'renja' => $renja,
                'apbd' => $apbd,
                'range' => $range,
            ], 'List APBD');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function detailCaramApbd($id, Request $request)
    {
        return $this->errorResponse('Fitur ini sedang dalam pengembangan');
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $kegiatan = Kegiatan::find($id);
                if (!$kegiatan) {
                    return $this->errorResponse('Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->first();
                $arrIndikator = IndikatorKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $apbdDetail = ApbdKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $kegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {

                    if ($apbdDetail->kinerja_json) {
                        $value = json_decode($apbdDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($apbdDetail->satuan_json) {
                        $satuanId = json_decode($apbdDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $apbdDetail->total_anggaran,
                    'anggaran_modal' => $apbdDetail->anggaran_modal,
                    'anggaran_operasi' => $apbdDetail->anggaran_operasi,
                    'anggaran_transfer' => $apbdDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $apbdDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $apbdDetail->percent_anggaran,
                    'percent_kinerja' => $apbdDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $kegiatan->id,
                    'id_apbd_detail' => $apbdDetail->id,
                    'type' => 'kegiatan',
                    'program_id' => $apbdDetail->program_id,
                    'program_name' => $kegiatan->Program->name ?? null,
                    'program_fullcode' => $kegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $kegiatan->id,
                    'kegiatan_name' => $kegiatan->name ?? null,
                    'kegiatan_fullcode' => $kegiatan->fullcode,
                    'year' => $apbdDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $apbdDetail->total_anggaran,
                    'percent_anggaran' => $apbdDetail->percent_anggaran,
                    'percent_kinerja' => $apbdDetail->percent_kinerja,
                ];


                // DB::commit();
                return $this->successResponse($datas, 'Detail Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $datas = [];
                $subKegiatan = SubKegiatan::find($id);
                if (!$subKegiatan) {
                    return $this->errorResponse('Sub Kegiatan tidak ditemukan');
                }

                $indicators = [];
                $anggaran = [];
                $conIndikator = DB::table('con_indikator_kinerja_sub_kegiatan')
                    ->where('instance_id', $request->instance)
                    ->where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->first();
                $arrIndikator = IndikatorSubKegiatan::where('pivot_id', $conIndikator->id)
                    ->get();
                $apbdDetail = ApbdSubKegiatan::where('program_id', $request->program)
                    ->where('kegiatan_id', $subKegiatan->kegiatan_id)
                    ->where('sub_kegiatan_id', $subKegiatan->id)
                    ->where('year', $request->year)
                    ->where('status', 'active')
                    ->first();
                foreach ($arrIndikator as $key => $indikator) {

                    if ($apbdDetail->kinerja_json) {
                        $value = json_decode($apbdDetail->kinerja_json, true)[$key] ?? null;
                    }
                    if ($apbdDetail->satuan_json) {
                        $satuanId = json_decode($apbdDetail->satuan_json, true)[$key] ?? null;
                        $satuanName = Satuan::where('id', $satuanId)->first()->name ?? null;
                    }
                    $indicators[] = [
                        'id_indikator' => $indikator->id,
                        'name' => $indikator->name,
                        'value' => $value ?? null,
                        'satuan_id' => $satuanId ?? null,
                        'satuan_name' => $satuanName ?? null,
                    ];
                }

                $anggaran = [
                    'total_anggaran' => $apbdDetail->total_anggaran,
                    'anggaran_modal' => $apbdDetail->anggaran_modal,
                    'anggaran_operasi' => $apbdDetail->anggaran_operasi,
                    'anggaran_transfer' => $apbdDetail->anggaran_transfer,
                    'anggaran_tidak_terduga' => $apbdDetail->anggaran_tidak_terduga,
                    'percent_anggaran' => $apbdDetail->percent_anggaran,
                    'percent_kinerja' => $apbdDetail->percent_kinerja,
                ];

                $datas = [
                    'id' => $subKegiatan->id,
                    'id_apbd_detail' => $apbdDetail->id,
                    'type' => 'sub-kegiatan',
                    'program_id' => $apbdDetail->program_id,
                    'program_name' => $subKegiatan->Program->name ?? null,
                    'program_fullcode' => $subKegiatan->Program->fullcode ?? null,
                    'kegiatan_id' => $apbdDetail->kegiatan_id,
                    'kegiatan_name' => $subKegiatan->Kegiatan->name ?? null,
                    'kegiatan_fullcode' => $subKegiatan->Kegiatan->fullcode,
                    'sub_kegiatan_id' => $subKegiatan->id,
                    'sub_kegiatan_name' => $subKegiatan->name ?? null,
                    'sub_kegiatan_fullcode' => $subKegiatan->fullcode,
                    'year' => $apbdDetail->year,
                    'indicators' => $indicators,
                    'anggaran' => $anggaran,
                    'total_anggaran' => $apbdDetail->total_anggaran,
                    'percent_anggaran' => $apbdDetail->percent_anggaran,
                    'percent_kinerja' => $apbdDetail->percent_kinerja,
                ];

                // DB::commit();
                return $this->successResponse($datas, 'Detail Sub Kegiatan');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        return $this->errorResponse('Tipe tidak ditemukan');
    }

    function saveCaramApbd($id, Request $request)
    {
        return $this->errorResponse('Fitur ini sedang dalam pengembangan');
        if ($request->type == 'kegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = ApbdKegiatan::find($request->data['id_apbd_detail']);

                $anggaranModal = 0;
                $anggaranModal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $anggaranModal = str_replace('.', '', $anggaranModal);
                $anggaranModal = str_replace('e', '', $anggaranModal);
                $anggaranModal = str_replace('E', '', $anggaranModal);
                $data->anggaran_modal = $anggaranModal;

                $anggaranOperasi = 0;
                $anggaranOperasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $anggaranOperasi = str_replace('.', '', $anggaranOperasi);
                $anggaranOperasi = str_replace('e', '', $anggaranOperasi);
                $anggaranOperasi = str_replace('E', '', $anggaranOperasi);
                $data->anggaran_operasi = $anggaranOperasi;

                $anggaranTransfer = 0;
                $anggaranTransfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $anggaranTransfer = str_replace('.', '', $anggaranTransfer);
                $anggaranTransfer = str_replace('e', '', $anggaranTransfer);
                $anggaranTransfer = str_replace('E', '', $anggaranTransfer);
                $data->anggaran_transfer = $anggaranTransfer;

                $anggaranTidakTerduga = 0;
                $anggaranTidakTerduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $anggaranTidakTerduga = str_replace('.', '', $anggaranTidakTerduga);
                $anggaranTidakTerduga = str_replace('e', '', $anggaranTidakTerduga);
                $anggaranTidakTerduga = str_replace('E', '', $anggaranTidakTerduga);
                $data->anggaran_tidak_terduga = $anggaranTidakTerduga;

                $totalAnggaran = 0;
                $totalAnggaran = $request->data['total_anggaran'] ?? 0;
                $totalAnggaran = str_replace('.', '', $totalAnggaran);
                $totalAnggaran = str_replace('e', '', $totalAnggaran);
                $totalAnggaran = str_replace('E', '', $totalAnggaran);
                $data->total_anggaran = $totalAnggaran;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'] ?? null;
                    $satuanArray[] = $indi['satuan_id'] ?? null;
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);

                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->updated_by = auth()->user()->id ?? null;
                $data->save();

                $apbd = Apbd::find($data->apbd_id);
                $apbd->total_anggaran = $apbd->calculateTotalAnggaranFromSubKegiatans();
                $apbd->updated_by = auth()->user()->id ?? null;
                $apbd->updated_at = Carbon::now();
                $apbd->save();

                DB::commit();
                return $this->successResponse($data, 'Data Apbd Berhasil diperbarui');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }

        if ($request->type == 'subkegiatan') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'instance' => 'required|numeric|exists:instances,id',
                'program' => 'required|numeric|exists:ref_program,id',
                'year' => 'required|numeric',
            ], [], [
                'periode' => 'Periode',
                'instance' => 'Perangkat Daerah',
                'program' => 'Program',
                'year' => 'Tahun',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            DB::beginTransaction();
            try {
                $data = ApbdSubKegiatan::find($request->data['id_apbd_detail']);
                // $data->anggaran_modal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                // $data->anggaran_operasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                // $data->anggaran_transfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                // $data->anggaran_tidak_terduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                // $data->total_anggaran = $request->data['total_anggaran'] ?? 0;

                $anggaranModal = 0;
                $anggaranModal = $request->data['anggaran']['anggaran_modal'] ?? 0;
                $anggaranModal = str_replace('.', '', $anggaranModal);
                $anggaranModal = str_replace('e', '', $anggaranModal);
                $anggaranModal = str_replace('E', '', $anggaranModal);
                $data->anggaran_modal = $anggaranModal;

                $anggaranOperasi = 0;
                $anggaranOperasi = $request->data['anggaran']['anggaran_operasi'] ?? 0;
                $anggaranOperasi = str_replace('.', '', $anggaranOperasi);
                $anggaranOperasi = str_replace('e', '', $anggaranOperasi);
                $anggaranOperasi = str_replace('E', '', $anggaranOperasi);
                $data->anggaran_operasi = $anggaranOperasi;

                $anggaranTransfer = 0;
                $anggaranTransfer = $request->data['anggaran']['anggaran_transfer'] ?? 0;
                $anggaranTransfer = str_replace('.', '', $anggaranTransfer);
                $anggaranTransfer = str_replace('e', '', $anggaranTransfer);
                $anggaranTransfer = str_replace('E', '', $anggaranTransfer);
                $data->anggaran_transfer = $anggaranTransfer;

                $anggaranTidakTerduga = 0;
                $anggaranTidakTerduga = $request->data['anggaran']['anggaran_tidak_terduga'] ?? 0;
                $anggaranTidakTerduga = str_replace('.', '', $anggaranTidakTerduga);
                $anggaranTidakTerduga = str_replace('e', '', $anggaranTidakTerduga);
                $anggaranTidakTerduga = str_replace('E', '', $anggaranTidakTerduga);
                $data->anggaran_tidak_terduga = $anggaranTidakTerduga;

                $totalAnggaran = 0;
                $totalAnggaran = $request->data['total_anggaran'] ?? 0;
                $totalAnggaran = str_replace('.', '', $totalAnggaran);
                $totalAnggaran = str_replace('e', '', $totalAnggaran);
                $totalAnggaran = str_replace('E', '', $totalAnggaran);
                $data->total_anggaran = $totalAnggaran;

                $kinerjaArray = [];
                $satuanArray = [];
                $indicators = $request->data['indicators'];
                foreach ($indicators as $indi) {
                    $kinerjaArray[] = $indi['value'];
                    $satuanArray[] = $indi['satuan_id'];
                }
                $data->kinerja_json = json_encode($kinerjaArray, true);
                $data->satuan_json = json_encode($satuanArray, true);

                $percentAnggaran = 0;
                if ($request->data['percent_anggaran'] > 100) {
                    $percentAnggaran = 100;
                } elseif ($request->data['percent_anggaran'] < 0) {
                    $percentAnggaran = 0;
                } else {
                    $percentAnggaran = $request->data['percent_anggaran'];
                }
                $data->percent_anggaran = $percentAnggaran;

                $percentKinerja = 0;
                if ($request->data['percent_kinerja'] > 100) {
                    $percentKinerja = 100;
                } elseif ($request->data['percent_kinerja'] < 0) {
                    $percentKinerja = 0;
                } else {
                    $percentKinerja = $request->data['percent_kinerja'];
                }
                $data->percent_kinerja = $percentKinerja;
                $data->updated_by = auth()->user()->id ?? null;
                $data->save();

                $parent = ApbdKegiatan::find($data->parent_id);
                $percentKinerja = ApbdSubKegiatan::where('parent_id', $parent->id)
                    // ->where('status', 'active')
                    ->get()
                    ->avg('percent_kinerja');
                $parent->percent_kinerja = $percentKinerja;
                $parent->updated_by = auth()->user()->id ?? null;
                $parent->updated_at = Carbon::now();
                $parent->save();

                $apbd = Apbd::find($data->apbd_id);
                // $apbd->total_anggaran = $apbd->calculateTotalAnggaranFromSubKegiatans();
                $apbd->total_anggaran = $apbd->detailKegiatan->sum('total_anggaran');
                // $apbd->percent_kinerja = $apbd->calculetePercentKinerja();
                $apbd->percent_kinerja = $percentKinerja;
                $apbd->updated_by = auth()->user()->id ?? null;
                $apbd->updated_at = Carbon::now();
                $apbd->save();

                DB::commit();
                return $this->successResponse($apbd, 'Data APBD Berhasil diperbarui');
            } catch (\Throwable $th) {
                DB::rollback();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }
    }

    function listCaramApbdNotes($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renja' => 'required|numeric|exists:data_renja,id',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renja' => 'Renja',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $datas = [];
        $notes = DB::table('notes_apbd')
            ->where('apbd_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        foreach ($notes as $note) {
            $user = User::find($note->user_id);
            $datas[] = [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'user_name' => $user->fullname ?? null,
                'user_photo' => asset($user->photo) ?? null,
                'message' => $note->message,
                'status' => $note->status,
                'type' => $note->type,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
            ];
        }

        return $this->successResponse($datas, 'Notes APBD');
    }

    function postCaramApbdNotes($id, Request $request)
    {
        return $this->errorResponse('Fitur ini sedang dalam pengembangan');
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'instance' => 'required|numeric|exists:instances,id',
            'program' => 'required|numeric|exists:ref_program,id',
            // 'renja' => 'required|numeric|exists:data_renja,id',
            'message' => 'required|string',
            'status' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'instance' => 'Perangkat Daerah',
            'program' => 'Program',
            // 'renja' => 'Renja',
            'message' => 'Pesan',
            'status' => 'Status',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $apbd = Apbd::find($id);
            if (!$apbd) {
                return $this->errorResponse('APBD tidak ditemukan');
            }
            if (auth()->user()->role_id == 9) {
                $type = 'request';
                $apbd->status = $request->status;
                $apbd->save();

                // send notification
                $users = User::where('role_id', 7)->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $apbd->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    null,
                    'Permintaan Verifikasi APBD',
                    'Permintaan Verifikasi APBD dari ' . auth()->user()->fullname,
                ));
            } else {
                $type = 'return';
                $apbd->status = $request->status;
                $apbd->notes_verificator = $request->message;
                $apbd->save();

                // send notification
                $users = User::where('role_id', 9)
                    ->where('instance_id', $apbd->instance_id)
                    ->get();
                Notification::send($users, new GlobalNotification(
                    'sent',
                    $apbd->id,
                    auth()->user()->id,
                    $users->pluck('id')->toArray(),
                    null,
                    'Verifikasi APBD',
                    auth()->user()->fullname . ' telah memberikan verifikasi APBD',
                ));
            }
            $note = DB::table('notes_apbd')
                ->insert([
                    'apbd_id' => $id,
                    'user_id' => auth()->user()->id,
                    'message' => $request->message,
                    'status' => $request->status,
                    'type' => $type ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::commit();
            return $this->successResponse($note, 'Pesan Berhasil dikirim');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function listRekening(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'level' => 'required|numeric',
            'parent_id' => 'nullable|numeric',
        ], [], [
            'periode' => 'Periode',
            'level' => 'Level',
            'parent_id' => 'Parent',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $datas = [];

        if ($request->level == 1) {
            $datas = KodeRekening::whereNull('code_2')
                ->where('periode_id', $request->periode)
                ->whereNull('code_3')
                ->whereNull('code_4')
                ->whereNull('code_5')
                ->whereNull('code_6')
                ->get();
            foreach ($datas as $data) {
                $data->level = 1;
                $data->type = 'Akun';
                $data->parent_id = null;
            }
        }

        if ($request->level == 2) {
            $datas = KodeRekening::where('parent_id', $request->parent_id)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $data->level = 2;
                $data->type = 'Kelompok';
                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
            }
        }

        if ($request->level == 3) {
            $datas = KodeRekening::where('parent_id', $request->parent_id)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $data->level = 3;
                $data->type = 'Jenis';
                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
            }
        }

        if ($request->level == 4) {
            $datas = KodeRekening::where('parent_id', $request->parent_id)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $data->level = 4;
                $data->type = 'objek';
                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
            }
        }

        if ($request->level == 5) {
            $datas = KodeRekening::where('parent_id', $request->parent_id)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $data->level = 5;
                $data->type = 'Rincian';
                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
                $data->parent_4_id = $data->Parent('Objek')->id;
            }
        }

        if ($request->level == 6) {
            $datas = KodeRekening::where('parent_id', $request->parent_id)
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $data->level = 6;
                $data->type = 'Sub Rincian';
                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
                $data->parent_4_id = $data->Parent('Objek')->id;
                $data->parent_5_id = $data->Parent('Rincian')->id;
            }
        }

        $datas = collect($datas)->sortBy('fullcode')->values()->all();
        if (count($datas) == 0) {
            return $this->errorResponse('Data tidak ditemukan');
        }

        return $this->successResponse($datas, 'List Rekening');
    }

    function createRekening(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'level' => 'required|numeric',
            'name' => 'required|string',
            'code' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'level' => 'Level',
            'name' => 'Nama',
            'code' => 'Kode',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();

        try {
            $level = $request->level;
            if ($level == 1) {
                $data = new KodeRekening;
                $data->name = $request->name;
                $data->code_1 = $request->code;
                $data->code_2 = null;
                $data->code_3 = null;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = null;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 2) {
                $data = new KodeRekening;
                $parent1 = KodeRekening::find($request->rek_1_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $request->code;
                $data->code_3 = null;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_1_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = $request->rek_1_id;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 3) {
                $data = new KodeRekening;
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $request->code;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_2_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = $request->rek_2_id;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 4) {
                $data = new KodeRekening;
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $request->code;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_3_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = $request->rek_3_id;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 5) {
                $data = new KodeRekening;
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $parent4 = KodeRekening::find($request->rek_4_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $parent4->code_4;
                $data->code_5 = $request->code;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_4_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = $request->rek_4_id;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 6) {
                $data = new KodeRekening;
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $parent4 = KodeRekening::find($request->rek_4_id);
                $parent5 = KodeRekening::find($request->rek_5_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $parent4->code_4;
                $data->code_5 = $parent5->code_5;
                $data->code_6 = $request->code;
                $data->fullcode = KodeRekening::find($request->rek_5_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->year = $request->year;
                $data->parent_id = $request->rek_5_id;
                $data->status = 'active';
                $data->created_by = auth()->user()->id;
                $data->save();
            }

            DB::commit();
            return $this->successResponse($data, 'Data Rekening Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function detailRekening($id, Request $request)
    {
        $validation = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'level' => 'required|numeric',
        ], [], [
            'periode' => 'Periode',
            'level' => 'Level',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        try {
            $level = $request->level;

            if ($level == 1) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 1;
                $data->type = 'Akun';
                $data->code = $data->code_1;

                $data->parent_id = null;
                return $this->successResponse($data, 'Detail Rekening');
            }

            if ($level == 2) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 2;
                $data->type = 'Kelompok';
                $data->code = $data->code_2;

                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                return $this->successResponse($data, 'Detail Rekening');
            }

            if ($level == 3) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 3;
                $data->type = 'Jenis';
                $data->code = $data->code_3;

                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                return $this->successResponse($data, 'Detail Rekening');
            }

            if ($level == 4) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 4;
                $data->type = 'Objek';
                $data->code = $data->code_4;

                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
                return $this->successResponse($data, 'Detail Rekening');
            }

            if ($level == 5) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 5;
                $data->type = 'Rincian';
                $data->code = $data->code_5;

                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
                $data->parent_4_id = $data->Parent('Objek')->id;

                return $this->successResponse($data, 'Detail Rekening');
            }

            if ($level == 6) {
                $data = KodeRekening::where('periode_id', $request->periode)
                    ->where('id', $id)
                    ->first();
                $data->level = 6;
                $data->type = 'Sub Rincian';
                $data->code = $data->code_6;

                $data->parent_id = $data->parent_id;
                $data->parent_1_id = $data->Parent('Akun')->id;
                $data->parent_2_id = $data->Parent('Kelompok')->id;
                $data->parent_3_id = $data->Parent('Jenis')->id;
                $data->parent_4_id = $data->Parent('Objek')->id;
                $data->parent_5_id = $data->Parent('Rincian')->id;

                return $this->successResponse($data, 'Detail Rekening');
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function updateRekening($id, Request $request)
    {
        $validation = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'level' => 'required|numeric',
            'name' => 'required|string',
            'code' => 'required|string',
        ], [], [
            'periode' => 'Periode',
            'level' => 'Level',
            'name' => 'Nama',
            'code' => 'Kode',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        DB::beginTransaction();

        try {
            $level = $request->level;

            if ($level == 1) {
                $data = KodeRekening1::find($id);
                $data->name = $request->name;
                $data->code_1 = $request->code;
                $data->code_2 = null;
                $data->code_3 = null;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 2) {
                $data = KodeRekening::find($id);
                $parent1 = KodeRekening::find($request->rek_1_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $request->code;
                $data->code_3 = null;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_1_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 3) {
                $data = KodeRekening::find($id);
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $request->code;
                $data->code_4 = null;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_2_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 4) {
                $data = KodeRekening::find($id);
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $request->code;
                $data->code_5 = null;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_3_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 5) {
                $data = KodeRekening::find($id);
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $parent4 = KodeRekening::find($request->rek_4_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $parent4->code_4;
                $data->code_5 = $request->code;
                $data->code_6 = null;
                $data->fullcode = KodeRekening::find($request->rek_4_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            if ($level == 6) {
                $data = KodeRekening::find($id);
                $parent1 = KodeRekening::find($request->rek_1_id);
                $parent2 = KodeRekening::find($request->rek_2_id);
                $parent3 = KodeRekening::find($request->rek_3_id);
                $parent4 = KodeRekening::find($request->rek_4_id);
                $parent5 = KodeRekening::find($request->rek_5_id);
                $data->name = $request->name;
                $data->code_1 = $parent1->code_1;
                $data->code_2 = $parent2->code_2;
                $data->code_3 = $parent3->code_3;
                $data->code_4 = $parent4->code_4;
                $data->code_5 = $parent5->code_5;
                $data->code_6 = $request->code;
                $data->fullcode = KodeRekening::find($request->rek_5_id)->fullcode . '.' . $request->code;
                $data->periode_id = $request->periode;
                $data->updated_by = auth()->user()->id;
                $data->save();
            }

            DB::commit();
            return $this->successResponse($data, 'Data Rekening Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function deleteRekening($id, Request $request)
    {
        $validation = Validator::make($request->all(), [
            // 'periode' => 'required|numeric|exists:ref_periode,id',
            'level' => 'required|numeric',
        ], [], [
            // 'periode' => 'Periode',
            'level' => 'Level',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        DB::beginTransaction();
        try {
            $level = $request->level;

            if ($level == 1) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            if ($level == 2) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            if ($level == 3) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            if ($level == 4) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            if ($level == 5) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            if ($level == 6) {
                $data = KodeRekening::find($id);
                $data->delete();
            }

            DB::commit();
            return $this->successResponse($data, 'Data Rekening Berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function uploadRekening(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
            'periode' => 'required|numeric|exists:ref_periode,id'
        ], [], [
            'file' => 'File',
            'periode' => 'Periode',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        DB::beginTransaction();
        try {
            $periode = $request->periode;
            $year = $request->year;

            $files = glob(storage_path('app/public/rekening/*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $file = $request->file('file');
            $path = $file->store('public/rekening');
            $path = str_replace('public/', '', $path);
            $path = storage_path('app/public/' . $path);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            if (
                $sheet->getCellByColumnAndRow(1, 1)->getValue() !== 'KODE' &&
                $sheet->getCellByColumnAndRow(2, 1)->getValue() !== 'URAIAN' &&
                $sheet->getCellByColumnAndRow(3, 1)->getValue() !== 'SEBELUM PERGESERAN JUMLAH (Rp)' &&
                $sheet->getCellByColumnAndRow(4, 1)->getValue() !== 'SESUDAH PERGESERAN JUMLAH (Rp)' &&
                $sheet->getCellByColumnAndRow(5, 1)->getValue() !== 'SELISIH (Rp)'
            ) {
                return $this->errorResponse('Format Excel tidak sesuai');
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 2; $row <= $highestRow; $row++) {
                $fullcode = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $code1 = null;
                $code2 = null;
                $code3 = null;
                $code4 = null;
                $code5 = null;
                $code6 = null;
                if ($fullcode !== null) {
                    $fullcode = (string)$fullcode;
                    $code1 = substr($fullcode, 0, 1);
                    $code2 = substr($fullcode, 2, 1);
                    if ($code2 === '') {
                        $code2 = null;
                    }
                    $code3 = substr($fullcode, 4, 2);
                    if ($code3 === '') {
                        $code3 = null;
                    }
                    $code4 = substr($fullcode, 7, 2);
                    if ($code4 === '') {
                        $code4 = null;
                    }
                    $code5 = substr($fullcode, 10, 2);
                    if ($code5 === '') {
                        $code5 = null;
                    }
                    $code6 = substr($fullcode, 13, 4);
                    if ($code6 === '') {
                        $code6 = null;
                    }
                }

                $uraian = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $uraian = str()->squish($uraian);

                $paguSebelumPergeseran = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                if ($paguSebelumPergeseran !== null) {
                    $paguSebelumPergeseran = str_replace('.', '', $paguSebelumPergeseran);
                    $paguSebelumPergeseran = str_replace(',', '.', $paguSebelumPergeseran);
                }
                $paguSetelahPergeseran = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                if ($paguSetelahPergeseran !== null) {
                    $paguSetelahPergeseran = str_replace('.', '', $paguSetelahPergeseran);
                    $paguSetelahPergeseran = str_replace(',', '.', $paguSetelahPergeseran);
                }
                $paguSelisih = $sheet->getCellByColumnAndRow(5, $row)->getValue();
                if ($paguSelisih !== null) {
                    $paguSelisih = str_replace('.', '', $paguSelisih);
                    $paguSelisih = str_replace(',', '.', $paguSelisih);
                }

                $data = KodeRekening::where('fullcode', $fullcode)
                    ->where('periode_id', $request->periode)
                    ->where('periode_id', $periode)
                    ->first();

                if (!$data) {
                    $data = new KodeRekening();
                    $data->status = 'active';
                    $data->created_by = 6;
                }
                $data->periode_id = $periode;
                $data->year = $year;
                $data->code_1 = isset($code1) ? $code1 : null;
                $data->code_2 = isset($code2) ? $code2 : null;
                $data->code_3 = isset($code3) ? $code3 : null;
                $data->code_4 = isset($code4) ? $code4 : null;
                $data->code_5 = isset($code5) ? $code5 : null;
                $data->code_6 = isset($code6) ? $code6 : null;
                $data->fullcode = $fullcode;
                $data->name = $uraian;
                $data->pagu_sebelum_pergeseran = $paguSebelumPergeseran ?? 0;
                $data->pagu_sesudah_pergeseran = $paguSetelahPergeseran ?? 0;
                $data->pagu_selisih = $paguSelisih ?? 0;

                $parent = null;
                if ($code2 && !$code3 && !$code4 && !$code5 && !$code6) {
                    $parent = KodeRekening::where('code_1', $code1)
                        ->where('periode_id', $request->periode)
                        ->first();
                }
                if ($code3 && !$code4 && !$code5 && !$code6) {
                    $parent = KodeRekening::where('code_1', $code1)
                        ->where('code_2', $code2)
                        ->where('periode_id', $request->periode)
                        ->first();
                }
                if ($code4 && !$code5 && !$code6) {
                    $parent = KodeRekening::where('code_1', $code1)
                        ->where('code_2', $code2)
                        ->where('code_3', $code3)
                        ->where('periode_id', $request->periode)
                        ->first();
                }
                if ($code5 && !$code6) {
                    $parent = KodeRekening::where('code_1', $code1)
                        ->where('code_2', $code2)
                        ->where('code_3', $code3)
                        ->where('code_4', $code4)
                        ->where('periode_id', $request->periode)
                        ->first();
                }
                if ($code6) {
                    $parent = KodeRekening::where('code_1', $code1)
                        ->where('code_2', $code2)
                        ->where('code_3', $code3)
                        ->where('code_4', $code4)
                        ->where('code_5', $code5)
                        ->where('periode_id', $request->periode)
                        ->first();
                }
                $data->parent_id = $parent->id ?? null;
                $data->save();
            }
            DB::commit();
            return $this->successResponse(null, 'Data Rekening Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function listSumberDana(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
        ], [], [
            'periode' => 'Periode',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $return = [];
            $datas = KodeSumberDana::where('periode_id', $request->periode)
                ->orderBy('fullcode', 'asc')
                ->get();
            foreach ($datas as $data) {
                $return[] = [
                    'id' => $data->id,
                    'fullcode' => $data->fullcode,
                    'name' => $data->name,
                    'periode_id' => $data->periode_id,
                    'status' => $data->status,
                ];
            }

            return $this->successResponse($return, 'List Sumber Dana');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function uploadSumberDana(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
        ], [], [
            'file' => 'File',
        ]);

        if ($validation->fails()) {
            return $this->validationResponse($validation->errors());
        }

        DB::beginTransaction();
        try {
            $periode = 1;
            $year = 2024;

            $files = glob(storage_path('app/public/smbdn/*'));
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $file = $request->file('file');
            $path = $file->store('public/smbdn');
            $path = str_replace('public/', '', $path);
            $path = storage_path('app/public/' . $path);

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            if (
                $sheet->getCellByColumnAndRow(1, 1)->getValue() !== 'KODE SUMBER DANA' &&
                $sheet->getCellByColumnAndRow(2, 1)->getValue() !== 'NAMA SUMBER DANA'
            ) {
                return $this->errorResponse('Format Excel tidak sesuai');
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($row = 2; $row <= $highestRow; $row++) {
                $fullcode = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $code1 = null;
                $code2 = null;
                $code3 = null;
                $code4 = null;
                $code5 = null;
                $code6 = null;
                if ($fullcode !== null) {
                    $fullcode = (string)$fullcode;
                    $code1 = substr($fullcode, 0, 1);
                    $code2 = substr($fullcode, 2, 1);
                    if ($code2 === '') {
                        $code2 = null;
                    }
                    $code3 = substr($fullcode, 4, 2);
                    if ($code3 === '') {
                        $code3 = null;
                    }
                    $code4 = substr($fullcode, 7, 2);
                    if ($code4 === '') {
                        $code4 = null;
                    }
                    $code5 = substr($fullcode, 10, 2);
                    if ($code5 === '') {
                        $code5 = null;
                    }
                    $code6 = substr($fullcode, 13, 4);
                    if ($code6 === '') {
                        $code6 = null;
                    }
                }

                $uraian = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $uraian = str()->squish($uraian);

                $paguSebelumPergeseran = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                if ($paguSebelumPergeseran !== null) {
                    $paguSebelumPergeseran = str_replace('.', '', $paguSebelumPergeseran);
                    $paguSebelumPergeseran = str_replace(',', '.', $paguSebelumPergeseran);
                }
                $paguSetelahPergeseran = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                if ($paguSetelahPergeseran !== null) {
                    $paguSetelahPergeseran = str_replace('.', '', $paguSetelahPergeseran);
                    $paguSetelahPergeseran = str_replace(',', '.', $paguSetelahPergeseran);
                }
                $paguSelisih = $sheet->getCellByColumnAndRow(5, $row)->getValue();
                if ($paguSelisih !== null) {
                    $paguSelisih = str_replace('.', '', $paguSelisih);
                    $paguSelisih = str_replace(',', '.', $paguSelisih);
                }

                $data = KodeSumberDana::where('fullcode', $fullcode)->first();
                if (!$data) {
                    $data = new KodeSumberDana();
                    $data->created_by = 6;
                }
                $data->periode_id = $periode;
                $data->year = $year;
                $data->code_1 = isset($code1) ? $code1 : null;
                $data->code_2 = isset($code2) ? $code2 : null;
                $data->code_3 = isset($code3) ? $code3 : null;
                $data->code_4 = isset($code4) ? $code4 : null;
                $data->code_5 = isset($code5) ? $code5 : null;
                $data->code_6 = isset($code6) ? $code6 : null;
                $data->fullcode = $fullcode;
                $data->name = $uraian;
                $data->status = 'active';

                $parent = null;
                if ($code2 && !$code3 && !$code4 && !$code5 && !$code6) {
                    $parent = KodeSumberDana::where('code_1', $code1)->first();
                }
                if ($code2 && $code3 && !$code4 && !$code5 && !$code6) {
                    $parent = KodeSumberDana::where('code_1', $code1)->where('code_2', $code2)->first();
                }
                if ($code2 && $code3 && $code4 && !$code5 && !$code6) {
                    $parent = KodeSumberDana::where('code_1', $code1)->where('code_2', $code2)->where('code_3', $code3)->first();
                }
                if ($code2 && $code3 && $code4 && $code5 && !$code6) {
                    $parent = KodeSumberDana::where('code_1', $code1)->where('code_2', $code2)->where('code_3', $code3)->where('code_4', $code4)->first();
                }
                if ($code2 && $code3 && $code4 && $code5 && $code6) {
                    $parent = KodeSumberDana::where('code_1', $code1)->where('code_2', $code2)->where('code_3', $code3)->where('code_4', $code4)->where('code_5', $code5)->first();
                }
                $data->parent_id = $parent->id ?? null;
                $data->save();
            }
            DB::commit();
            return $this->successResponse(null, 'Data Sumber Dana Berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }

    function getUser()
    {
        $datas = User::where('role_id', 9)->get();
        return $this->successResponse($datas, 'List User');
    }
}
