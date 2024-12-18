<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
use App\Models\Ref\IndikatorSasaran;
use Illuminate\Support\Facades\Validator;
use App\Models\Data\TargetPerubahanTujuan;
use App\Models\Data\TargetPerubahanSasaran;

class TargetTujuanSasaranController extends Controller
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
        if ($request->instance_id === 'null' || $request->instance_id === '') {
            $request->instance_id = null;
        }

        $rangeYear = [];
        $periode = Periode::find($request->periode_id);
        $rangeYear = CarbonPeriod::create($periode->start_date, $periode->end_date)->years()->toArray();
        foreach ($rangeYear as $key => $date) {
            $rangeYear[$key] = $date->format('Y');
        }

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
                    $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)
                        ->first();

                    $targetTujuan = [];
                    foreach ($rangeYear as $year) {
                        $target = TargetTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $year)
                            ->where('instance_id', null)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();
                        if (!$target) {
                            $target = new TargetTujuan();
                            $target->periode_id = $request->periode_id;
                            $target->year = $year;
                            $target->instance_id = null;
                            $target->tujuan_id = $data->id;
                            $target->ref_id = $indTujuan->ref_id;
                            $target->value = null;
                            $target->created_by = auth()->id();
                            $target->save();
                        }
                        $targetTujuan[] = [
                            'year' => $year,
                            'value' => $target->value,
                        ];
                    }

                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                        'target' => $targetTujuan,
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

                        $targetSasaran = [];
                        foreach ($rangeYear as $year) {
                            $target = TargetSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $year)
                                ->where('instance_id', null)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();
                            if (!$target) {
                                $target = new TargetSasaran();
                                $target->periode_id = $request->periode_id;
                                $target->year = $year;
                                $target->instance_id = null;
                                $target->tujuan_id = $data->id;
                                $target->sasaran_id = $sasaran->id;
                                $target->ref_id = $indSasaran->ref_id;
                                $target->value = null;
                                $target->created_by = auth()->id();
                                $target->save();
                            }
                            $targetSasaran[] = [
                                'year' => $year,
                                'value' => $target->value,
                            ];
                        }


                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                            'target' => $targetSasaran,
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
                    $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)
                        ->first();

                    $targetTujuan = [];
                    foreach ($rangeYear as $year) {
                        $target = TargetTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $data->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();
                        if (!$target) {
                            $target = new TargetTujuan();
                            $target->periode_id = $request->periode_id;
                            $target->year = $year;
                            $target->instance_id = $request->instance_id;
                            $target->tujuan_id = $data->id;
                            $target->ref_id = $indTujuan->ref_id;
                            $target->value = null;
                            $target->created_by = auth()->id();
                            $target->save();
                        }
                        $targetTujuan[] = [
                            'year' => $year,
                            'value' => $target->value,
                        ];
                    }

                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                        'target' => $targetTujuan,
                    ];
                }

                $returnSasaran = [];
                $arrSasaran = Sasaran::where('tujuan_id', $data->id)
                    ->where('periode_id', $request->periode_id)
                    ->get();
                foreach ($arrSasaran as $sasaran) {
                    $returnIndikatorSasaran = [];
                    $arrIndikatorTujuan = DB::table('pivot_master_sasaran_to_ref_sasaran')
                        ->where('sasaran_id', $sasaran->id)
                        ->get();
                    foreach ($arrIndikatorTujuan as $indSasaran) {
                        $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();

                        $targetSasaran = [];
                        foreach ($rangeYear as $year) {
                            $target = TargetSasaran::where('periode_id', $request->periode_id)
                                ->where('year', $year)
                                ->where('instance_id', $request->instance_id)
                                ->where('tujuan_id', $data->id)
                                ->where('sasaran_id', $sasaran->id)
                                ->where('ref_id', $indSasaran->ref_id)
                                ->first();
                            if (!$target) {
                                $target = new TargetSasaran();
                                $target->periode_id = $request->periode_id;
                                $target->year = $year;
                                $target->instance_id = $request->instance_id;
                                $target->tujuan_id = $data->id;
                                $target->sasaran_id = $sasaran->id;
                                $target->ref_id = $indSasaran->ref_id;
                                $target->value = null;
                                $target->created_by = auth()->id();
                                $target->save();
                            }
                            $targetSasaran[] = [
                                'year' => $year,
                                'value' => $target->value,
                            ];
                        }


                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                            'target' => $targetSasaran,
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


        return $this->successResponse($return);
    }

    function getDetail($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'nullable|numeric|exists:ref_periode,id',
            'type' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        $rangeYear = [];
        $periode = Periode::find($request->periode_id);
        $rangeYear = CarbonPeriod::create($periode->start_date, $periode->end_date)->years()->toArray();

        try {
            if ($request->type == 'tujuan') {
                $tujuan = Tujuan::find($id);
                if (!$tujuan) {
                    return $this->errorResponse('Tujuan tidak ditemukan');
                }

                $datas = [];

                $returnIndikatorTujuan = [];
                $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                    ->where('tujuan_id', $tujuan->id)
                    ->get();
                foreach ($arrIndikatorTujuan as $indTujuan) {
                    $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)
                        ->first();
                    $targetTujuan = [];
                    foreach ($rangeYear as $year) {
                        $year = Carbon::parse($year)->format('Y');
                        $target = TargetTujuan::where('periode_id', $request->periode_id)
                            ->where('year', $year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $tujuan->id)
                            ->where('ref_id', $indTujuan->ref_id)
                            ->first();
                        $targetTujuan[] = [
                            'year' => $year,
                            'value' => $target->value,
                        ];
                    }

                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                        'target' => $targetTujuan,
                    ];
                }

                $datas = [
                    'tujuan_id' => $tujuan->id,
                    'tujuan' => $tujuan->RefTujuan->name,
                    'indikator_tujuan' => $returnIndikatorTujuan,
                ];

                return $this->successResponse($datas, 'Data Target Tujuan');
            }

            if ($request->type == 'sasaran') {
                $sasaran = Sasaran::find($id);
                if (!$sasaran) {
                    return $this->errorResponse('Sasaran tidak ditemukan');
                }

                $datas = [];

                $returnIndikatorSasaran = [];
                $arrIndikatorSasaran = DB::table('pivot_master_sasaran_to_ref_sasaran')
                    ->where('sasaran_id', $sasaran->id)
                    ->get();
                foreach ($arrIndikatorSasaran as $indSasaran) {
                    $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();
                    $targetSasaran = [];
                    foreach ($rangeYear as $year) {
                        $year = Carbon::parse($year)->format('Y');
                        $target = TargetSasaran::where('periode_id', $request->periode_id)
                            ->where('year', $year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $sasaran->tujuan_id)
                            ->where('sasaran_id', $sasaran->id)
                            ->where('ref_id', $indSasaran->ref_id)
                            ->first();
                        $targetSasaran[] = [
                            'year' => $year,
                            'value' => $target->value,
                        ];
                    }

                    $returnIndikatorSasaran[] = [
                        'id_ref' => $indSasaran->ref_id,
                        'name' => $refIndikatorSasaran->name,
                        'rumus' => $indSasaran->rumus ?? null,
                        'target' => $targetSasaran,
                    ];
                }

                $datas = [
                    'sasaran_id' => $sasaran->id,
                    'tujuan' => $sasaran->Tujuan->RefTujuan->name ?? null,
                    'sasaran' => $sasaran->RefSasaran->name,
                    'indikator_sasaran' => $returnIndikatorSasaran,
                ];

                return $this->successResponse($datas, 'Data Target Tujuan');
            }
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    function update($id, Request $request)
    {
        if ($request->inputType == 'tujuan') {
            DB::beginTransaction();
            try {
                $data = Tujuan::find($request->id ?? $id);
                if (!$data) {
                    return $this->errorResponse('Tujuan tidak ditemukan');
                }

                foreach ($request->data as $inputTarget) {
                    foreach ($inputTarget['target'] as $input) {
                        $target = TargetTujuan::where('tujuan_id', $data->id)
                            ->where('ref_id', $inputTarget['id_ref'])
                            ->where('year', $input['year'])
                            ->first();
                        if ($target) {
                            $target->last_value = $target->value;
                            $target->value = $input['value'];
                            $target->updated_by = auth()->id();
                            $target->save();
                        }
                    }
                }
                DB::commit();

                return $this->successResponse(null, 'Target Tujuan telah diperbarui!');
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
            DB::beginTransaction();
            try {
                $data = Sasaran::find($request->id ?? $id);
                if (!$data) {
                    return $this->errorResponse('Sasaran tidak ditemukan');
                }

                foreach ($request->data as $inputTarget) {
                    foreach ($inputTarget['target'] as $input) {
                        $target = TargetSasaran::where('sasaran_id', $data->id)
                            ->where('tujuan_id', $data->tujuan_id)
                            ->where('ref_id', $inputTarget['id_ref'])
                            ->where('year', $input['year'])
                            ->first();

                        if (!$target) {
                            $target = new TargetSasaran();
                            $target->sasaran_id = $data->id;
                            $target->tujuan_id = $data->tujuan_id;
                            $target->ref_id = $inputTarget['id_ref'];
                            $target->year = $input['year'];
                            $target->periode_id = $data->periode_id;
                            $target->instance_id = $data->instance_id;
                            $target->created_by = auth()->id();
                        }

                        $target->last_value = $target->value;
                        $target->value = $input['value'];
                        $target->updated_by = auth()->id();
                        $target->save();
                    }
                }
                DB::commit();

                return $this->successResponse(null, 'Target Sasaran telah diperbarui!');
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


    // Perubahan
    function indexPerubahan(Request $request)
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

        $periode = Periode::find($request->periode_id);

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

                    $targetTujuan = [];
                    $target = TargetTujuan::where('periode_id', $request->periode_id)
                        ->where('year', $request->year)
                        ->where('instance_id', null)
                        ->where('tujuan_id', $data->id)
                        ->where('ref_id', $indTujuan->ref_id)
                        ->first();
                    if (!$target) {
                        $target = new TargetTujuan();
                        $target->periode_id = $request->periode_id;
                        $target->year = $request->year;
                        $target->instance_id = null;
                        $target->tujuan_id = $data->id;
                        $target->ref_id = $indTujuan->ref_id;
                        $target->value = null;
                        $target->created_by = auth()->id();
                        $target->save();
                    }

                    $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                        ->where('year', $request->year)
                        ->where('instance_id', null)
                        ->where('tujuan_id', $data->id)
                        ->where('ref_id', $indTujuan->ref_id)
                        ->where('parent_id', $target->id)
                        ->first();
                    if (!$targetPerubahan) {
                        $targetPerubahan = new TargetPerubahanTujuan();
                        $targetPerubahan->periode_id = $request->periode_id;
                        $targetPerubahan->year = $request->year;
                        $targetPerubahan->instance_id = null;
                        $targetPerubahan->tujuan_id = $data->id;
                        $targetPerubahan->ref_id = $indTujuan->ref_id;
                        $targetPerubahan->parent_id = $target->id;
                        $targetPerubahan->value_1 = null;
                        $targetPerubahan->value_2 = null;
                        $targetPerubahan->value_3 = null;
                        $targetPerubahan->value_4 = null;
                        $targetPerubahan->value_5 = null;
                        $targetPerubahan->value_6 = null;
                        $targetPerubahan->value_7 = null;
                        $targetPerubahan->value_8 = null;
                        $targetPerubahan->value_9 = null;
                        $targetPerubahan->value_10 = null;
                        $targetPerubahan->value_11 = null;
                        $targetPerubahan->value_12 = null;
                        $targetPerubahan->created_by = auth()->id();
                        $targetPerubahan->save();
                    }

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

                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                        'target' => $targetTujuan,
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
                        if (!$target) {
                            $target = new TargetSasaran();
                            $target->periode_id = $request->periode_id;
                            $target->year = $request->year;
                            $target->instance_id = null;
                            $target->tujuan_id = $data->id;
                            $target->sasaran_id = $sasaran->id;
                            $target->ref_id = $indSasaran->ref_id;
                            $target->value = null;
                            $target->created_by = auth()->id();
                            $target->save();
                        }

                        $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', null)
                            ->where('tujuan_id', $data->id)
                            ->where('sasaran_id', $sasaran->id)
                            ->where('ref_id', $indSasaran->ref_id)
                            ->where('parent_id', $target->id)
                            ->first();
                        if (!$targetPerubahan) {
                            $targetPerubahan = new TargetPerubahanSasaran();
                            $targetPerubahan->periode_id = $request->periode_id;
                            $targetPerubahan->year = $request->year;
                            $targetPerubahan->instance_id = null;
                            $targetPerubahan->tujuan_id = $data->id;
                            $targetPerubahan->sasaran_id = $sasaran->id;
                            $targetPerubahan->ref_id = $indSasaran->ref_id;
                            $targetPerubahan->parent_id = $target->id;
                            $targetPerubahan->value_1 = null;
                            $targetPerubahan->value_2 = null;
                            $targetPerubahan->value_3 = null;
                            $targetPerubahan->value_4 = null;
                            $targetPerubahan->value_5 = null;
                            $targetPerubahan->value_6 = null;
                            $targetPerubahan->value_7 = null;
                            $targetPerubahan->value_8 = null;
                            $targetPerubahan->value_9 = null;
                            $targetPerubahan->value_10 = null;
                            $targetPerubahan->value_11 = null;
                            $targetPerubahan->value_12 = null;
                            $targetPerubahan->created_by = auth()->id();
                            $targetPerubahan->save();
                        }


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

                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                            'target' => $targetSasaran,
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
                    if (!$target) {
                        $target = new TargetTujuan();
                        $target->periode_id = $request->periode_id;
                        $target->year = $request->year;
                        $target->instance_id = $request->instance_id;
                        $target->tujuan_id = $data->id;
                        $target->ref_id = $indTujuan->ref_id;
                        $target->value = null;
                        $target->created_by = auth()->id();
                        $target->save();
                    }

                    $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                        ->where('year', $request->year)
                        ->where('instance_id', $request->instance_id)
                        ->where('tujuan_id', $data->id)
                        ->where('ref_id', $indTujuan->ref_id)
                        ->where('parent_id', $target->id)
                        ->first();
                    if (!$targetPerubahan) {
                        $targetPerubahan = new TargetPerubahanTujuan();
                        $targetPerubahan->periode_id = $request->periode_id;
                        $targetPerubahan->year = $request->year;
                        $targetPerubahan->instance_id = $request->instance_id;
                        $targetPerubahan->tujuan_id = $data->id;
                        $targetPerubahan->ref_id = $indTujuan->ref_id;
                        $targetPerubahan->parent_id = $target->id;
                        $targetPerubahan->value_1 = null;
                        $targetPerubahan->value_2 = null;
                        $targetPerubahan->value_3 = null;
                        $targetPerubahan->value_4 = null;
                        $targetPerubahan->value_5 = null;
                        $targetPerubahan->value_6 = null;
                        $targetPerubahan->value_7 = null;
                        $targetPerubahan->value_8 = null;
                        $targetPerubahan->value_9 = null;
                        $targetPerubahan->value_10 = null;
                        $targetPerubahan->value_11 = null;
                        $targetPerubahan->value_12 = null;
                        $targetPerubahan->created_by = auth()->id();
                        $targetPerubahan->save();
                    }

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

                    $targetPerubahan = [];

                    $returnIndikatorTujuan[] = [
                        'id_ref' => $indTujuan->ref_id,
                        'name' => $refIndikatorTujuan->name,
                        'rumus' => $indTujuan->rumus ?? null,
                        'target' => $targetTujuan,
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
                        if (!$target) {
                            $target = new TargetSasaran();
                            $target->periode_id = $request->periode_id;
                            $target->year = $request->year;
                            $target->instance_id = $request->instance_id;
                            $target->tujuan_id = $data->id;
                            $target->sasaran_id = $sasaran->id;
                            $target->ref_id = $indSasaran->ref_id;
                            $target->value = null;
                            $target->created_by = auth()->id();
                            $target->save();
                        }

                        $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                            ->where('year', $request->year)
                            ->where('instance_id', $request->instance_id)
                            ->where('tujuan_id', $data->id)
                            ->where('sasaran_id', $sasaran->id)
                            ->where('ref_id', $indSasaran->ref_id)
                            ->where('parent_id', $target->id)
                            ->first();
                        if (!$targetPerubahan) {
                            $targetPerubahan = new TargetPerubahanSasaran();
                            $targetPerubahan->periode_id = $request->periode_id;
                            $targetPerubahan->year = $request->year;
                            $targetPerubahan->instance_id = $request->instance_id;
                            $targetPerubahan->tujuan_id = $data->id;
                            $targetPerubahan->sasaran_id = $sasaran->id;
                            $targetPerubahan->ref_id = $indSasaran->ref_id;
                            $targetPerubahan->parent_id = $target->id;
                            $targetPerubahan->value_1 = null;
                            $targetPerubahan->value_2 = null;
                            $targetPerubahan->value_3 = null;
                            $targetPerubahan->value_4 = null;
                            $targetPerubahan->value_5 = null;
                            $targetPerubahan->value_6 = null;
                            $targetPerubahan->value_7 = null;
                            $targetPerubahan->value_8 = null;
                            $targetPerubahan->value_9 = null;
                            $targetPerubahan->value_10 = null;
                            $targetPerubahan->value_11 = null;
                            $targetPerubahan->value_12 = null;
                            $targetPerubahan->created_by = auth()->id();
                            $targetPerubahan->save();
                        }


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

                        $returnIndikatorSasaran[] = [
                            'id_ref' => $indSasaran->ref_id,
                            'name' => $refIndikatorSasaran->name ?? null,
                            'rumus' => $indSasaran->rumus ?? null,
                            'target' => $targetSasaran,
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

        return $this->successResponse($return);
    }

    function getDetailPerubahan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'instance_id' => 'nullable|numeric|exists:instances,id',
            'periode_id' => 'required|numeric|exists:ref_periode,id',
            'type' => 'required|string',
            'year' => 'required|numeric|min:2000|max:2999',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        if ($request->type == 'tujuan') {
            $tujuan = Tujuan::find($id);
            if (!$tujuan) {
                return $this->errorResponse('Tujuan tidak ditemukan');
            }

            $datas = [];

            $returnIndikatorTujuan = [];
            $arrIndikatorTujuan = DB::table('pivot_master_tujuan_to_ref_tujuan')
                ->where('tujuan_id', $tujuan->id)
                ->get();
            foreach ($arrIndikatorTujuan as $indTujuan) {
                $refIndikatorTujuan = IndikatorTujuan::where('id', $indTujuan->ref_id)->first();
                $targetTujuan = [];
                $target = TargetTujuan::where('periode_id', $request->periode_id)
                    ->where('year', $request->year)
                    ->where('instance_id', $request->instance_id)
                    ->where('tujuan_id', $tujuan->id)
                    ->where('ref_id', $indTujuan->ref_id)
                    ->first();

                $targetPerubahan = TargetPerubahanTujuan::where('periode_id', $request->periode_id)
                    ->where('year', $request->year)
                    ->where('instance_id', $request->instance_id)
                    ->where('tujuan_id', $tujuan->id)
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

                $returnIndikatorTujuan[] = [
                    'id_ref' => $indTujuan->ref_id,
                    'name' => $refIndikatorTujuan->name,
                    'rumus' => $indTujuan->rumus ?? null,
                    'target' => $targetTujuan,
                ];
            }

            $datas = [
                'tujuan_id' => $tujuan->id,
                'tujuan' => $tujuan->RefTujuan->name,
                'indikator_tujuan' => $returnIndikatorTujuan,
            ];

            return $this->successResponse($datas, 'Data Target Tujuan');
        }

        if ($request->type == 'sasaran') {
            $sasaran = Sasaran::find($id);
            if (!$sasaran) {
                return $this->errorResponse('Sasaran tidak ditemukan');
            }

            $datas = [];

            $returnIndikatorSasaran = [];
            $arrIndikatorSasaran = DB::table('pivot_master_sasaran_to_ref_sasaran')
                ->where('sasaran_id', $sasaran->id)
                ->get();
            foreach ($arrIndikatorSasaran as $indSasaran) {
                $refIndikatorSasaran = IndikatorSasaran::where('id', $indSasaran->ref_id)->first();
                $targetSasaran = [];
                $target = TargetSasaran::where('periode_id', $request->periode_id)
                    ->where('year', $request->year)
                    ->where('instance_id', $request->instance_id)
                    ->where('tujuan_id', $sasaran->tujuan_id)
                    ->where('sasaran_id', $sasaran->id)
                    ->where('ref_id', $indSasaran->ref_id)
                    ->first();

                $targetPerubahan = TargetPerubahanSasaran::where('periode_id', $request->periode_id)
                    ->where('year', $request->year)
                    ->where('instance_id', $request->instance_id)
                    ->where('tujuan_id', $sasaran->tujuan_id)
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

                $returnIndikatorSasaran[] = [
                    'id_ref' => $indSasaran->ref_id,
                    'name' => $refIndikatorSasaran->name,
                    'rumus' => $indSasaran->rumus ?? null,
                    'target' => $targetSasaran,
                ];
            }

            $datas = [
                'sasaran_id' => $sasaran->id,
                'tujuan' => $sasaran->Tujuan->RefTujuan->name ?? null,
                'sasaran' => $sasaran->RefSasaran->name,
                'indikator_sasaran' => $returnIndikatorSasaran,
            ];

            return $this->successResponse($datas, 'Data Target Tujuan');
        }
    }

    function updatePerubahan($id, Request $request)
    {
        if ($request->inputType == 'tujuan') {
            DB::beginTransaction();
            try {
                $data = Tujuan::find($request->id ?? $id);
                if (!$data) {
                    return $this->errorResponse('Tujuan tidak ditemukan');
                }

                foreach ($request->data as $input) {
                    $target = TargetPerubahanTujuan::where('tujuan_id', $data->id)
                        ->where('ref_id', $input['id_ref'])
                        ->where('year', $input['target']['year'])
                        ->first();
                    if ($target) {
                        $target->value_1 = $input['target']['month_1'];
                        $target->value_2 = $input['target']['month_2'];
                        $target->value_3 = $input['target']['month_3'];
                        $target->value_4 = $input['target']['month_4'];
                        $target->value_5 = $input['target']['month_5'];
                        $target->value_6 = $input['target']['month_6'];
                        $target->value_7 = $input['target']['month_7'];
                        $target->value_8 = $input['target']['month_8'];
                        $target->value_9 = $input['target']['month_9'];
                        $target->value_10 = $input['target']['month_10'];
                        $target->value_11 = $input['target']['month_11'];
                        $target->value_12 = $input['target']['month_12'];
                        $target->updated_by = auth()->id();
                        $target->save();
                    }
                }
                DB::commit();

                return $this->successResponse(null, 'Perubahan Target Tujuan telah diperbarui!');
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
            DB::beginTransaction();
            try {
                $data = Sasaran::find($request->id ?? $id);
                if (!$data) {
                    return $this->errorResponse('Sasaran tidak ditemukan');
                }

                foreach ($request->data as $input) {
                    $target = TargetPerubahanSasaran::where('sasaran_id', $data->id)
                        ->where('tujuan_id', $data->tujuan_id)
                        ->where('ref_id', $input['id_ref'])
                        ->where('year', $input['target']['year'])
                        ->first();
                    if ($target) {
                        $target->value_1 = $input['target']['month_1'];
                        $target->value_2 = $input['target']['month_2'];
                        $target->value_3 = $input['target']['month_3'];
                        $target->value_4 = $input['target']['month_4'];
                        $target->value_5 = $input['target']['month_5'];
                        $target->value_6 = $input['target']['month_6'];
                        $target->value_7 = $input['target']['month_7'];
                        $target->value_8 = $input['target']['month_8'];
                        $target->value_9 = $input['target']['month_9'];
                        $target->value_10 = $input['target']['month_10'];
                        $target->value_11 = $input['target']['month_11'];
                        $target->value_12 = $input['target']['month_12'];
                        $target->updated_by = auth()->id();
                        $target->save();
                    }
                }
                DB::commit();

                return $this->successResponse(null, 'Perubahan Target Sasaran telah diperbarui!');
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
