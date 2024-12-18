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
            'instance' => 'required|numeric|exists:instances,id',
            'periode' => 'required|numeric|exists:ref_periode,id'
        ], [], [
            'instance' => 'Perangkat Daerah',
            'periode' => 'Periode',
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
            if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                $progs = auth()->user()->MyPermissions()->pluck('program_id');
                $progs = collect($progs)->unique()->values();

                $arrPrograms = Program::where('instance_id', $instance->id)
                    ->where('periode_id', $request->periode)
                    ->whereIn('id', $progs)
                    ->where('status', 'active')
                    ->orderBy('fullcode')
                    ->get();
            } else {
                $arrPrograms = Program::where('instance_id', $instance->id)
                    ->where('periode_id', $request->periode)
                    ->where('status', 'active')
                    ->orderBy('fullcode')
                    ->get();
            }
            foreach ($arrPrograms as $keyPrg => $program) {
                $datas['data'][$keyPrg] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'fullcode' => $program->fullcode,
                    'kegiatan' => [],
                ];

                if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                    $kegs = auth()->user()->MyPermissions()->pluck('kegiatan_id');
                    $kegs = collect($kegs)->unique()->values();

                    $arrKegiatans = Kegiatan::where('program_id', $program->id)
                        ->where('periode_id', $request->periode)
                        ->whereIn('id', $kegs)
                        ->where('status', 'active')
                        ->orderBy('fullcode')
                        ->get();
                } else {
                    $arrKegiatans = Kegiatan::where('program_id', $program->id)
                        ->where('periode_id', $request->periode)
                        ->where('status', 'active')
                        ->orderBy('fullcode')
                        ->get();
                }
                foreach ($arrKegiatans as $keyKgt => $kegiatan) {
                    $datas['data'][$keyPrg]['kegiatan'][$keyKgt] = [
                        'id' => $kegiatan->id,
                        'name' => $kegiatan->name,
                        'fullcode' => $kegiatan->fullcode,
                        'sub_kegiatan' => [],
                    ];

                    if (auth()->user()->role_id == 9 && auth()->user()->instance_type == 'staff') {
                        $subKegs = auth()->user()->MyPermissions()->pluck('sub_kegiatan_id');
                        $subKegs = collect($subKegs)->unique()->values();

                        $arrSubKegiatans = SubKegiatan::where('kegiatan_id', $kegiatan->id)
                            ->where('periode_id', $request->periode)
                            ->whereIn('id', $subKegs)
                            ->where('status', 'active')
                            ->orderBy('fullcode')
                            ->get();
                    } else {
                        $arrSubKegiatans = SubKegiatan::where('kegiatan_id', $kegiatan->id)
                            ->where('periode_id', $request->periode)
                            ->where('status', 'active')
                            ->orderBy('fullcode')
                            ->get();
                    }
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
            // 'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|integer',
        ], [], [
            'instance' => 'Perangkat Daerah',
            // 'periode' => 'Periode',
            'year' => 'Tahun',
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
                ->where('year', $request->year)
                ->get();
            $datas = [
                'sub_kegiatan_id' => $data->id,
                'year' => $request->year,
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
            'year' => 'nullable|integer',
            'tags' => 'nullable|array',
            'values' => 'nullable|array',
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

            $subKegiatan = SubKegiatan::find($id);
            if (!$subKegiatan) {
                return $this->errorResponse('Data Sub Kegiatan tidak ditemukan');
            }

            TaggingSumberDana::where('sub_kegiatan_id', $id)
                ->where('year', $request->year ?? date('Y'))
                ->update([
                    'status' => 'inactive',
                ]);

            foreach ($request->tags as $key => $tag) {
                $data = TaggingSumberDana::where('sub_kegiatan_id', $id)
                    ->where('ref_tag_id', $tag['value'])
                    ->where('year', $request->year ?? date('Y'))
                    ->first();
                if (!$data) {
                    $data = new TaggingSumberDana();
                    $data->sub_kegiatan_id = $id;
                    $data->year = $request->year ?? date('Y');
                    $data->ref_tag_id = $tag['value'];
                    $data->created_by = auth()->user()->id;
                } else {
                    $data->updated_by = auth()->user()->id;
                }
                $data->nominal = $request->values[$key]['nominal'];
                $data->status = 'active';
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

                $description = '';
                if (count($request->tags) > 0) {
                    $description = auth()->user()->fullname . ' memperbarui ' . count($request->tags) . ' tag sumber dana ke sub kegiatan ' . $subKegiatan->name;
                } else {
                    $description = auth()->user()->fullname . ' menghapus tag sumber dana dari sub kegiatan ' . $subKegiatan->name;
                }

                $newLogs[] = [
                    'action' => 'tagging-sumber-dana@create',
                    'description' => $description,
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

            DB::commit();
            return $this->successResponse(null, 'Tag Sumber Dana berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }

        return $this->successResponse(null, 'Tag Sumber Dana berhasil disimpan');
    }
}
