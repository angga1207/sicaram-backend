<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InstanceSeeder extends Seeder
{
    public function run(): void
    {
        $datas = [];
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/opd');
        $datas = collect(json_decode($datas, true));
        $instances = $datas['data'];

        foreach ($instances as $instance) {
            // dd($instance);
            DB::table('instances')->insert([
                // 'id' => $instance['id'],
                'id_eoffice' => $instance['id_eoffice'],
                'name' => $instance['name'],
                'alias' => $instance['alias'],
                'code' => $instance['code'],
                'description' => $instance['description'],
                'website' => $instance['website'],
                'facebook' => $instance['facebook'],
                'youtube' => $instance['youtube'],
                'logo' => $instance['photo'],
                'created_at' => $instance['created_at'],
                'updated_at' => $instance['updated_at'],
            ]);
        }
    }
}
