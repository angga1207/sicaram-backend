<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RefTujuanSasaranSeeder extends Seeder
{
    public function run(): void
    {
        $arrRefTujuan = Http::get('https://sicaram.oganilirkab.go.id/api/public/refTujuan');
        $arrRefTujuan = collect(json_decode($arrRefTujuan, true));
        $arrRefTujuan = $arrRefTujuan['data'];

        $arrRefSasaran = Http::get('https://sicaram.oganilirkab.go.id/api/public/refSasaran');
        $arrRefSasaran = collect(json_decode($arrRefSasaran, true));
        $arrRefSasaran = $arrRefSasaran['data'];
        DB::beginTransaction();
        try {
            foreach ($arrRefTujuan as $tujuan) {
                DB::table('ref_tujuan')->insert([
                    'id' => $tujuan['id'],
                    'instance_id' => $tujuan['perangkat_daerah_id'],
                    'name' => str()->squish($tujuan['name']),
                    'status' => 'active',
                    'created_by' => $tujuan['created_by'] ?? 6,
                    'updated_by' => $tujuan['updated_by'],
                    'deleted_at' => $tujuan['deleted_at'] ?? null,
                ]);
            }

            foreach ($arrRefSasaran as $sasaran) {
                DB::table('ref_sasaran')->insert([
                    'id' => $sasaran['id'],
                    'instance_id' => $sasaran['perangkat_daerah_id'],
                    'name' => str()->squish($sasaran['name']),
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
