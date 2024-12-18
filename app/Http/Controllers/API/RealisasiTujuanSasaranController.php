<?php

namespace App\Http\Controllers\API;

use App\Models\Ref\Periode;
use App\Models\Caram\Tujuan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\Caram\Sasaran;
use App\Models\Data\TargetTujuan;
use App\Models\Data\TargetSasaran;
use Illuminate\Support\Facades\DB;
use App\Models\Ref\IndikatorTujuan;
use App\Http\Controllers\Controller;
use App\Models\Data\RealisasiSasaran;
use App\Models\Data\RealisasiTujuan;
use App\Models\Ref\IndikatorSasaran;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetPerubahanTujuan;
use App\Models\Data\TargetPerubahanSasaran;

class RealisasiTujuanSasaranController extends Controller
{
    use JsonReturner;

    function index(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:ref_periode,id',
            'year' => 'required|numeric|min:2000|max:2999',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $return = [];
        if ($request->instance_id === 'null' || $request->instance_id === '') {
            $request->instance_id = null;
        }

        DB::beginTransaction();
        try {
            // List Kabupaten
            if (!$request->instance_id) {
                $datas = Tujuan::whereNull('instance_id')
                    ->where('periode_id', $request->periode_id)
                    ->get();
                foreach ($datas as $data) {
                    $returnIndikatorTujuan = [];
                    $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                        ->where('tujuan_id', $data->id)
                        ->get();
                    foreach ($arrIndikatorTujuan as $indTujuan) {
                        $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)->first();

                        $targetTujuan = null;
                        $target = TargetTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', null)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();

                        $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', null)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->where('parent_id', $target->id)
                            ->first();

                        $targetTujuan = [
                            'year' => $request->year,
                            'month_1' => $targetPerubahan->value_1,
                            'month_2' => $targetPerubahan->value_2,
                            'month_3' => $targetPerubahan->value_3,
                            'month_4' => $targetPerubahan->value_4,
                            'month_5' => $targetPerubahan->value_5,
                            'month_6' => $targetPerubahan->value_6,
                            'month_7' => $targetPerubahan->value_7,
                            'month_8' => $targetPerubahan->value_8,
                            'month_9' => $targetPerubahan->value_9,
                            'month_10' => $targetPerubahan->value_10,
                            'month_11' => $targetPerubahan->value_11,
                            'month_12' => $targetPerubahan->value_12,
                            'target' => $target->value,
                        ];

                        $realisasiTujuan = null;
                        $realisasi = RealisasiTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', null)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();
                        if (!$realisasi) {
                            $realisasi = new RealisasiTujuan();
                            $realisasi->periode_id = $request->periode_id;
                            $realisasi->year = $request->year;
                            $realisasi->instance_id = null;
                            $realisasi->tujuan_id = $data->id;
                            $realisasi->ref_id = $indTujuan->ref_id;
                            $realisasi->status = 'active';
                            $realisasi->created_by = auth()->id();
                            $realisasi->save();
                        }

                        $realisasiTujuan = [
                            'year' => $request->year,

                            'realisasi_1' => $realisasi->realisasi_1,
                            'keterangan_1' => $realisasi->keterangan_1,
                            'files_1' => $realisasi->files_1,

                            'realisasi_2' => $realisasi->realisasi_2,
                            'keterangan_2' => $realisasi->keterangan_2,
                            'files_2' => $realisasi->files_2,

                            'realisasi_3' => $realisasi->realisasi_3,
                            'keterangan_3' => $realisasi->keterangan_3,
                            'files_3' => $realisasi->files_3,

                            'realisasi_4' => $realisasi->realisasi_4,
                            'keterangan_4' => $realisasi->keterangan_4,
                            'files_4' => $realisasi->files_4,

                            'realisasi_5' => $realisasi->realisasi_5,
                            'keterangan_5' => $realisasi->keterangan_5,
                            'files_5' => $realisasi->files_5,

                            'realisasi_6' => $realisasi->realisasi_6,
                            'keterangan_6' => $realisasi->keterangan_6,
                            'files_6' => $realisasi->files_6,

                            'realisasi_7' => $realisasi->realisasi_7,
                            'keterangan_7' => $realisasi->keterangan_7,
                            'files_7' => $realisasi->files_7,

                            'realisasi_8' => $realisasi->realisasi_8,
                            'keterangan_8' => $realisasi->keterangan_8,
                            'files_8' => $realisasi->files_8,

                            'realisasi_9' => $realisasi->realisasi_9,
                            'keterangan_9' => $realisasi->keterangan_9,
                            'files_9' => $realisasi->files_9,

                            'realisasi_10' => $realisasi->realisasi_10,
                            'keterangan_10' => $realisasi->keterangan_10,
                            'files_10' => $realisasi->files_10,

                            'realisasi_11' => $realisasi->realisasi_11,
                            'keterangan_11' => $realisasi->keterangan_11,
                            'files_11' => $realisasi->files_11,

                            'realisasi_12' => $realisasi->realisasi_12,
                            'keterangan_12' => $realisasi->keterangan_12,
                            'files_12' => $realisasi->files_12,

                            'realisasi' => $realisasi->realisasi,
                            'keterangan' => $realisasi->keterangan,
                            'files' => $realisasi->files,
                        ];

                        $returnIndikatorTujuan[] = [
                            'id_ref' => $indTujuan->ref_id,
                            'name' => $refIndikatorTujuan->name,
                            'rumus' => $indTujuan->rumus ?? null,
                            'target' => $targetTujuan,
                            'realisasi' => $realisasiTujuan,
                        ];
                    }

                    $returnSasaran = [];
                    $arrSasaran = Sasaran::where('tujuan_id', $data->id)->get();
                    foreach ($arrSasaran as $sasaran) {
                        $returnIndikatorSasaran = [];
                        $arrIndikatorTujuan = DB::table('pivot_master_sasaran_to_ref_sasaran')
                            ->where('sasaran_id', $sasaran->id)
                            ->get();
                        foreach ($arrIndikatorTujuan as $indSasaran) {
                            $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();

                            $targetSasaran = null;

                            $target = TargetSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', null)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();

                            $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', null)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->where('parent_id', $target->id)
                                ->first();


                            $targetSasaran = [
                                'year' => $request->year,
                                'month_1' => $targetPerubahan->value_1,
                                'month_2' => $targetPerubahan->value_2,
                                'month_3' => $targetPerubahan->value_3,
                                'month_4' => $targetPerubahan->value_4,
                                'month_5' => $targetPerubahan->value_5,
                                'month_6' => $targetPerubahan->value_6,
                                'month_7' => $targetPerubahan->value_7,
                                'month_8' => $targetPerubahan->value_8,
                                'month_9' => $targetPerubahan->value_9,
                                'month_10' => $targetPerubahan->value_10,
                                'month_11' => $targetPerubahan->value_11,
                                'month_12' => $targetPerubahan->value_12,
                                'target' => $target->value,
                            ];

                            $realisasiSasaran = null;
                            $realisasi = RealisasiSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', null)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();
                            if (!$realisasi) {
                                $realisasi = new RealisasiSasaran();
                                $realisasi->periode_id = $request->periode_id;
                                $realisasi->year = $request->year;
                                $realisasi->instance_id = null;
                                $realisasi->tujuan_id = $data->id;
                                $realisasi->sasaran_id = $sasaran->id;
                                $realisasi->ref_id = $indSasaran->ref_id;
                                $realisasi->created_by = auth()->id();
                                $realisasi->save();
                            }

                            $realisasiSasaran = [
                                'year' => $request->year,

                                'realisasi_1' => $realisasi->realisasi_1,
                                'keterangan_1' => $realisasi->keterangan_1,
                                'files_1' => $realisasi->files_1,

                                'realisasi_2' => $realisasi->realisasi_2,
                                'keterangan_2' => $realisasi->keterangan_2,
                                'files_2' => $realisasi->files_2,

                                'realisasi_3' => $realisasi->realisasi_3,
                                'keterangan_3' => $realisasi->keterangan_3,
                                'files_3' => $realisasi->files_3,

                                'realisasi_4' => $realisasi->realisasi_4,
                                'keterangan_4' => $realisasi->keterangan_4,
                                'files_4' => $realisasi->files_4,

                                'realisasi_5' => $realisasi->realisasi_5,
                                'keterangan_5' => $realisasi->keterangan_5,
                                'files_5' => $realisasi->files_5,

                                'realisasi_6' => $realisasi->realisasi_6,
                                'keterangan_6' => $realisasi->keterangan_6,
                                'files_6' => $realisasi->files_6,

                                'realisasi_7' => $realisasi->realisasi_7,
                                'keterangan_7' => $realisasi->keterangan_7,
                                'files_7' => $realisasi->files_7,

                                'realisasi_8' => $realisasi->realisasi_8,
                                'keterangan_8' => $realisasi->keterangan_8,
                                'files_8' => $realisasi->files_8,

                                'realisasi_9' => $realisasi->realisasi_9,
                                'keterangan_9' => $realisasi->keterangan_9,
                                'files_9' => $realisasi->files_9,

                                'realisasi_10' => $realisasi->realisasi_10,
                                'keterangan_10' => $realisasi->keterangan_10,
                                'files_10' => $realisasi->files_10,

                                'realisasi_11' => $realisasi->realisasi_11,
                                'keterangan_11' => $realisasi->keterangan_11,
                                'files_11' => $realisasi->files_11,

                                'realisasi_12' => $realisasi->realisasi_12,
                                'keterangan_12' => $realisasi->keterangan_12,
                                'files_12' => $realisasi->files_12,

                                'realisasi' => $realisasi->realisasi,
                                'keterangan' => $realisasi->keterangan,
                                'files' => $realisasi->files,
                            ];

                            $returnIndikatorSasaran[] = [
                                'id_ref' => $indSasaran->ref_id,
                                'name' => $refIndikatorSasaran->name ?? null,
                                'rumus' => $indSasaran->rumus ?? null,
                                'target' => $targetSasaran,
                                'realisasi' => $realisasiSasaran,
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

            // List OPD
            if ($request->instance_id) {
                $datas = Tujuan::where('instance_id', $request->instance_id)
                    ->where('periode_id', $request->periode_id)
                    ->get();
                foreach ($datas as $data) {
                    $returnIndikatorTujuan = [];
                    $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                        ->where('tujuan_id', $data->id)
                        ->get();
                    foreach ($arrIndikatorTujuan as $indTujuan) {
                        $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)->first();

                        $targetTujuan = [];
                        $target = TargetTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();

                        $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->where('parent_id', $target->id)
                            ->first();

                        $targetTujuan = [
                            'year' => $request->year,
                            'month_1' => $targetPerubahan->value_1,
                            'month_2' => $targetPerubahan->value_2,
                            'month_3' => $targetPerubahan->value_3,
                            'month_4' => $targetPerubahan->value_4,
                            'month_5' => $targetPerubahan->value_5,
                            'month_6' => $targetPerubahan->value_6,
                            'month_7' => $targetPerubahan->value_7,
                            'month_8' => $targetPerubahan->value_8,
                            'month_9' => $targetPerubahan->value_9,
                            'month_10' => $targetPerubahan->value_10,
                            'month_11' => $targetPerubahan->value_11,
                            'month_12' => $targetPerubahan->value_12,
                            'target' => $target->value,
                        ];

                        $realisasiTujuan = null;
                        $realisasi = RealisasiTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();
                        if (!$realisasi) {
                            $realisasi = new RealisasiTujuan();
                            $realisasi->periode_id = $request->periode_id;
                            $realisasi->year = $request->year;
                            $realisasi->instance_id = $request->instance_id;
                            $realisasi->tujuan_id = $data->id;
                            $realisasi->ref_id = $indTujuan->ref_id;
                            $realisasi->status = 'active';
                            $realisasi->created_by = auth()->id();
                            $realisasi->save();
                        }

                        $realisasiTujuan = [
                            'year' => $request->year,

                            'realisasi_1' => $realisasi->realisasi_1,
                            'keterangan_1' => $realisasi->keterangan_1,
                            'files_1' => $realisasi->files_1,

                            'realisasi_2' => $realisasi->realisasi_2,
                            'keterangan_2' => $realisasi->keterangan_2,
                            'files_2' => $realisasi->files_2,

                            'realisasi_3' => $realisasi->realisasi_3,
                            'keterangan_3' => $realisasi->keterangan_3,
                            'files_3' => $realisasi->files_3,

                            'realisasi_4' => $realisasi->realisasi_4,
                            'keterangan_4' => $realisasi->keterangan_4,
                            'files_4' => $realisasi->files_4,

                            'realisasi_5' => $realisasi->realisasi_5,
                            'keterangan_5' => $realisasi->keterangan_5,
                            'files_5' => $realisasi->files_5,

                            'realisasi_6' => $realisasi->realisasi_6,
                            'keterangan_6' => $realisasi->keterangan_6,
                            'files_6' => $realisasi->files_6,

                            'realisasi_7' => $realisasi->realisasi_7,
                            'keterangan_7' => $realisasi->keterangan_7,
                            'files_7' => $realisasi->files_7,

                            'realisasi_8' => $realisasi->realisasi_8,
                            'keterangan_8' => $realisasi->keterangan_8,
                            'files_8' => $realisasi->files_8,

                            'realisasi_9' => $realisasi->realisasi_9,
                            'keterangan_9' => $realisasi->keterangan_9,
                            'files_9' => $realisasi->files_9,

                            'realisasi_10' => $realisasi->realisasi_10,
                            'keterangan_10' => $realisasi->keterangan_10,
                            'files_10' => $realisasi->files_10,

                            'realisasi_11' => $realisasi->realisasi_11,
                            'keterangan_11' => $realisasi->keterangan_11,
                            'files_11' => $realisasi->files_11,

                            'realisasi_12' => $realisasi->realisasi_12,
                            'keterangan_12' => $realisasi->keterangan_12,
                            'files_12' => $realisasi->files_12,

                            'realisasi' => $realisasi->realisasi,
                            'keterangan' => $realisasi->keterangan,
                            'files' => $realisasi->files,
                        ];

                        $returnIndikatorTujuan[] = [
                            'id_ref' => $indTujuan->ref_id,
                            'name' => $refIndikatorTujuan->name,
                            'rumus' => $indTujuan->rumus ?? null,
                            'target' => $targetTujuan,
                            'realisasi' => $realisasiTujuan,
                        ];
                    }

                    $returnSasaran = [];
                    $arrSasaran = Sasaran::where('tujuan_id', $data->id)->get();
                    foreach ($arrSasaran as $sasaran) {
                        $returnIndikatorSasaran = [];
                        $arrIndikatorTujuan = DB::table('pivot_master_sasaran_to_ref_sasaran')
                            ->where('sasaran_id', $sasaran->id)
                            ->get();
                        foreach ($arrIndikatorTujuan as $indSasaran) {
                            $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();

                            $targetSasaran = null;

                            $target = TargetSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', $request->instance_id)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();

                            $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', $request->instance_id)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->where('parent_id', $target->id)
                                ->first();

                            $targetSasaran = [
                                'year' => $request->year,
                                'month_1' => $targetPerubahan->value_1,
                                'month_2' => $targetPerubahan->value_2,
                                'month_3' => $targetPerubahan->value_3,
                                'month_4' => $targetPerubahan->value_4,
                                'month_5' => $targetPerubahan->value_5,
                                'month_6' => $targetPerubahan->value_6,
                                'month_7' => $targetPerubahan->value_7,
                                'month_8' => $targetPerubahan->value_8,
                                'month_9' => $targetPerubahan->value_9,
                                'month_10' => $targetPerubahan->value_10,
                                'month_11' => $targetPerubahan->value_11,
                                'month_12' => $targetPerubahan->value_12,
                                'target' => $target->value,
                            ];

                            $realisasiSasaran = null;
                            $realisasi = RealisasiSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $request->year)
                                ->where('instance_id', $request->instance_id)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();
                            if (!$realisasi) {
                                $realisasi = new RealisasiSasaran();
                                $realisasi->periode_id = $request->periode_id;
                                $realisasi->year = $request->year;
                                $realisasi->instance_id = $request->instance_id;
                                $realisasi->tujuan_id = $data->id;
                                $realisasi->sasaran_id = $sasaran->id;
                                $realisasi->ref_id = $indSasaran->ref_id;
                                $realisasi->created_by = auth()->id();
                                $realisasi->save();
                            }

                            $realisasiSasaran = [
                                'year' => $request->year,

                                'realisasi_1' => $realisasi->realisasi_1,
                                'keterangan_1' => $realisasi->keterangan_1,
                                'files_1' => $realisasi->files_1,

                                'realisasi_2' => $realisasi->realisasi_2,
                                'keterangan_2' => $realisasi->keterangan_2,
                                'files_2' => $realisasi->files_2,

                                'realisasi_3' => $realisasi->realisasi_3,
                                'keterangan_3' => $realisasi->keterangan_3,
                                'files_3' => $realisasi->files_3,

                                'realisasi_4' => $realisasi->realisasi_4,
                                'keterangan_4' => $realisasi->keterangan_4,
                                'files_4' => $realisasi->files_4,

                                'realisasi_5' => $realisasi->realisasi_5,
                                'keterangan_5' => $realisasi->keterangan_5,
                                'files_5' => $realisasi->files_5,

                                'realisasi_6' => $realisasi->realisasi_6,
                                'keterangan_6' => $realisasi->keterangan_6,
                                'files_6' => $realisasi->files_6,

                                'realisasi_7' => $realisasi->realisasi_7,
                                'keterangan_7' => $realisasi->keterangan_7,
                                'files_7' => $realisasi->files_7,

                                'realisasi_8' => $realisasi->realisasi_8,
                                'keterangan_8' => $realisasi->keterangan_8,
                                'files_8' => $realisasi->files_8,

                                'realisasi_9' => $realisasi->realisasi_9,
                                'keterangan_9' => $realisasi->keterangan_9,
                                'files_9' => $realisasi->files_9,

                                'realisasi_10' => $realisasi->realisasi_10,
                                'keterangan_10' => $realisasi->keterangan_10,
                                'files_10' => $realisasi->files_10,

                                'realisasi_11' => $realisasi->realisasi_11,
                                'keterangan_11' => $realisasi->keterangan_11,
                                'files_11' => $realisasi->files_11,

                                'realisasi_12' => $realisasi->realisasi_12,
                                'keterangan_12' => $realisasi->keterangan_12,
                                'files_12' => $realisasi->files_12,

                                'realisasi' => $realisasi->realisasi,
                                'keterangan' => $realisasi->keterangan,
                                'files' => $realisasi->files,
                            ];

                            $returnIndikatorSasaran[] = [
                                'id_ref' => $indSasaran->ref_id,
                                'name' => $refIndikatorSasaran->name ?? null,
                                'rumus' => $indSasaran->rumus ?? null,
                                'target' => $targetSasaran,
                                'realisasi' => $realisasiSasaran,
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
            DB::commit();
            return $this->successResponse($return);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }


        return $this->successResponse($return);
    }

    function getDetail($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'nullable|numeric|exists:ref_periode,id',
            'type' => 'required|string',
            'year' => 'required|numeric|min:2000|max:2999',
            'month' => 'required|numeric|min:1|max:12'
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->type == 'tujuan') {
            $return = [];

            // get tujuan
            $tujuan = Tujuan::find($id);
            if (!$tujuan) {
                return $this->errorResponse('Tujuan tidak ditemukan');
            }

            // get indikator
            $refIndikator = IndikatorTujuan::where('id', $request->ref_id)->first();
            if (!$refIndikator) {
                return $this->errorResponse('Indikator Tujuan tidak ditemukan');
            }

            // get target
            $target = TargetTujuan::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $tujuan->id)
                ->where('ref_id', $request->ref_id)
                ->first();

            if (!$target) {
                return $this->errorResponse('Target tidak ditemukan');
            }

            // get target perubahan
            $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $tujuan->id)
                ->where('ref_id', $request->ref_id)
                ->where('parent_id', $target->id)
                ->first();

            if (!$targetPerubahan) {
                return $this->errorResponse('Target Perubahan tidak ditemukan');
            }

            $targetSelected = null;
            if ($request->month == 1) {
                $targetSelected = $targetPerubahan->value_1;
            }
            if ($request->month == 2) {
                $targetSelected = $targetPerubahan->value_2;
            }
            if ($request->month == 3) {
                $targetSelected = $targetPerubahan->value_3;
            }
            if ($request->month == 4) {
                $targetSelected = $targetPerubahan->value_4;
            }
            if ($request->month == 5) {
                $targetSelected = $targetPerubahan->value_5;
            }
            if ($request->month == 6) {
                $targetSelected = $targetPerubahan->value_6;
            }
            if ($request->month == 7) {
                $targetSelected = $targetPerubahan->value_7;
            }
            if ($request->month == 8) {
                $targetSelected = $targetPerubahan->value_8;
            }
            if ($request->month == 9) {
                $targetSelected = $targetPerubahan->value_9;
            }
            if ($request->month == 10) {
                $targetSelected = $targetPerubahan->value_10;
            }
            if ($request->month == 11) {
                $targetSelected = $targetPerubahan->value_11;
            }
            if ($request->month == 12) {
                $targetSelected = $targetPerubahan->value_12;
            }

            // get realisasi
            $realisasi = RealisasiTujuan::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $tujuan->id)
                ->where('ref_id', $request->ref_id)
                ->first();

            $realisasiValue = null;
            $realisasiKeterangan = null;
            $realisasiFiles = [];

            if ($request->month == 1) {
                $realisasiValue = $realisasi->realisasi_1;
                $realisasiKeterangan = $realisasi->keterangan_1;
                $realisasiFiles = $realisasi->files_1;
            }
            if ($request->month == 2) {
                $realisasiValue = $realisasi->realisasi_2;
                $realisasiKeterangan = $realisasi->keterangan_2;
                $realisasiFiles = $realisasi->files_2;
            }
            if ($request->month == 3) {
                $realisasiValue = $realisasi->realisasi_3;
                $realisasiKeterangan = $realisasi->keterangan_3;
                $realisasiFiles = $realisasi->files_3;
            }
            if ($request->month == 4) {
                $realisasiValue = $realisasi->realisasi_4;
                $realisasiKeterangan = $realisasi->keterangan_4;
                $realisasiFiles = $realisasi->files_4;
            }
            if ($request->month == 5) {
                $realisasiValue = $realisasi->realisasi_5;
                $realisasiKeterangan = $realisasi->keterangan_5;
                $realisasiFiles = $realisasi->files_5;
            }
            if ($request->month == 6) {
                $realisasiValue = $realisasi->realisasi_6;
                $realisasiKeterangan = $realisasi->keterangan_6;
                $realisasiFiles = $realisasi->files_6;
            }
            if ($request->month == 7) {
                $realisasiValue = $realisasi->realisasi_7;
                $realisasiKeterangan = $realisasi->keterangan_7;
                $realisasiFiles = $realisasi->files_7;
            }
            if ($request->month == 8) {
                $realisasiValue = $realisasi->realisasi_8;
                $realisasiKeterangan = $realisasi->keterangan_8;
                $realisasiFiles = $realisasi->files_8;
            }
            if ($request->month == 9) {
                $realisasiValue = $realisasi->realisasi_9;
                $realisasiKeterangan = $realisasi->keterangan_9;
                $realisasiFiles = $realisasi->files_9;
            }
            if ($request->month == 10) {
                $realisasiValue = $realisasi->realisasi_10;
                $realisasiKeterangan = $realisasi->keterangan_10;
                $realisasiFiles = $realisasi->files_10;
            }
            if ($request->month == 11) {
                $realisasiValue = $realisasi->realisasi_11;
                $realisasiKeterangan = $realisasi->keterangan_11;
                $realisasiFiles = $realisasi->files_11;
            }
            if ($request->month == 12) {
                $realisasiValue = $realisasi->realisasi_12;
                $realisasiKeterangan = $realisasi->keterangan_12;
                $realisasiFiles = $realisasi->files_12;
            }

            $return = [
                'realisasi_id' => $realisasi->id,
                'tujuan_id' => $tujuan->id,
                'tujuan_name' => $tujuan->RefTujuan->name,
                'type' => $request->type,
                'ref_id' => $request->ref_id,
                'indikator' => $refIndikator->name,
                'year' => $request->year,
                'month' => $request->month,
                'periode_id' => $request->periode_id,
                'target_tahunan' => $target->value,
                'target' => $targetSelected,
                'realisasiValue' => $realisasiValue,
                'realisasiKeterangan' => $realisasiKeterangan,
                'realisasiFiles' => $realisasiFiles,
            ];

            return $this->successResponse($return);
        }


        if ($request->type == 'sasaran') {
            $return = [];

            // get sasaran
            $sasaran = Sasaran::find($id);
            if (!$sasaran) {
                return $this->errorResponse('Sasaran tidak ditemukan');
            }

            // get indikator
            $refIndikator = IndikatorSasaran::where('id', $request->ref_id)->first();
            if (!$refIndikator) {
                return $this->errorResponse('Indikator Sasaran tidak ditemukan');
            }

            // get target
            $target = TargetSasaran::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $sasaran->tujuan_id)
                ->where('sasaran_id', $sasaran->id)
                ->where('ref_id', $request->ref_id)
                ->first();

            if (!$target) {
                return $this->errorResponse('Target tidak ditemukan');
            }

            // get target perubahan
            $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $sasaran->tujuan_id)
                ->where('sasaran_id', $sasaran->id)
                ->where('ref_id', $request->ref_id)
                ->where('parent_id', $target->id)
                ->first();

            if (!$targetPerubahan) {
                return $this->errorResponse('Target Perubahan tidak ditemukan');
            }


            $targetSelected = null;
            if ($request->month == 1) {
                $targetSelected = $targetPerubahan->value_1;
            }
            if ($request->month == 2) {
                $targetSelected = $targetPerubahan->value_2;
            }
            if ($request->month == 3) {
                $targetSelected = $targetPerubahan->value_3;
            }
            if ($request->month == 4) {
                $targetSelected = $targetPerubahan->value_4;
            }
            if ($request->month == 5) {
                $targetSelected = $targetPerubahan->value_5;
            }
            if ($request->month == 6) {
                $targetSelected = $targetPerubahan->value_6;
            }
            if ($request->month == 7) {
                $targetSelected = $targetPerubahan->value_7;
            }
            if ($request->month == 8) {
                $targetSelected = $targetPerubahan->value_8;
            }
            if ($request->month == 9) {
                $targetSelected = $targetPerubahan->value_9;
            }
            if ($request->month == 10) {
                $targetSelected = $targetPerubahan->value_10;
            }
            if ($request->month == 11) {
                $targetSelected = $targetPerubahan->value_11;
            }
            if ($request->month == 12) {
                $targetSelected = $targetPerubahan->value_12;
            }

            // get realisasi
            $realisasi = RealisasiSasaran::where('periode_id', $request->periode_id)
                ->where('year', $request->year)
                ->where('instance_id', $request->instance_id)
                ->where('tujuan_id', $sasaran->tujuan_id)
                ->where('sasaran_id', $sasaran->id)
                ->where('ref_id', $request->ref_id)
                ->first();

            $realisasiValue = null;
            $realisasiKeterangan = null;
            $realisasiFiles = [];

            if ($request->month == 1) {
                $realisasiValue = $realisasi->realisasi_1;
                $realisasiKeterangan = $realisasi->keterangan_1;
                $realisasiFiles = $realisasi->files_1;
            }
            if ($request->month == 2) {
                $realisasiValue = $realisasi->realisasi_2;
                $realisasiKeterangan = $realisasi->keterangan_2;
                $realisasiFiles = $realisasi->files_2;
            }
            if ($request->month == 3) {
                $realisasiValue = $realisasi->realisasi_3;
                $realisasiKeterangan = $realisasi->keterangan_3;
                $realisasiFiles = $realisasi->files_3;
            }
            if ($request->month == 4) {
                $realisasiValue = $realisasi->realisasi_4;
                $realisasiKeterangan = $realisasi->keterangan_4;
                $realisasiFiles = $realisasi->files_4;
            }
            if ($request->month == 5) {
                $realisasiValue = $realisasi->realisasi_5;
                $realisasiKeterangan = $realisasi->keterangan_5;
                $realisasiFiles = $realisasi->files_5;
            }
            if ($request->month == 6) {
                $realisasiValue = $realisasi->realisasi_6;
                $realisasiKeterangan = $realisasi->keterangan_6;
                $realisasiFiles = $realisasi->files_6;
            }
            if ($request->month == 7) {
                $realisasiValue = $realisasi->realisasi_7;
                $realisasiKeterangan = $realisasi->keterangan_7;
                $realisasiFiles = $realisasi->files_7;
            }
            if ($request->month == 8) {
                $realisasiValue = $realisasi->realisasi_8;
                $realisasiKeterangan = $realisasi->keterangan_8;
                $realisasiFiles = $realisasi->files_8;
            }
            if ($request->month == 9) {
                $realisasiValue = $realisasi->realisasi_9;
                $realisasiKeterangan = $realisasi->keterangan_9;
                $realisasiFiles = $realisasi->files_9;
            }
            if ($request->month == 10) {
                $realisasiValue = $realisasi->realisasi_10;
                $realisasiKeterangan = $realisasi->keterangan_10;
                $realisasiFiles = $realisasi->files_10;
            }
            if ($request->month == 11) {
                $realisasiValue = $realisasi->realisasi_11;
                $realisasiKeterangan = $realisasi->keterangan_11;
                $realisasiFiles = $realisasi->files_11;
            }
            if ($request->month == 12) {
                $realisasiValue = $realisasi->realisasi_12;
                $realisasiKeterangan = $realisasi->keterangan_12;
                $realisasiFiles = $realisasi->files_12;
            }

            $return = [
                'realisasi_id' => $realisasi->id,
                'tujuan_id' => $sasaran->tujuan_id,
                'tujuan_name' => $sasaran->Tujuan->RefTujuan->name ?? '',
                'sasaran_id' => $sasaran->id,
                'sasaran_name' => $sasaran->RefSasaran->name ?? '',
                'type' => $request->type,
                'ref_id' => $request->ref_id,
                'indikator' => $refIndikator->name,
                'year' => $request->year,
                'month' => $request->month,
                'periode_id' => $request->periode_id,
                'target_tahunan' => $target->value,
                'target' => $targetSelected,
                'realisasiValue' => $realisasiValue,
                'realisasiKeterangan' => $realisasiKeterangan,
                'realisasiFiles' => $realisasiFiles,
            ];

            return $this->successResponse($return);
        }
    }

    function updated($id, Request $request)
    {
        if ($request->inputType == 'tujuan') {

            $validate = Validator::make($request->all(), [
                'instance_id' => 'nullable|numeric|exists:instances,id',
                'periode_id' => 'nullable|numeric|exists:ref_periode,id',
                'inputType' => 'required|string',
                'year' => 'required|numeric|min:2000|max:2999',
                'month' => 'required|numeric|min:1|max:12',
                'realisasi_id' => 'required',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $realisasi = RealisasiTujuan::find($request->realisasi_id);
                if (!$realisasi) {
                    return $this->errorResponse('Realisasi tidak ditemukan');
                }

                if ($request->month == 1) {
                    $realisasi->realisasi_1 = $request->realisasiValue;
                    $realisasi->keterangan_1 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 2) {
                    $realisasi->realisasi_2 = $request->realisasiValue;
                    $realisasi->keterangan_2 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 3) {
                    $realisasi->realisasi_3 = $request->realisasiValue;
                    $realisasi->keterangan_3 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 4) {
                    $realisasi->realisasi_4 = $request->realisasiValue;
                    $realisasi->keterangan_4 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 5) {
                    $realisasi->realisasi_5 = $request->realisasiValue;
                    $realisasi->keterangan_5 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 6) {
                    $realisasi->realisasi_6 = $request->realisasiValue;
                    $realisasi->keterangan_6 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 7) {
                    $realisasi->realisasi_7 = $request->realisasiValue;
                    $realisasi->keterangan_7 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 8) {
                    $realisasi->realisasi_8 = $request->realisasiValue;
                    $realisasi->keterangan_8 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 9) {
                    $realisasi->realisasi_9 = $request->realisasiValue;
                    $realisasi->keterangan_9 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 10) {
                    $realisasi->realisasi_10 = $request->realisasiValue;
                    $realisasi->keterangan_10 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 11) {
                    $realisasi->realisasi_11 = $request->realisasiValue;
                    $realisasi->keterangan_11 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 12) {
                    $realisasi->realisasi_12 = $request->realisasiValue;
                    $realisasi->keterangan_12 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                $realisasi->updated_by = auth()->id();
                $realisasi->save();

                DB::commit();
                return $this->successResponse($realisasi, 'Realisasi Tujuan berhasil disimpan!');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse([
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
            }
        }

        if ($request->inputType == 'sasaran') {

            $validate = Validator::make($request->all(), [
                'instance_id' => 'nullable|numeric|exists:instances,id',
                'periode_id' => 'nullable|numeric|exists:ref_periode,id',
                'inputType' => 'required|string',
                'year' => 'required|numeric|min:2000|max:2999',
                'month' => 'required|numeric|min:1|max:12',
                'realisasi_id' => 'required',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $realisasi = RealisasiSasaran::find($request->realisasi_id);
                if (!$realisasi) {
                    return $this->errorResponse('Realisasi tidak ditemukan');
                }

                if ($request->month == 1) {
                    $realisasi->realisasi_1 = $request->realisasiValue;
                    $realisasi->keterangan_1 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 2) {
                    $realisasi->realisasi_2 = $request->realisasiValue;
                    $realisasi->keterangan_2 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 3) {
                    $realisasi->realisasi_3 = $request->realisasiValue;
                    $realisasi->keterangan_3 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 4) {
                    $realisasi->realisasi_4 = $request->realisasiValue;
                    $realisasi->keterangan_4 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 5) {
                    $realisasi->realisasi_5 = $request->realisasiValue;
                    $realisasi->keterangan_5 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 6) {
                    $realisasi->realisasi_6 = $request->realisasiValue;
                    $realisasi->keterangan_6 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 7) {
                    $realisasi->realisasi_7 = $request->realisasiValue;
                    $realisasi->keterangan_7 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 8) {
                    $realisasi->realisasi_8 = $request->realisasiValue;
                    $realisasi->keterangan_8 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 9) {
                    $realisasi->realisasi_9 = $request->realisasiValue;
                    $realisasi->keterangan_9 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 10) {
                    $realisasi->realisasi_10 = $request->realisasiValue;
                    $realisasi->keterangan_10 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 11) {
                    $realisasi->realisasi_11 = $request->realisasiValue;
                    $realisasi->keterangan_11 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                if ($request->month == 12) {
                    $realisasi->realisasi_12 = $request->realisasiValue;
                    $realisasi->keterangan_12 = $request->realisasiKeterangan;
                    if ($request->realisasiFiles) {
                        // upload files
                    }
                }

                $realisasi->updated_by = auth()->id();
                $realisasi->save();

                DB::commit();
                return $this->successResponse($realisasi, 'Realisasi Sasaran berhasil disimpan!');
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
}
