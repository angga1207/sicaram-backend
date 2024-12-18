<?php

namespace App\Http\Controllers\API;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Data\PohonKinerja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PohonKinerjaController extends Controller
{
    use JsonReturner;

    function index(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:ref_periode,id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $return = [];
        $datas = PohonKinerja::search($request->search)
            ->where('instance_id', $request->instance_id)
            ->where('periode_id', $request->periode_id)
            ->get();
        foreach ($datas as $data) {
            $return[] = [
                'id' => $data->id,
                'name' => $data->name,
                'instance_id' => $data->instance_id,
                'instance_name' => $data->Instance->name ?? '',
                'instance_short_name' => $data->Instance->alias ?? '',
                'description' => $data->description,
                'file' => asset($data->file),
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
                'createdBy' => $data->CreatedBy->fullname ?? '',
                'updatedBy' => $data->UpdatedBy->fullname ?? '',
            ];
        }

        return $this->successResponse($return);
    }

    function save($id = null, Request $request)
    {
        if ($request->type == 'create') {
            $validate = Validator::make($request->all(), [
                'instance_id' => 'nullable|numeric|exists:instances,id',
                'periode_id' => 'required|numeric|exists:ref_periode,id',
                'name' => 'required|string',
                'filePath' => 'required|file|mimes:pdf|max:20000',
            ], [], [
                'instance_id' => 'Perangkat Daerah',
                'periode_id' => 'Periode',
                'name' => 'Judul Pohon Kinerja',
                'filePath' => 'Berkas',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $data = new PohonKinerja();
                $data->instance_id = $request->instance_id;
                $data->periode_id = $request->periode_id;
                $data->name = $request->name;
                $data->description = $request->description;
                $data->status = 'active';
                $data->created_by = auth()->id();

                if ($request->filePath) {
                    $fileName = time();
                    $upload = $request->filePath->storeAs('pohon-kinerja', $fileName . '.' . $request->filePath->extension(), 'public');
                    $data->file = 'storage/' . $upload;
                }

                $data->save();
                DB::commit();

                return $this->successResponse($data, 'Pohon Kinerja berhasil dibuat!');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse([
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
            }
        }

        if ($request->type == 'edit') {
            $validate = Validator::make($request->all(), [
                'instance_id' => 'nullable|numeric|exists:instances,id',
                'periode_id' => 'nullable|numeric|exists:ref_periode,id',
                'name' => 'required|string',
                'filePath' => 'nullable|file|mimes:pdf|max:20000',
            ], [], [
                'instance_id' => 'Perangkat Daerah',
                'periode_id' => 'Periode',
                'name' => 'Judul Pohon Kinerja',
                'filePath' => 'Berkas',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $data = PohonKinerja::find($request->id);
                $data->instance_id = $request->instance_id;
                $data->name = $request->name;
                $data->description = $request->description;
                $data->updated_by = auth()->id();

                if ($request->filePath) {
                    $fileName = time();
                    $upload = $request->filePath->storeAs('pohon-kinerja', $fileName . '.' . $request->filePath->extension(), 'public');
                    $data->file = 'storage/' . $upload;
                }

                $data->save();
                DB::commit();

                return $this->successResponse($data, 'Pohon Kinerja berhasil diperbarui!');
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

    function delete($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:ref_periode,id',
            'id' => 'required|numeric|exists:data_pohon_kinerja',
        ], [], [
            'instance_id' => 'Perangkat Daerah',
            'periode_id' => 'Periode',
            'id' => 'Data',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = PohonKinerja::find($id);
            $data->deleted_by = auth()->id();
            $data->save();

            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Pohon Kinerja berhasil dihapus!');
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
