<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use Carbon\CarbonPeriod;
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
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RenjaKegiatan;
use App\Models\Caram\RPJMDAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Caram\RPJMDIndikator;
use App\Models\Caram\RenstraKegiatan;
use App\Models\Ref\IndikatorKegiatan;
use App\Models\Caram\RenjaSubKegiatan;
use App\Models\Caram\RenstraSubKegiatan;
use App\Models\Ref\IndikatorSubKegiatan;
use Illuminate\Support\Facades\Validator;

class MasterCaramController extends Controller
{
    use JsonReturner;

    function listRefUrusan(Request $request)
    {
        try {
            $datas = Urusan::search($request->search)
                ->where('periode_id', $request->periode ?? null)
                ->get();
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
                        'created_by' => $urusan->created_by,
                        'updated_by' => $urusan->updated_by,
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
                        'created_by' => $bidang->created_by,
                        'updated_by' => $bidang->updated_by,
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
                    $programs = Program::search($request->search)
                        ->where('instance_id', $request->instance)
                        ->where('periode_id', $request->periode ?? null)
                        ->where('urusan_id', $urusan->id)
                        ->where('bidang_id', $bidang->id)
                        ->get();
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
                            'created_by' => $bidang->created_by,
                            'updated_by' => $bidang->updated_by,
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
                            'created_by' => $program->created_by,
                            'updated_by' => $program->updated_by,
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
                    $programs = Program::where('instance_id', $request->instance)
                        ->where('periode_id', $request->periode ?? null)
                        ->where('urusan_id', $urusan->id)
                        ->where('bidang_id', $bidang->id)
                        ->get();
                    if (count($programs) > 0) {
                        foreach ($programs as $program) {
                            $kegiatans = Kegiatan::search($request->search)
                                ->where('instance_id', $request->instance)
                                ->where('periode_id', $request->periode ?? null)
                                ->where('urusan_id', $urusan->id)
                                ->where('bidang_id', $bidang->id)
                                ->where('program_id', $program->id)
                                ->get();
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
                                    'created_by' => $program->created_by,
                                    'updated_by' => $program->updated_by,
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
                                    'created_by' => $kegiatan->created_by,
                                    'updated_by' => $kegiatan->updated_by,
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
                ->get();
            foreach ($urusans as $urusan) {
                $bidangs = Bidang::where('periode_id', $request->periode ?? null)
                    ->where('urusan_id', $urusan->id)
                    ->get();

                foreach ($bidangs as $bidang) {
                    $programs = Program::where('instance_id', $request->instance)
                        ->where('periode_id', $request->periode ?? null)
                        ->where('urusan_id', $urusan->id)
                        ->where('bidang_id', $bidang->id)
                        ->get();
                    if (count($programs) > 0) {
                        foreach ($programs as $program) {
                            $kegiatans = Kegiatan::where('instance_id', $request->instance)
                                ->where('periode_id', $request->periode ?? null)
                                ->where('urusan_id', $urusan->id)
                                ->where('bidang_id', $bidang->id)
                                ->where('program_id', $program->id)
                                ->get();
                            if (count($kegiatans) > 0) {
                                foreach ($kegiatans as $kegiatan) {
                                    $subkegiatans = SubKegiatan::search($request->search)
                                        ->where('instance_id', $request->instance)
                                        ->where('periode_id', $request->periode ?? null)
                                        ->where('urusan_id', $urusan->id)
                                        ->where('bidang_id', $bidang->id)
                                        ->where('program_id', $program->id)
                                        ->where('kegiatan_id', $kegiatan->id)
                                        ->get();
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
                                            'created_by' => $kegiatan->created_by,
                                            'updated_by' => $kegiatan->updated_by,
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
                                            'created_by' => $subkegiatan->created_by,
                                            'updated_by' => $subkegiatan->updated_by,
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
            $data->fullcode = $kegiatan->fullcode . '.' . $request->code;
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
            return $this->errorResponse($th->getMessage());
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
                        'created_by' => $value->created_by,
                        'updated_by' => $value->updated_by,
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
                $renstra->save();
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
                $anggaranOperasi  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_operasi');
                $anggaranTransfer = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_transfer');
                $anggaranTidakTerduga  = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('anggaran_tidak_terduga');
                $totalAnggaran = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->sum('total_anggaran');
                $renstra->total_anggaran = $totalAnggaran;
                $renstra->percent_anggaran = 100;

                $averagePercentKinerja = RenstraKegiatan::where('renstra_id', $renstra->id)
                    ->where('program_id', $program->id)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->get()->avg('percent_kinerja');
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
                    'created_by' => $renstra->created_by,
                    'updated_by' => $renstra->updated_by,
                ];

                $kegiatans = Kegiatan::where('program_id', $program->id)
                    ->where('status', 'active')
                    ->get();
                foreach ($kegiatans as $kegiatan) {
                    $renstraKegiatan = RenstraKegiatan::where('renstra_id', $renstra->id)
                        ->where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('year', $year)
                        ->where('status', 'active')
                        ->first();

                    if (!$renstraKegiatan) {
                        $renstraKegiatan = new RenstraSubKegiatan();
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
                        'created_at' => $renstraKegiatan->created_at,
                        'updated_at' => $renstraKegiatan->updated_at,
                    ];

                    $subKegiatans = SubKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('status', 'active')
                        ->get();
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
                            $renstraSubKegiatan->save();
                        }

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
                            'created_at' => $renstraSubKegiatan->created_at ?? null,
                            'updated_at' => $renstraSubKegiatan->updated_at ?? null,
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
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
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
            } else {
                $type = 'return';
                $renstra->status = $request->status;
                $renstra->notes_verificator = $request->message;
                $renstra->save();
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
                    'created_by' => $renja->created_by,
                    'updated_by' => $renja->updated_by,
                ];

                $kegiatans = Kegiatan::where('program_id', $program->id)
                    ->where('status', 'active')
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
                        'created_by' => $renjaKegiatan->created_by,
                        'updated_by' => $renjaKegiatan->updated_by,
                        'created_at' => $renjaKegiatan->created_at,
                        'updated_at' => $renjaKegiatan->updated_at,
                    ];

                    $subKegiatans = SubKegiatan::where('program_id', $program->id)
                        ->where('kegiatan_id', $kegiatan->id)
                        ->where('status', 'active')
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
                            'created_by' => $renjaSubKegiatan->created_by ?? null,
                            'updated_by' => $renjaSubKegiatan->updated_by ?? null,
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
        // return $request->type;
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

                $renja = Renja::find($data->renja_id);
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

                $data->save();

                $renja = Renja::find($data->renja_id);
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
            } else {
                $type = 'return';
                $renja->status = $request->status;
                $renja->notes_verificator = $request->message;
                $renja->save();
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
            return $this->successResponse($note, 'Verifikasi Renja Berhasil dikirim');
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
        }
    }
}
