<?php

namespace App\Http\Controllers\API;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Ref\TagSumberDana;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReferencesController extends Controller
{
    use JsonReturner;

    function listTagSumberDana(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'search' => 'nullable|string|max:100',
        ], [], [
            'search' => 'Pencarian',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $search = $request->search;
            $data = TagSumberDana::search($search)
                ->oldest()
                ->paginate(10);

            DB::commit();
            return $this->successResponse($data, 'Daftar Tag Sumber Dana berhasil diambil');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function saveTagSumberDana(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:ref_tag_sumber_dana,id',
        ], [], [
            'name' => 'Nama Tag Sumber Dana',
            'description' => 'Deskripsi Tag Sumber Dana',
            'parent_id' => 'Tag Sumber Dana Induk',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {
            if ($request->inputType == 'create') {
                $data = new TagSumberDana();
                $data->name = $request->name;
                $data->description = $request->description;
                // $data->parent_id = $request->parent_id;
                $data->created_by = auth()->user()->id;
                $data->status = 'active'; // 'active' or 'inactive
                $data->save();
            } elseif ($request->inputType == 'edit') {
                $data = TagSumberDana::find($request->id);
                $data->name = $request->name;
                $data->description = $request->description;
                // $data->parent_id = $request->parent_id;
                $data->save();
            }

            DB::commit();
            return $this->successResponse(null, 'Tag Sumber Dana berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }

    function deleteTagSumberDana($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $data = TagSumberDana::find($id);
            if (!$data) {
                return $this->errorResponse('Data Tag Sumber Dana tidak ditemukan');
            }
            $data->delete();

            DB::commit();
            return $this->successResponse(null, 'Tag Sumber Dana berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine() . ' - ' . $e->getFile());
        }
    }
}
