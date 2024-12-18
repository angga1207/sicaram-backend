<?php

namespace Database\Seeders\Ref;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MasterTujuanSasaran extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/dataTujuanSasaran');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];
        DB::beginTransaction();
        try {
            foreach ($datas as $data) {
                // Tujuan Sasaran Kabupaten Start
                if ($data['perangkat_daerah_id'] === null || $data['perangkat_daerah_id'] === 0) {
                    if ($data['parent_id'] === null && $data['ref_sasaran_id'] === null) {

                        DB::table('master_tujuan')->updateOrInsert([
                            'id' => $data['id'],
                        ], [
                            'instance_id' => null,
                            'parent_id' => $data['parent_id'],
                            'ref_tujuan_id' => $data['ref_tujuan_id'],
                            'status' => 'active',
                            'created_at' => $data['created_at'],
                            'updated_at' => $data['updated_at'],
                            'deleted_at' => $data['deleted_at'],
                            'created_by' => 6,
                        ]);

                        $indicators = json_decode($data['ref_indikator_tujuan_id'], true);
                        if (is_array($indicators)) {
                            foreach ($indicators as $indicator) {
                                $rumus = $data['rumus_tujuan'] ? json_decode($data['rumus_tujuan'], true)[$indicator] : null;
                                DB::table('pivot_master_tujuan_to_ref_tujuan')->insert([
                                    'tujuan_id' => $data['id'],
                                    'ref_id' => $indicator,
                                    'rumus' => $rumus ? str()->squish($rumus) : null,
                                ]);
                            }
                        }
                        //  else {
                        //     $indicator = $data['ref_tujuan_id'];
                        //     DB::table('pivot_master_tujuan_to_ref_tujuan')->insert([
                        //         'tujuan_id' => $data['id'],
                        //         'ref_id' => $indicator,
                        //         'rumus' => str()->squish($data['rumus_tujuan_old']),
                        //     ]);
                        // }
                    } elseif ($data['parent_id'] !== null && $data['ref_sasaran_id'] !== null) {
                        DB::table('master_sasaran')->updateOrInsert([
                            'id' => $data['id'],
                        ], [
                            'instance_id' => null,
                            'tujuan_id' => $data['parent_id'],
                            'parent_id' => $data['parent_id_sasaran'] ?? null,
                            'ref_sasaran_id' => $data['ref_sasaran_id'],
                            'status' => 'active',
                            'created_at' => $data['created_at'],
                            'updated_at' => $data['updated_at'],
                            'deleted_at' => $data['deleted_at'],
                            'created_by' => 6,
                        ]);

                        $indicators = json_decode($data['ref_indikator_sasaran_id'], true);
                        if ($indicators) {
                            if (is_array($indicators)) {
                                foreach ($indicators as $indicator) {
                                    $rumus = $data['rumus_sasaran'] ? json_decode($data['rumus_sasaran'], true)[$indicator] : null;
                                    if ($indicator === 0) {
                                        $indicator = $data['ref_sasaran_id'];
                                    }
                                    DB::table('pivot_master_sasaran_to_ref_sasaran')->insert([
                                        'sasaran_id' => $data['id'],
                                        'ref_id' => $indicator,
                                        'rumus' => $rumus ? str()->squish($rumus) : null,
                                    ]);
                                }
                            }
                            //  else {
                            //     $indicator = $data['ref_sasaran_id'];
                            //     DB::table('pivot_master_sasaran_to_ref_sasaran')->insert([
                            //         'sasaran_id' => $data['id'],
                            //         'ref_id' => $indicator,
                            //         'rumus' => str()->squish($data['rumus_sasaran_old']),
                            //     ]);
                            // }
                        }
                    }
                }
                // Tujuan Sasaran Kabupaten End

                // Tujuan Sasaran OPD Start
                if ($data['perangkat_daerah_id'] !== null || $data['perangkat_daerah_id'] !== 0) {
                    $instanceCheck = DB::table('instances')->where('id', $data['perangkat_daerah_id'])->first();
                    if ($instanceCheck) {
                        if ($data['ref_sasaran_id'] === null) {
                            DB::table('master_tujuan')->updateOrInsert([
                                'id' => $data['id'],
                            ], [
                                'instance_id' => $instanceCheck->id,
                                'parent_id' => $data['parent_id'],
                                'ref_tujuan_id' => $data['ref_tujuan_id'],
                                'status' => 'active',
                                'created_at' => $data['created_at'],
                                'updated_at' => $data['updated_at'],
                                'deleted_at' => $data['deleted_at'],
                                'created_by' => 6,
                            ]);
                            $indicators = json_decode($data['ref_indikator_tujuan_id'], true);
                            if (is_array($indicators)) {
                                foreach ($indicators as $indicator) {
                                    $rumus = $data['rumus_tujuan'] ? json_decode($data['rumus_tujuan'], true)[$indicator] : null;
                                    DB::table('pivot_master_tujuan_to_ref_tujuan')->insert([
                                        'tujuan_id' => $data['id'],
                                        'ref_id' => $indicator,
                                        'rumus' => $rumus ? str()->squish($rumus) : null,
                                    ]);
                                }
                            }
                            // else {
                            //     $indicator = $data['ref_tujuan_id'];
                            //     DB::table('pivot_master_tujuan_to_ref_tujuan')->insert([
                            //         'tujuan_id' => $data['id'],
                            //         'ref_id' => $indicator,
                            //         'rumus' => str()->squish($data['rumus_tujuan_old']),
                            //     ]);
                            // }
                        } elseif ($data['ref_sasaran_id'] !== null) {
                            $sasaranCheck = DB::table('master_sasaran')->where('id', $data['parent_id_sasaran'])->first();
                            DB::table('master_sasaran')->updateOrInsert([
                                'id' => $data['id'],
                            ], [
                                'instance_id' => $instanceCheck->id,
                                'tujuan_id' => $data['parent_id'],
                                'parent_id' => $sasaranCheck->id ?? null,
                                'ref_sasaran_id' => $data['ref_sasaran_id'],
                                'status' => 'active',
                                'created_at' => $data['created_at'],
                                'updated_at' => $data['updated_at'],
                                'deleted_at' => $data['deleted_at'],
                                'created_by' => 6,
                            ]);

                            $indicators = json_decode($data['ref_indikator_sasaran_id'], true);
                            if ($indicators) {
                                if (is_array($indicators)) {
                                    foreach ($indicators as $indicator) {
                                        $rumus = $data['rumus_sasaran'] ? (json_decode($data['rumus_sasaran'], true)[$indicator]) ?? null : null;
                                        if ($indicator === 0) {
                                            $indicator = $data['ref_sasaran_id'];
                                        }
                                        DB::table('pivot_master_sasaran_to_ref_sasaran')->insert([
                                            'sasaran_id' => $data['id'],
                                            'ref_id' => $indicator,
                                            'rumus' => $rumus ? str()->squish($rumus) : null,
                                        ]);
                                    }
                                }
                                // else {
                                //     $indicator = $data['ref_sasaran_id'];
                                //     DB::table('pivot_master_sasaran_to_ref_sasaran')->insert([
                                //         'sasaran_id' => $data['id'],
                                //         'ref_id' => $indicator,
                                //         'rumus' => str()->squish($data['rumus_sasaran_old']),
                                //     ]);
                                // }
                            }
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }
}
