<?php

namespace Database\Seeders\Ref;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class KegiatanSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/kegiatan');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        foreach ($datas as $data) {
            $fullcode = null;
            $program = DB::table('ref_program')->where('id', $data['program_id'])->first();
            if ($program) {
                $fullcode = $program->fullcode . '.' . $data['code'];
            }
            $data = [
                'id' => $data['id'],
                'urusan_id' => $data['urusan_id'],
                'bidang_id' => $data['bidang_urusan_id'],
                'program_id' => $data['program_id'],
                'instance_id' => $data['perangkat_daerah_id'],
                'name' => Str::squish($data['name']),
                'code_1' => $data['code'],
                'code_2' => $data['code_2'],
                'fullcode' => $fullcode,
                'description' => $data['description'],
                'status' => $data['status'],
                'periode_id' => 1,
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ];

            DB::table('ref_kegiatan')->insert($data);
        }
    }
}
