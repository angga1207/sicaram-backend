<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Data\TargetKinerja;
use App\Http\Controllers\Controller;
use App\Models\Data\Realisasi;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use JsonReturner;

    function chartRealisasi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->view == 1) {
            $rangeMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        }

        if ($request->view == 2) {
            $rangeMonths = [1, 2, 3];
        }

        if ($request->view == 3) {
            $rangeMonths = [4, 5, 6];
        }

        if ($request->view == 4) {
            $rangeMonths = [7, 8, 9];
        }

        if ($request->view == 5) {
            $rangeMonths = [10, 11, 12];
        }

        if ($request->view > 5 || $request->view < 1) {
            return $this->errorResponse('View tidak valid');
        }

        $dataTarget = [];
        $dataRealisasi = [];

        foreach ($rangeMonths as $month) {
            $sumTarget = TargetKinerja::where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->where('month', $month)
                // ->where('status', 'verified')
                ->where('is_detail', true)
                ->sum('pagu_sipd');
            $dataTarget[] = [
                'month' => $month,
                'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                'target' => $sumTarget ?? 0,
            ];

            $sumRealisasi = Realisasi::where('periode_id', $request->periode)
                ->where('year', $request->year)
                ->where('month', $month)
                // ->where('status', 'verified')
                ->sum('anggaran');
            $dataRealisasi[] = [
                'month' => $month,
                'month_name' => Carbon::createFromDate($request->year, $month)->translatedFormat('F'),
                'month_short' => Carbon::createFromDate($request->year, $month)->translatedFormat('M'),
                'realisasi' => $sumRealisasi ?? 0,
            ];
        }

        return $this->successResponse([
            'target' => $dataTarget,
            'realisasi' => $dataRealisasi,
        ], 'Data berhasil diambil');
    }

    function summaryRealisasi(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'periode' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|digits:4',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $sumTarget = TargetKinerja::where('periode_id', $request->periode)
            ->where('year', $request->year)
            ->where('month', 12)
            // ->where('status', 'verified')
            ->where('is_detail', true)
            ->sum('pagu_sipd');

        $sumRealisasi = Realisasi::where('periode_id', $request->periode)
            ->where('year', $request->year)
            // ->where('status', 'verified')
            ->sum('anggaran');

        return $this->successResponse([
            'target' => [
                'updated_at' => TargetKinerja::where('periode_id', $request->periode)
                    ->where('year', $request->year)
                    ->where('is_detail', true)
                    ->latest('updated_at')
                    ->first()
                    ->updated_at ?? null,
                'target' => $sumTarget ?? 0,
            ],
            'realisasi' => [
                'updated_at' => Realisasi::where('periode_id', $request->periode)
                    ->where('year', $request->year)
                    ->latest('updated_at')
                    ->first()
                    ->updated_at ?? null,
                'realisasi' => $sumRealisasi ?? 0,
            ]
        ], 'Data berhasil diambil');
    }
}
