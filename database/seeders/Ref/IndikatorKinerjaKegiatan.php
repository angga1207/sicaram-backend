<?php

namespace Database\Seeders\Ref;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IndikatorKinerjaKegiatan extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/indikatorKegiatan');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        foreach ($datas as $data) {
            if ($data['type'] == 'main') {
                $insert = DB::table('con_indikator_kinerja_kegiatan')->insertGetId([
                    'instance_id' => $data['perangkat_daerah_id'],
                    'program_id' => $data['program_id'],
                    'kegiatan_id' => $data['kegiatan_id'],
                    'status' => 'active', // 'active', 'inactive'
                    'created_by' => $data['created_by'],
                    'updated_by' => $data['updated_by'],
                ]);

                if ($data['indikator_kinerja_kegiatan']) {
                    $indis = json_decode($data['indikator_kinerja_kegiatan'], true);
                    if (is_array($indis)) {
                        foreach ($indis as $indikator) {
                            DB::table('ref_indikator_kinerja_kegiatan')->insert([
                                'pivot_id' => $insert,
                                'name' => $indikator,
                                'status' => 'active', // 'active', 'inactive'
                                'created_by' => $data['created_by'],
                                'updated_by' => $data['updated_by'],
                            ]);
                        }
                    }
                }
            }

            if ($data['type'] == 'sub') {
                $insert = DB::table('con_indikator_kinerja_sub_kegiatan')->insertGetId([
                    'instance_id' => $data['perangkat_daerah_id'],
                    'program_id' => $data['program_id'],
                    'kegiatan_id' => $data['kegiatan_id'],
                    'sub_kegiatan_id' => $data['sub_kegiatan_id'],
                    'status' => 'active', // 'active', 'inactive'
                    'created_by' => $data['created_by'],
                    'updated_by' => $data['updated_by'],
                ]);

                if ($data['indikator_kinerja_kegiatan']) {
                    $indis = json_decode($data['indikator_kinerja_kegiatan'], true);
                    if (is_array($indis)) {
                        foreach ($indis as $indikator) {
                            DB::table('ref_indikator_kinerja_sub_kegiatan')->insert([
                                'pivot_id' => $insert,
                                'name' => str()->squish($indikator),
                                'status' => 'active', // 'active', 'inactive'
                                'created_by' => $data['created_by'],
                                'updated_by' => $data['updated_by'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
