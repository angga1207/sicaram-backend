<?php

namespace Database\Seeders\Ref;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PeriodeSeeder extends Seeder
{
    public function run(): void
    {
        $datas = [
            'name' => '2022-2026',
            'start_date' => '2022-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('ref_periode')->insert($datas);
    }
}
