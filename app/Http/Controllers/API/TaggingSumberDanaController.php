<?php

namespace App\Http\Controllers\API;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Data\TaggingSumberDana;
use App\Models\Instance;
use App\Models\Ref\Kegiatan;
use App\Models\Ref\Program;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\TagSumberDana;
use Illuminate\Support\Facades\Validator;

class TaggingSumberDanaController extends Controller
{
    use JsonReturner;

    function index(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|integer|exists:instances,id',
        ], [], [
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $instance = Instance::find($request->instance);
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan');
            }

            $datas = [];
            $arrPrograms = Program::where('instance_id', $instance->id)
                ->where('status', 'active')
                ->orderBy('fullcode')
                ->get();
            foreach ($arrPrograms as $keyPrg => $program) {
                $datas['data'][$keyPrg] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'fullcode' => $program->fullcode,
                    'kegiatan' => [],
                ];

                $arrKegiatans = Kegiatan::where('program_id', $program->id)
                    ->where('status', 'active')
                    ->orderBy('fullcode')
                    ->get();
                foreach ($arrKegiatans as $keyKgt => $kegiatan) {
                    $datas['data'][$keyPrg]['kegiatan'][$keyKgt] = [
                        'id' => $kegiatan->id,
                        'name' => $kegiatan->name,
                        'fullcode' => $kegiatan->fullcode,
                        'sub_kegiatan' => [],
                    ];

                    $arrSubKegiatans = SubKegiatan::where('kegiatan_id', $kegiatan->id)
                        ->where('status', 'active')
                        ->orderBy('fullcode')
                        ->get();
                    foreach ($arrSubKegiatans as $subKegiatan) {
                        $datas['data'][$keyPrg]['kegiatan'][$keyKgt]['sub_kegiatan'][] = [
                            'id' => $subKegiatan->id,
                            'name' => $subKegiatan->name,
                            'fullcode' => $subKegiatan->fullcode,
                        ];
                    }
                }
            }
            $datas['options'] = TagSumberDana::where('status', 'active')->get();

            DB::commit();
            return $this->successResponse($datas, 'Daftar Tag Sumber Dana berhasil diambil');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }

        return $this->successResponse([], 'Daftar Tag Sumber Dana berhasil diambil');
    }

    function detail($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|integer|exists:instances,id',
        ], [], [
            'instance' => 'Perangkat Daerah',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $instance = Instance::find($request->instance);
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan');
            }

            $data = SubKegiatan::find($id);
            if (!$data) {
                return $this->errorResponse('Data Sub Kegiatan tidak ditemukan');
            }

            $tags = TaggingSumberDana::where('sub_kegiatan_id', $data->id)
                ->where('status', 'active')
                ->get();
            $datas = [
                'sub_kegiatan_id' => $data->id,
                'tags' => [],
                'values' => [],
            ];
            foreach ($tags as $tag) {
                $datas['tags'][] = [
                    'value' => $tag->ref_tag_id,
                    'label' => $tag->RefTag->name,
                ];
                $datas['values'][] = [
                    'id' => $tag->ref_tag_id,
                    'nominal' => $tag->nominal,
                ];
            }

            DB::commit();
            return $this->successResponse($datas, 'Detail Sub Kegiatan berhasil diambil');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }

        return $this->successResponse([], 'Detail Sub Kegiatan berhasil diambil');
    }

    function save($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'required|integer|exists:instances,id',
            'tags' => 'required|array',
            'values' => 'required|array',
        ], [], [
            'instance' => 'Perangkat Daerah',
            'tags' => 'Tag Sumber Dana',
            'values' => 'Nilai',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $instance = Instance::find($request->instance);
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan');
            }

            $data = SubKegiatan::find($id);
            if (!$data) {
                return $this->errorResponse('Data Sub Kegiatan tidak ditemukan');
            }

            foreach ($request->tags as $key => $tag) {
                // Delete data if not in request
                TaggingSumberDana::where('sub_kegiatan_id', $id)
                    ->whereNotIn('ref_tag_id', collect($request->tags)->pluck('value'))
                    ->delete();

                $data = TaggingSumberDana::where('sub_kegiatan_id', $id)
                    ->where('ref_tag_id', $tag['value'])
                    ->first();
                if (!$data) {
                    $data = new TaggingSumberDana();
                    $data->sub_kegiatan_id = $id;
                    $data->ref_tag_id = $tag['value'];
                    $data->status = 'active';
                    $data->created_by = auth()->user()->id;
                } else {
                    $data->updated_by = auth()->user()->id;
                }
                $data->nominal = $request->values[$key]['nominal'];
                $data->save();
            }

            DB::commit();
            return $this->successResponse(null, 'Tag Sumber Dana berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }

        return $this->successResponse(null, 'Tag Sumber Dana berhasil disimpan');
    }
}
