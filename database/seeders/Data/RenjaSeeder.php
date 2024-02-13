<?php

namespace Database\Seeders\Data;

use App\Models\Caram\RenjaKegiatan;
use App\Models\Caram\RenjaSubKegiatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RenjaSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            DB::table('data_renja')->truncate();
            DB::table('data_renja_detail_kegiatan')->truncate();
            DB::table('data_renja_detail_sub_kegiatan')->truncate();

            $datas = Http::get('https://sicaram.oganilirkab.go.id/api/public/renja');
            $datas = collect(json_decode($datas, true));
            $datas = $datas['data'];

            $targets = Http::get('https://sicaram.oganilirkab.go.id/api/public/renjaTarget');
            $targets = collect(json_decode($targets, true));
            $targets = $targets['data'];


            foreach ($datas as $key => $data) {
                $renja = DB::table('data_renja')->insertGetId([
                    'id' => $data['id'],
                    'rpjmd_id' => $data['rpjmd_id'],
                    'renstra_id' => $data['renstra_id'],
                    // 'periode_id' => $data['periode_id'],
                    'periode_id' => 1,
                    'instance_id' => $data['perangkat_daerah_id'],
                    'program_id' => $data['program_id'],
                    'status' => $data['status'] ?? 'draft',
                    'status_leader' => $data['status_leader'] ?? 'draft',
                    'notes_verificator' => $data['notes'] ?? null,
                    'percent_anggaran' => 100,
                    'percent_kinerja' => 100,
                    'deleted_at' => $data['deleted_at'] ?? null,
                    'created_by' => $data['created_by'],
                    'updated_by' => $data['updated_by'],
                ]);

                $trgs = collect($targets)
                    // ->where('renstra_id', $data['renstra_id'])
                    // ->where('renstra_perubahan_id', $data['id'])
                    ->where('program_id', $data['program_id'])
                    ->all();
                foreach ($trgs as $target) {
                    if ($target['sub_kegiatan_id'] == null) {
                        $totalAnggaran = 0;
                        $anggarans = json_decode($target['anggaran_json'], true);
                        if (is_array($anggarans)) {
                            foreach ($anggarans as $year => $ang) {
                                $totalAnggaran += $ang;
                            }
                        }
                        if ($target['anggaran_detail_json'] || $target['anggaran_detail_json'] != '[]') {
                            $anggaranModal = json_decode($target['anggaran_detail_json'], true)[0]['anggaranModal'] ?? 0;
                            $anggaranOperasi = json_decode($target['anggaran_detail_json'], true)[0]['anggaranOperasi'] ?? 0;
                            $anggaranTransfer = json_decode($target['anggaran_detail_json'], true)[0]['anggaranTransfer'] ?? 0;
                            $anggaranTidakTerduga = json_decode($target['anggaran_detail_json'], true)[0]['anggaranTidakTerduga'] ?? 0;
                            if ($anggaranOperasi == 0) {
                                $anggaranOperasi = $totalAnggaran;
                            }
                        }

                        $renjaKegiatan = DB::table('data_renja_detail_kegiatan')
                            ->where('renstra_id', $data['renstra_id'])
                            ->where('renja_id', $renja)
                            ->where('kegiatan_id', $target['kegiatan_id'])
                            ->where('year', $target['year'])
                            ->first();
                        if (!$renjaKegiatan) {
                            $renjaKegiatan = DB::table('data_renja_detail_kegiatan')
                                ->insertGetId([
                                    'renstra_id' => $data['renstra_id'],
                                    'renja_id' => $renja ?? $target['renstra_perubahan_id'],
                                    'program_id' => $target['program_id'],
                                    'kegiatan_id' => $target['kegiatan_id'],
                                    'anggaran_json' => null,
                                    'anggaran_detail_json' => null,
                                    'kinerja_json' => $target['target_json'] == [] ? NULL : $target['target_json'],
                                    'satuan_json' => $target['satuan_json'] == [] ? NULL : $target['satuan_json'],
                                    'year' => $target['year'],
                                    'anggaran_modal' => $anggaranModal ?? 0,
                                    'anggaran_operasi' => $anggaranOperasi ?? 0,
                                    'anggaran_transfer' => $anggaranTransfer ?? 0,
                                    'anggaran_tidak_terduga' => $anggaranTidakTerduga ?? 0,
                                    'total_anggaran' => $totalAnggaran,
                                    'percent_anggaran' => 100,
                                    'percent_kinerja' => 100,
                                    'status' => 'active',
                                    'created_by' => $target['created_by'],
                                    'updated_by' => $target['updated_by'],
                                    'deleted_at' => $target['deleted_at'] ?? null,
                                ]);
                        } else {
                            $renjaKegiatan = DB::table('data_renja_detail_kegiatan')
                                ->where('renstra_id', $data['renstra_id'])
                                ->where('renja_id', $renja)
                                ->where('kegiatan_id', $target['kegiatan_id'])
                                ->where('year', $target['year'])
                                ->update([
                                    'renstra_id' => $data['renstra_id'],
                                    'renja_id' => $renja ?? $target['renstra_perubahan_id'],
                                    'program_id' => $target['program_id'],
                                    'kegiatan_id' => $target['kegiatan_id'],
                                    'anggaran_json' => null,
                                    'anggaran_detail_json' => null,
                                    'kinerja_json' => $target['target_json'] == [] ? NULL : $target['target_json'],
                                    'satuan_json' => $target['satuan_json'] == [] ? NULL : $target['satuan_json'],
                                    'year' => $target['year'],
                                    // 'anggaran_modal' => $anggaranModal ?? 0,
                                    // 'anggaran_operasi' => $anggaranOperasi ?? 0,
                                    // 'anggaran_transfer' => $anggaranTransfer ?? 0,
                                    // 'anggaran_tidak_terduga' => $anggaranTidakTerduga ?? 0,
                                    'anggaran_modal' => 0,
                                    'anggaran_operasi' => 0,
                                    'anggaran_transfer' => 0,
                                    'anggaran_tidak_terduga' => 0,
                                    'total_anggaran' => $totalAnggaran,
                                    'percent_anggaran' => 100,
                                    'percent_kinerja' => 100,
                                    'status' => 'active',
                                    'created_by' => $target['created_by'],
                                    'updated_by' => $target['updated_by'],
                                    'deleted_at' => $target['deleted_at'] ?? null,
                                ]);
                        }
                    } else {
                        $totalAnggaran = 0;
                        $anggarans = json_decode($target['anggaran_json'], true);
                        if (is_array($anggarans)) {
                            foreach ($anggarans as $year => $ang) {
                                $totalAnggaran += $ang;
                            }
                        }
                        if ($target['anggaran_detail_json'] != NULL) {
                            $anggaranModal = json_decode($target['anggaran_detail_json'], true)[0]['anggaranModal'] ?? 0;
                            $anggaranOperasi = json_decode($target['anggaran_detail_json'], true)[0]['anggaranOperasi'] ?? 0;
                            $anggaranTransfer = json_decode($target['anggaran_detail_json'], true)[0]['anggaranTransfer'] ?? 0;
                            $anggaranTidakTerduga = json_decode($target['anggaran_detail_json'], true)[0]['anggaranTidakTerduga'] ?? 0;
                            if ($anggaranOperasi == 0) {
                                $anggaranOperasi = $totalAnggaran;
                            }
                        }
                        $parents = DB::table('data_renja_detail_kegiatan')
                            ->where('program_id', $target['program_id'])
                            ->where('kegiatan_id', $target['kegiatan_id'])
                            ->where('year', $target['year'])
                            ->get();
                        if ($parents->count() > 0) {
                            $parentId = $parents->first();
                            DB::table('data_renja_detail_sub_kegiatan')
                                ->where('renstra_id', $data['renstra_id'])
                                ->where('renja_id', $renja)
                                ->where('program_id', $target['program_id'])
                                ->where('kegiatan_id', $target['kegiatan_id'])
                                ->where('sub_kegiatan_id', $target['sub_kegiatan_id'])
                                ->where('year', $target['year'])
                                ->where('id', '!=', $parentId->id)
                                ->delete();
                        }

                        DB::table('data_renja_detail_sub_kegiatan')
                            ->insertGetId([
                                'renstra_id' => $data['renstra_id'],
                                'renja_id' => $renja ?? $target['renstra_perubahan_id'],
                                'parent_id' => $parentId->id ?? $parentId['id'],
                                'program_id' => $target['program_id'],
                                'kegiatan_id' => $target['kegiatan_id'],
                                'sub_kegiatan_id' => $target['sub_kegiatan_id'],
                                'anggaran_json' => null,
                                'anggaran_detail_json' => null,
                                'kinerja_json' => $target['target_json'] == [] ? NULL : $target['target_json'],
                                'satuan_json' => $target['satuan_json'] == [] ? NULL : $target['satuan_json'],
                                'year' => $target['year'],
                                'anggaran_modal' => $anggaranModal ?? 0,
                                'anggaran_operasi' => $anggaranOperasi ?? 0,
                                'anggaran_transfer' => $anggaranTransfer ?? 0,
                                'anggaran_tidak_terduga' => $anggaranTidakTerduga ?? 0,
                                'total_anggaran' => $totalAnggaran,
                                'percent_anggaran' => 100,
                                'percent_kinerja' => 100,
                                'status' => 'active',
                                'created_by' => $target['created_by'],
                                'updated_by' => $target['updated_by'],
                                'deleted_at' => $target['deleted_at'] ?? null,
                            ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
        }
    }
}
