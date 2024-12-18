<?php

namespace App\Http\Controllers\API;

use App\Models\Caram\Tujuan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Sasaran;
use App\Models\Ref\RefTujuan;
use App\Models\Ref\RefSasaran;
use Illuminate\Support\Facades\DB;
use App\Models\Ref\IndikatorTujuan;
use App\Http\Controllers\Controller;
use App\Models\Ref\IndikatorSasaran;
use Illuminate\Support\Facades\Validator;

class TujuanSasaranController extends Controller
{
    use JsonReturner;

    function listRefTujuanSasaran(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|numeric|exists:instances,id',
            'periode' => 'required|numeric|exists:ref_periode,id',
            'type' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            if ($request->type === 'tujuan') {
                $datas = RefTujuan::search($request->search)
                    ->where('periode_id', $request->periode)
                    ->where('instance_id', $request->instance)
                    ->with('Instance', function ($query) use ($request) {
                        if ($request->instance) {
                            $query->select([
                                'id',
                                'name',
                            ]);
                        }
                    })
                    ->select([
                        'id',
                        'name',
                        'status',
                        'instance_id',
                    ])
                    ->get();
            }
            if ($request->type === 'sasaran') {
                $datas = RefSasaran::search($request->search)
                    ->where('periode_id', $request->periode)
                    ->where('instance_id', $request->instance)
                    ->with('Instance', function ($query) use ($request) {
                        if ($request->instance) {
                            $query->select([
                                'id',
                                'name',
                            ]);
                        }
                    })
                    ->select([
                        'id',
                        'name',
                        'status',
                        'instance_id',
                    ])
                    ->get();
            }
            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function detailRefTUjuanSasaran($id, Request $request)
    {
        if ($request->type === 'tujuan') {
            $data = RefTujuan::where('id', $id)->select('id', 'name', 'status', 'instance_id', 'periode_id')->first();
            return $this->successResponse($data);
        }
        if ($request->type === 'sasaran') {
            $data = RefSasaran::where('id', $id)->select('id', 'name', 'status', 'instance_id', 'periode_id')->first();
            return $this->successResponse($data);
        }

        return $this->errorResponse('Type not found');
    }

    function saveRefTujuanSasaran(Request $request)
    {
        $validate = Validator::make($request->all(), [
            // 'id' => 'nullable|numeric|exists:ref_tujuan,id',
            'name' => 'required|string',
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:instances,id',
            'type' => 'required|string',
            'inputType' => 'required|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            if ($request->inputType === 'create') {
                if ($request->type === 'tujuan') {
                    $data = new RefTujuan();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->created_by = auth()->user()->id;
                    $data->save();
                }
                if ($request->type === 'sasaran') {
                    $data = new RefSasaran();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->created_by = auth()->user()->id;
                    $data->save();
                }
            }

            if ($request->inputType === 'edit') {
                if ($request->type === 'tujuan') {
                    $data = RefTujuan::where('id', $request->id)->first();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->updated_by = auth()->user()->id;
                    $data->save();
                }
                if ($request->type === 'sasaran') {
                    $data = RefSasaran::where('id', $request->id)->first();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->updated_by = auth()->user()->id;
                    $data->save();
                }
            }

            DB::commit();
            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function deleteRefTujuanSasaran($id, Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->type === 'tujuan') {
                $data = RefTujuan::where('id', $id)->first();
                $data->deleted_by = auth()->user()->id;
                $data->save();
                $data->delete();
            }

            if ($request->type === 'sasaran') {
                $data = RefSasaran::where('id', $id)->first();
                $data->deleted_by = auth()->user()->id;
                $data->save();
                $data->delete();
            }

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function listRefIndikatorTujuanSasaran(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|numeric|exists:instances,id',
            'periode' => 'nullable|numeric|exists:ref_periode,id',
            'type' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            if ($request->type === 'tujuan') {
                $datas = IndikatorTujuan::search($request->search)
                    ->where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->with('Instance', function ($query) use ($request) {
                        if ($request->instance) {
                            $query->select([
                                'id',
                                'name',
                            ]);
                        }
                    })
                    ->select([
                        'id',
                        'name',
                        'status',
                        'instance_id',
                    ])
                    ->get();
            }
            if ($request->type === 'sasaran') {
                $datas = IndikatorSasaran::search($request->search)
                    ->where('instance_id', $request->instance)
                    ->where('periode_id', $request->periode)
                    ->with('Instance', function ($query) use ($request) {
                        if ($request->instance) {
                            $query->select([
                                'id',
                                'name',
                            ]);
                        }
                    })
                    ->select([
                        'id',
                        'name',
                        'status',
                        'instance_id',
                    ])
                    ->get();
            }
            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function detailRefIndikatorTujuanSasaran($id, Request $request)
    {
        if ($request->type === 'tujuan') {
            $data = IndikatorTujuan::where('id', $id)->select('id', 'name', 'status', 'instance_id', 'periode_id')->first();
            return $this->successResponse($data);
        }
        if ($request->type === 'sasaran') {
            $data = IndikatorSasaran::where('id', $id)->select('id', 'name', 'status', 'instance_id', 'periode_id')->first();
            return $this->successResponse($data);
        }

        return $this->errorResponse('Type not found');
    }

    function saveRefIndikatorTujuanSasaran(Request $request)
    {
        $validate = Validator::make($request->all(), [
            // 'id' => 'nullable|numeric|exists:ref_indikator_tujuan,id',
            'name' => 'required|string',
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:ref_periode,id',
            'type' => 'required|string',
            'inputType' => 'required|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            if ($request->inputType === 'create') {
                if ($request->type === 'tujuan') {
                    $data = new IndikatorTujuan();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->created_by = auth()->user()->id;
                    $data->save();
                }
                if ($request->type === 'sasaran') {
                    $data = new IndikatorSasaran();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->created_by = auth()->user()->id;
                    $data->save();
                }
            }

            if ($request->inputType === 'edit') {
                if ($request->type === 'tujuan') {
                    $data = IndikatorTujuan::where('id', $request->id)->first();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->updated_by = auth()->user()->id;
                    $data->save();
                }
                if ($request->type === 'sasaran') {
                    $data = IndikatorSasaran::where('id', $request->id)->first();
                    $data->name = $request->name;
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode_id;
                    $data->status = 'active';
                    $data->updated_by = auth()->user()->id;
                    $data->save();
                }
            }

            DB::commit();
            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function deleteRefIndikatorTujuanSasaran($id, Request $request)
    {
        if ($request->type === 'tujuan') {
            $data = IndikatorTujuan::where('id', $id)->first();
            $data->status = 'inactive';
            $data->deleted_by = auth()->user()->id;
            $data->save();
            return $this->successResponse(null, 'Data berhasil dihapus');
        }
        if ($request->type === 'sasaran') {
            $data = IndikatorSasaran::where('id', $id)->first();
            $data->status = 'inactive';
            $data->deleted_by = auth()->user()->id;
            $data->save();
            return $this->successResponse(null, 'Data berhasil dihapus');
        }

        return $this->errorResponse('Type not found');
    }

    function getMasterTujuan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $return = [];
        if ($request->instance === 'null' || $request->instance === '') {
            $request->instance = null;
        }

        if (!$request->instance) {
            $datas = Tujuan::whereNull('instance_id')
                ->where('periode_id', $request->periode)
                ->get();
            foreach ($datas as $data) {
                $returnIndikatorTujuan = [];
                $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                    ->where('tujuan_id', $data->id)
                    ->get();
                foreach ($arrIndikatorTujuan as $indTujuan) {
                    $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)->first();
                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                    ];
                }

                $returnSasaran = [];
                $arrSasaran = Sasaran::where('tujuan_id', $data->id)
                    ->where('periode_id', $request->periode)
                    ->get();
                foreach ($arrSasaran as $sasaran) {
                    $returnIndikatorSasaran = [];
                    $arrIndikatorTujuan = DB::table('pivot_master_sasaran_to_ref_sasaran')
                        ->where('sasaran_id', $sasaran->id)
                        ->get();
                    foreach ($arrIndikatorTujuan as $indSasaran) {
                        $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();
                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                        ];
                    }
                    $returnSasaran[] = [
                        'id' => $sasaran->id,
                        'ref_sasaran_id' => $sasaran->ref_sasaran_id,
                        'sasaran' => $sasaran->RefSasaran->name,
                        'indikator_sasaran' => $returnIndikatorSasaran,
                    ];
                }
                $return[] = [
                    'id' => $data->id,
                    'ref_tujuan_id' => $data->ref_tujuan_id,
                    'tujuan' => $data->RefTujuan->name,
                    'indikator_tujuan' => $returnIndikatorTujuan,
                    'sasaran' => $returnSasaran,
                ];
            }
        }

        if ($request->instance) {
            $datas = Tujuan::where('instance_id', $request->instance)
                ->where('periode_id', $request->periode)
                ->get();
            // return $datas;
            $arrTujuanKabupaten = Tujuan::whereNull('instance_id')
                ->whereIn('id', $datas->pluck('parent_id'))
                ->get();
            foreach ($arrTujuanKabupaten as $TujuanKabupaten) {
                $returnIndikatorTujuan = [];
                $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                    ->where('tujuan_id', $TujuanKabupaten->id)
                    ->get();

                foreach ($arrIndikatorTujuan as $indTujuan) {
                    $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)->first();
                    $returnIndikatorTujuan[] = [
                        'id_ref' => $refIndikatorTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                    ];
                }

                $returnSasaran = [];
                $arrSasaran = Sasaran::where('tujuan_id', $TujuanKabupaten->id)
                    ->where('periode_id', $request->periode)
                    ->get();
                foreach ($arrSasaran as $sasaran) {
                    $returnIndikatorSasaran = [];
                    $arrIndikatorTujuan = DB::table('pivot_master_sasaran_to_ref_sasaran')
                        ->where('sasaran_id', $sasaran->id)
                        ->get();
                    foreach ($arrIndikatorTujuan as $indSasaran) {
                        $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();
                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                        ];
                    }
                    $returnSasaran[] = [
                        'id' => $sasaran->id,
                        'ref_sasaran_id' => $sasaran->ref_sasaran_id,
                        'sasaran' => $sasaran->RefSasaran->name,
                        'indikator_sasaran' => $returnIndikatorSasaran,
                    ];
                }

                $returnOPD = [];
                $arrOPD = Tujuan::where('parent_id', $TujuanKabupaten->id)
                    ->where('instance_id', $request->instance)
                    ->get();
                foreach ($arrOPD as $OPD) {
                    $returnIndikatorOPD = [];
                    $arrIndikatorOPD = DB::table('pivot_master_tujuan_to_ref_tujuan')
                        ->where('tujuan_id', $OPD->id)
                        ->get();
                    foreach ($arrIndikatorOPD as $indOPD) {
                        $refIndikatorOPD = IndikatorTujuan::where('id', $indOPD->ref_id)->first();
                        $returnIndikatorOPD[] = [
                            'id_ref' => $refIndikatorOPD->ref_id,
                            'name' => $refIndikatorOPD->name,
                            'rumus' => $indOPD->rumus ?? null,
                        ];
                    }

                    $returnSasaranOPD = [];
                    $arrSasaranOPD = Sasaran::where('tujuan_id', $OPD->id)->get();
                    foreach ($arrSasaranOPD as $sasaranOPD) {
                        $returnIndikatorSasaranOPD = [];
                        $arrIndikatorOPD = DB::table('pivot_master_sasaran_to_ref_sasaran')
                            ->where('sasaran_id', $sasaranOPD->id)
                            ->get();
                        foreach ($arrIndikatorOPD as $indSasaranOPD) {
                            $refIndikatorSasaranOPD = IndikatorSasaran::where('id', $indSasaranOPD->ref_id)->first();
                            $returnIndikatorSasaranOPD[] = [
                                'id_ref' => $indSasaranOPD->ref_id,
                                'name' => $refIndikatorSasaranOPD->name ?? null,
                                'rumus' => $indSasaranOPD->rumus ?? null,
                            ];
                        }
                        $returnSasaranOPD[] = [
                            'id' => $sasaranOPD->id,
                            'ref_sasaran_id' => $sasaranOPD->ref_sasaran_id,
                            'sasaran' => $sasaranOPD->RefSasaran->name,
                            'indikator_sasaran' => $returnIndikatorSasaranOPD,
                        ];
                    }
                    $returnOPD[] = [
                        'id' => $OPD->id,
                        'ref_tujuan_id' => $OPD->ref_tujuan_id,
                        'tujuan' => $OPD->RefTujuan->name,
                        'indikator_tujuan' => $returnIndikatorOPD,
                        'sasaran' => $returnSasaranOPD,
                    ];
                }
                $return[] = [
                    'id' => $TujuanKabupaten->id,
                    'ref_tujuan_id' => $TujuanKabupaten->ref_tujuan_id,
                    'tujuan' => $TujuanKabupaten->RefTujuan->name,
                    'indikator_tujuan' => $returnIndikatorTujuan,
                    'sasaran' => $returnSasaran,
                    'opd' => $returnOPD,
                ];
            }
        }

        return $this->successResponse($return);
    }

    function getDetailMasterTujuan($id, Request $request)
    {
        $data = Tujuan::where('id', $id)->first();
        $indikatorTujuanIds = DB::table('pivot_master_tujuan_to_ref_tujuan')
            ->where('tujuan_id', $data->id)
            ->get();
        $returnRumus = [];
        foreach ($indikatorTujuanIds as $indikator) {
            $label = IndikatorTujuan::where('id', $indikator->ref_id)->first();
            $returnRumus[] = [
                'id' => $indikator->ref_id,
                'label' => $label->name, // 'label' => 'Indikator 1
                'rumus' => $indikator->rumus,
            ];
        }

        $return = [
            'id' => $data->id,
            'inputType' => 'edit',
            'parent_id' => $data->parent_id, // 'parent_id' => 'id tujuan',
            'parent_ref_tujuan_id' => $data->Parent->ref_tujuan_id ?? null, // 'parent_ref_tujuan_id' => 'id ref tujuan',
            'ref_tujuan_id' => $data->ref_tujuan_id,
            'instance_id' => $data->instance_id,
            'indikator_tujuan_ids' => $indikatorTujuanIds->pluck('ref_id'),
            'rumus_tujuan' => $returnRumus,
        ];

        return $this->successResponse($return);
    }

    function saveMasterTujuan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'inputType' => 'required|string',
            'instance_id' => 'nullable|exists:instances,id',
            'periode' => 'required|exists:ref_periode,id',
            'ref_tujuan_id' => 'required|exists:ref_tujuan,id',
            'indikator_tujuan_ids' => 'required|array',
            'indikator_tujuan_ids.*' => 'required|exists:ref_indikator_tujuan,id',
            'rumus_tujuan' => 'nullable|array',
            'rumus_tujuan.*.rumus' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            // Tujuan Kabupaten
            if ($request->instance_id === null) {
                if ($request->inputType === 'create') {
                    $checkExistRefTujuanId = Tujuan::where('ref_tujuan_id', $request->ref_tujuan_id)->first();
                    if ($checkExistRefTujuanId) {
                        return $this->errorResponse('Data sudah ada');
                    }
                    $data = new Tujuan();
                    $data->instance_id = null;
                    $data->periode_id = $request->periode;
                    $data->ref_tujuan_id = $request->ref_tujuan_id;
                    $data->created_by = auth()->user()->id;
                    $data->status = 'active';
                    $data->save();

                    if (count($request->rumus_tujuan) > 0) {
                        foreach ($request->rumus_tujuan as $input) {
                            DB::table('pivot_master_tujuan_to_ref_tujuan')->updateOrInsert([
                                'tujuan_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
                if ($request->inputType === 'edit') {
                    $data = Tujuan::where('id', $request->id)->first();
                    $data->instance_id = null;
                    $data->periode_id = $request->periode;
                    $data->ref_tujuan_id = $request->ref_tujuan_id;
                    $data->updated_by = auth()->user()->id;
                    $data->save();

                    if (count($request->rumus_tujuan) > 0) {
                        foreach ($request->rumus_tujuan as $input) {
                            DB::table('pivot_master_tujuan_to_ref_tujuan')->updateOrInsert([
                                'tujuan_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
            }

            // Tujuan OPD
            if ($request->instance_id) {
                if ($request->inputType === 'create') {
                    // $checkExistRefTujuanId = Tujuan::where('ref_tujuan_id', $request->ref_tujuan_id)->first();
                    // if ($checkExistRefTujuanId) {
                    //     return $this->errorResponse('Data sudah ada');
                    // }
                    $parent = Tujuan::whereNull('instance_id')
                        ->where('ref_tujuan_id', $request->parent_ref_tujuan_id)
                        ->first();
                    if (!$parent) {
                        return $this->errorResponse('Tujuan Kabupaten belum ada');
                    }
                    $data = new Tujuan();
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode;
                    $data->parent_id = $parent->id;
                    $data->ref_tujuan_id = $request->ref_tujuan_id;
                    $data->created_by = auth()->user()->id;
                    $data->status = 'active';
                    $data->save();

                    if (count($request->rumus_tujuan) > 0) {
                        foreach ($request->rumus_tujuan as $input) {
                            DB::table('pivot_master_tujuan_to_ref_tujuan')->updateOrInsert([
                                'tujuan_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
                if ($request->inputType === 'edit') {
                    // $checkExistRefTujuanId = Tujuan::whereNotIn('id', [$request->id])
                    //     ->where('ref_tujuan_id', $request->ref_tujuan_id)
                    //     ->first();
                    // if ($checkExistRefTujuanId) {
                    //     return $this->errorResponse('Data sudah ada');
                    // }
                    $parent = Tujuan::whereNull('instance_id')
                        ->where('ref_tujuan_id', $request->parent_ref_tujuan_id)
                        ->first();
                    if (!$parent) {
                        return $this->errorResponse('Tujuan Kabupaten belum ada');
                    }
                    $data = Tujuan::where('id', $request->id)->first();
                    $data->instance_id = $request->instance_id;
                    $data->periode_id = $request->periode;
                    $data->parent_id = $parent->id;
                    $data->ref_tujuan_id = $request->ref_tujuan_id;
                    $data->updated_by = auth()->user()->id;
                    $data->save();

                    if (count($request->rumus_tujuan) > 0) {
                        foreach ($request->rumus_tujuan as $input) {
                            DB::table('pivot_master_tujuan_to_ref_tujuan')->updateOrInsert([
                                'tujuan_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function deleteMasterTujuan($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Tujuan::where('id', $id)->first();
            $data->deleted_by = auth()->user()->id;
            $data->save();
            $data->delete();
            DB::table('pivot_master_tujuan_to_ref_tujuan')->where('tujuan_id', $id)->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function getDetailMasterSasaran($id, Request $request)
    {
        $data = Sasaran::where('id', $id)->first();
        $indikatorSasaranIds = DB::table('pivot_master_sasaran_to_ref_sasaran')
            ->where('sasaran_id', $data->id)
            ->get();
        $returnRumus = [];
        foreach ($indikatorSasaranIds as $indikator) {
            $label = IndikatorSasaran::where('id', $indikator->ref_id)->first();
            $returnRumus[] = [
                'id' => $indikator->ref_id,
                'label' => $label->name, // 'label' => 'Indikator 1
                'rumus' => $indikator->rumus,
            ];
        }

        $return = [
            'id' => $data->id,
            'inputType' => 'edit',
            'ref_sasaran_id' => $data->ref_sasaran_id,
            'instance_id' => $data->instance_id,
            'tujuan_id' => $data->tujuan_id,
            'indikator_sasaran_ids' => $indikatorSasaranIds->pluck('ref_id'),
            'rumus_sasaran' => $returnRumus,
        ];

        return $this->successResponse($return);
    }

    function saveMasterSasaran(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'inputType' => 'required|string',
            'instance_id' => 'nullable|exists:instances,id',
            'periode' => 'required|exists:ref_periode,id',
            'tujuan_id' => 'required|exists:master_tujuan,id',
            'ref_sasaran_id' => 'required|exists:ref_sasaran,id',
            'indikator_sasaran_ids' => 'required|array',
            'indikator_sasaran_ids.*' => 'required|exists:ref_indikator_sasaran,id',
            'rumus_sasaran' => 'nullable|array',
            'rumus_sasaran.*.rumus' => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            // Sasaran Kabupaten
            if ($request->instance_id === null) {
                if ($request->inputType === 'create') {
                    $checkExistRefSasaranId = Sasaran::where('ref_sasaran_id', $request->ref_sasaran_id)->first();
                    if ($checkExistRefSasaranId) {
                        return $this->errorResponse('Data sudah ada');
                    }
                    $data = new Sasaran();
                    $data->instance_id = null;
                    $data->periode_id = $request->periode;
                    $data->tujuan_id = $request->tujuan_id;
                    $data->ref_sasaran_id = $request->ref_sasaran_id;
                    $data->created_by = auth()->user()->id;
                    $data->status = 'active';
                    $data->save();

                    if (count($request->rumus_sasaran) > 0) {
                        foreach ($request->rumus_sasaran as $input) {
                            DB::table('pivot_master_sasaran_to_ref_sasaran')->updateOrInsert([
                                'sasaran_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
                if ($request->inputType === 'edit') {
                    $data = Sasaran::where('id', $request->id)->first();
                    $data->instance_id = null;
                    $data->periode_id = $request->periode;
                    $data->tujuan_id = $request->tujuan_id;
                    $data->ref_sasaran_id = $request->ref_sasaran_id;
                    $data->updated_by = auth()->user()->id;
                    $data->save();

                    if (count($request->rumus_sasaran) > 0) {
                        foreach ($request->rumus_sasaran as $input) {
                            DB::table('pivot_master_sasaran_to_ref_sasaran')->updateOrInsert([
                                'sasaran_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
            }

            // Sasaran OPD
            if ($request->instance_id) {
                if ($request->inputType === 'create') {
                    $checkExistRefSasaranId = Sasaran::where('ref_sasaran_id', $request->ref_sasaran_id)->first();
                    if ($checkExistRefSasaranId) {
                        return $this->errorResponse('Data sudah ada');
                    }
                    $data = new Sasaran();
                    $data->instance_id = $request->instance;
                    $data->periode_id = $request->periode;
                    $data->tujuan_id = $request->tujuan_id;
                    $data->ref_sasaran_id = $request->ref_sasaran_id;
                    $data->created_by = auth()->user()->id;
                    $data->status = 'active';
                    $data->save();

                    if (count($request->rumus_sasaran) > 0) {
                        foreach ($request->rumus_sasaran as $input) {
                            DB::table('pivot_master_sasaran_to_ref_sasaran')->updateOrInsert([
                                'sasaran_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
                if ($request->inputType === 'edit') {
                    $data = Sasaran::where('id', $request->id)->first();
                    $data->instance_id = $request->instance;
                    $data->periode_id = $request->periode;
                    $data->tujuan_id = $request->tujuan_id;
                    $data->ref_sasaran_id = $request->ref_sasaran_id;
                    $data->updated_by = auth()->user()->id;
                    $data->save();

                    if (count($request->rumus_sasaran) > 0) {
                        foreach ($request->rumus_sasaran as $input) {
                            DB::table('pivot_master_sasaran_to_ref_sasaran')->updateOrInsert([
                                'sasaran_id' => $data->id,
                                'ref_id' => $input['id'],
                            ], [
                                'rumus' => $input['rumus'],
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return $this->successResponse(null, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function deleteMasterSasaran($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Sasaran::where('id', $id)->first();
            $data->deleted_by = auth()->user()->id;
            $data->save();
            $data->delete();
            DB::table('pivot_master_sasaran_to_ref_sasaran')->where('sasaran_id', $id)->delete();

            DB::commit();
            return $this->successResponse(null, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }
}
