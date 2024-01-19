<?php

namespace Database\Seeders\Ref;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/program');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        foreach ($datas as $data) {
            $fullcode = null;
            $bidang = DB::table('ref_bidang_urusan')->where('id', $data['bidang_urusan_id'])->first();
            if ($bidang) {
                $fullcode = $bidang->fullcode . '.' . $data['code'];
            }
            $data = [
                'id' => $data['id'],
                'urusan_id' => $data['urusan_id'],
                'bidang_id' => $data['bidang_urusan_id'],
                'instance_id' => $data['perangkat_daerah_id'],
                'name' => Str::squish($data['name']),
                'code' => $data['code'],
                'fullcode' => $fullcode,
                'description' => $data['description'],
                'status' => $data['status'],
                'periode_id' => 1,
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ];

            DB::table('ref_program')->insert($data);
        }
    }
}
