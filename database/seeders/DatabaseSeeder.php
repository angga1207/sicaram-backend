<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\Data\RenstraSeeder;
use Database\Seeders\Data\RpjmdSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\Ref\BidangSeeder;
use Database\Seeders\Ref\IndikatorKinerjaKegiatan;
use Database\Seeders\Ref\SatuanSeeder;
use Database\Seeders\Ref\UrusanSeeder;
use Database\Seeders\Ref\PeriodeSeeder;
use Database\Seeders\Ref\ProgramSeeder;
use Database\Seeders\Ref\KegiatanSeeder;
use Database\Seeders\Ref\SubKegiatanSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // RolesSeeder::class,
            // UserSeeder::class,
            // InstanceSeeder::class,

            // PeriodeSeeder::class,
            // SatuanSeeder::class,
            // UrusanSeeder::class,
            // BidangSeeder::class,
            // ProgramSeeder::class,
            // KegiatanSeeder::class,
            // SubKegiatanSeeder::class,

            // IndikatorKinerjaKegiatan::class,

            // RpjmdSeeder::class,

            RenstraSeeder::class,
        ]);
    }
}
