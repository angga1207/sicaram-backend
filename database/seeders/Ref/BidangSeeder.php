<?php

namespace Database\Seeders\Ref;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BidangSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/bidang');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        foreach ($datas as $data) {
            $fullcode = null;
            $urusan = DB::table('ref_urusan')->where('id', $data['urusan_id'])->first();
            if ($urusan) {
                $fullcode = $urusan->code . '.' . $data['code'];
            }
            $data = [
                'id' => $data['id'],
                'urusan_id' => $data['urusan_id'],
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

            DB::table('ref_bidang_urusan')->insert($data);
        }
    }
}
