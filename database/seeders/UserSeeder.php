<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/user');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];

        foreach ($datas as $data) {
            $role = $data['roles'][0]['name'];
            $roles = DB::table('roles')->where('display_name', $role)->first();
            // dd($data);
            // dd($roles->id);
            DB::table('users')->insert([
                'id' => $data['id'],
                'fullname' => $data['fullname'],
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'username' => $data['username'],
                'email' => $data['email'],
                'photo' => $data['photo'],
                'instance_id' => $data['instance_id'] ?? NULL,
                'instance_type' => $data['instance_type'] ?? NULL,
                'password' => $data['password'],
                // 'remember_token' => $data['remember_token'],
                'fcm_token' => $data['fcm_token'],
                'role_id' => $roles->id ?? null,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);
        }
    }
}
