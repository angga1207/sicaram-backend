<?php

namespace App\Http\Controllers\API;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\KodeRekeningImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    use JsonReturner;

    function importKodeRekening(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ], [], [
            'file' => 'File'
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $file->move('uploads', $fileName);

            $path = public_path('uploads/' . $fileName);
            $data = Excel::import(new KodeRekeningImport, $path);
            DB::commit();
            return $this->successResponse('Data berhasil diimport');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
