<?php

namespace Database\Seeders\Ref;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IndikatorTujuanSasaran extends Seeder
{
    public function run(): void
    {
        $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/indikatorTujuanSasaran');
        $datas = collect(json_decode($datas, true));
        $datas = $datas['data'];
        DB::beginTransaction();
        try {
            $dataTujuan = $datas['tujuan'];
            foreach ($dataTujuan as $tujuan) {
                $data = DB::table('ref_indikator_tujuan')->insert([
                    'id' => $tujuan['id'],
                    'name' => str()->squish($tujuan['name']),
                    'instance_id' => $tujuan['perangkat_daerah_id'],
                    'status' => 'active',
                    'created_by' => $tujuan['created_by'] ?? 6,
                    'updated_by' => $tujuan['updated_by'],
                    'deleted_at' => $tujuan['deleted_at'] ?? null,
                ]);
            }
            $dataSasaran = $datas['sasaran'];
            foreach ($dataSasaran as $sasaran) {
                $data = DB::table('ref_indikator_sasaran')->insert([
                    'id' => $sasaran['id'],
                    'name' => str()->squish($sasaran['name']),
                    'instance_id' => $sasaran['perangkat_daerah_id'],
                    'status' => 'active',
                    'created_by' => $sasaran['created_by'] ?? 6,
                    'updated_by' => $sasaran['updated_by'],
                    'deleted_at' => $sasaran['deleted_at'] ?? null,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }
}
