<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GlobalController extends Controller
{
    use JsonReturner;
    public $isAbleToInput = true;
    public $globalMessage = 'Sedang Dalam Perbaikan!';

    function main(Request $request)
    {
        if ($request->_get == 'ref_periode') {
            $return = [];
            $datas = DB::table('ref_periode')
                ->get();
            foreach ($datas as $data) {
                $return[] = [
                    'id' => $data->id,
                    'name' => $data->name,
                    'start_date' => $data->start_date,
                    'end_date' => $data->end_date,
                    'start_year' => Carbon::parse($data->start_date)->format('Y'),
                    'end_year' => Carbon::parse($data->end_date)->format('Y'),
                ];
            }

            return $this->successResponse($return);
        }

        if ($request->_get == 'instances') {
            $return = [];
            $datas = DB::table('instances')
                ->get();
            foreach ($datas as $data) {
                $return[] = [
                    'id' => $data->id,
                    'name' => $data->name,
                    'code' => $data->code,
                    'alias' => $data->alias,
                    'logo' => asset($data->logo),
                    'description' => asset($data->description),
                    'address' => asset($data->address),
                    'facebook' => asset($data->facebook),
                    'phone' => asset($data->phone),
                    'fax' => asset($data->fax),
                    'website' => asset($data->website),
                    'facebook' => asset($data->facebook),
                    'instagram' => asset($data->instagram),
                    'youtube' => asset($data->youtube),
                ];
            }

            return $this->successResponse($return);
        }

        if ($request->_get == 'kode_rekening') {
            $q = [];
            if ($request->q) {
                $qs = $request->q;

                foreach ($qs as $key => $item) {
                    $q[] = explode('|', $item);
                }
                // return $q;
            }
            $return = [];
            if (count($q) > 0) {
                $datas = DB::table('ref_kode_rekening_complete')
                    ->when(count($q) > 0, function ($qr) use ($q) {
                        foreach ($q as $key => $item) {
                            if ($item[0] == 'where') {
                                $qr->where($item[1], $item[2], $item[3]);
                            }
                        }
                        return $qr;
                    })
                    ->orderBy('fullcode')
                    ->get();
            } else {
                $datas = DB::table('ref_kode_rekening_complete')
                    ->get();
            }
            foreach ($datas as $data) {
                $return[] = [
                    'id' => $data->id,
                    'periode_id' => $data->periode_id,
                    'year' => $data->year,
                    'code_1' => $data->code_1,
                    'code_2' => $data->code_2,
                    'code_3' => $data->code_3,
                    'code_4' => $data->code_4,
                    'code_5' => $data->code_5,
                    'code_6' => $data->code_6,
                    'fullcode' => $data->fullcode,
                    'name' => $data->name,
                ];
            }

            return $this->successResponse($return);
        }
    }
}
