<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $webRoles = [
            'Developer',
            'Super Admin',
            'Admin Bappeda',
            'Admin BPKAD',
            'Admin Dalbang',
            'Verifikator Bappeda',
            'Verifikator BPKAD',
            'Verifikator Dalbang',
            'Perangkat Daerah',
            'Pengawas',
            'Guest',
        ];

        foreach ($webRoles as $role) {
            DB::table('roles')->insert([
                'name' => strtolower(str_replace(' ', '-', $role)),
                'display_name' => $role,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
