<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class RenstraSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/renstra');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        $targets = Http::get('https://sicaram.oganilirkab.go.id/api/public/renstraTarget');
        $targets = collect(json_decode($targets, true));
        $targets = $targets['data'];

        foreach ($datas as $data) {
            $renstra = DB::table('data_renstra')->insertGetId([
                'id' => $data['id'],
                'rpjmd_id' => $data['rpjmd_id'],
                'periode_id' => $data['periode_id'],
                'instance_id' => $data['perangkat_daerah_id'],
                'program_id' => $data['program_id'],
                'status' => $data['status'] ?? 'draft',
                'status_leader' => $data['status_leader'] ?? 'draft',
                'notes_verificator' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
                'updated_by' => $data['updated_by'],
            ]);
        }
    }
}
