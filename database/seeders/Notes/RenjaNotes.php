<?php

namespace Database\Seeders\Notes;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RenjaNotes extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/renjaLog');
            $datas = collect(json_decode($datas, true));
            $datas = $datas['data'];

            foreach ($datas as $data) {
                DB::table('notes_renja')->insert([
                    'renja_id' => $data['renstra_perubahan_id'],
                    'user_id' => $data['user_id'],
                    'status' => $data['status'],
                    'type' => $data['type'],
                    'message' => $data['notes'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                    'deleted_at' => $data['deleted_at'],
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
