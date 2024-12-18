<?php

namespace App\Http\Controllers\API\Accountancy;

use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class HutangBelanjaController extends Controller
{
    use JsonReturner;

    function getIndex(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance' => 'nullable|exists:instances,id',
            'year' => 'nullable|integer',
            'periode' => 'required|exists:ref_periode,id'
        ], [], [
            'instance' => 'Instance ID',
            'periode' => 'Periode'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        try {
            $datas = [];
            $arrData = DB::table('acc_hutang_belanja')
                ->when($request->instance, function ($query) use ($request) {
                    return $query->where('instance_id', $request->instance);
                })
                ->when($request->year, function ($query) use ($request) {
                    return $query->where('year', $request->year);
                })
                ->get();


            return $this->successResponse($datas);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
