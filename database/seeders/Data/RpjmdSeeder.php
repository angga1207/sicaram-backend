<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RpjmdSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/rpjmd');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        $indicators = Http::get('https://sicaram.oganilirkab.go.id/api/public/rpjmdIndikator');
        $indicators = collect(json_decode($indicators, true));
        $indicators = $indicators['data'];

        foreach ($datas as $data) {
            $rpjmd = DB::table('data_rpjmd')->insertGetId([
                'id' => $data['id'],
                'periode_id' => 1,
                'instance_id' => $data['perangkat_daerah_id'],
                'program_id' => $data['program_id'],
                'status' => 'active',
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
            ]);

            foreach (json_decode($data['anggaran'], true) as $year => $ang) {
                DB::table('data_rpjmd_anggaran')->insert([
                    'rpjmd_id' => $rpjmd,
                    'year' => $year,
                    'anggaran' => $ang,
                    'status' => 'active',
                    'created_by' => $data['created_by'],
                    'updated_by' => $data['updated_by'],
                ]);
            }

            $inds = collect($indicators)->where('rpjmd_id', $data['id'])->all();
            foreach ($inds as $ind) {
                DB::table('data_rpjmd_indikator')->insert([
                    'rpjmd_id' => $rpjmd,
                    'name' => $ind['indikator'],
                    'year' => $ind['year'],
                    'value' => $ind['value'],
                    'satuan_id' => $ind['satuan_id'],
                    'status' => 'active',
                    'created_by' => $ind['created_by'],
                    'updated_by' => $ind['updated_by'],
                ]);
            }
        }
    }
}
