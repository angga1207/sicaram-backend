<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessRecap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // public $periode, $year, $month;

    public function __construct()
    {
        // $this->periode = $periode;
        // $this->year = $year;
        // $this->month = $month;
    }

    public function handle(): void
    {
        try {
            $arrInstances = DB::table('instances')->get();
            $arrPeriodes = DB::table('ref_periode')->get();
            $arrMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

            foreach ($arrPeriodes as $periode) {
                $startYear = Carbon::parse($periode->start_date)->format('Y');
                $endYear = Carbon::parse($periode->end_date)->format('Y');
                $arrYears = range($startYear, $endYear);

                foreach ($arrYears as $year) {
                    foreach ($arrMonths as $month) {
                        foreach ($arrInstances as $instance) {

                            $paguAnggaran = DB::table('data_target_kinerja')
                                ->where('instance_id', $instance->id)
                                ->where('year', $year)
                                ->where('month', $month)
                                ->sum('pagu_sipd') ?? 0;
                            if ($paguAnggaran > 0) {

                                $realisasiAnggaran = DB::table('data_realisasi_sub_kegiatan')
                                    ->where('instance_id', $instance->id)
                                    ->where('year', $year)
                                    ->where('month', $month)
                                    ->where('status', 'verified')
                                    ->get()
                                    ->sum('realisasi_anggaran') ?? 0;

                                $persentaseRealisasi = $paguAnggaran > 0 ? ($realisasiAnggaran / $paguAnggaran) * 100 : 0;
                                $sisaAnggaran = $paguAnggaran - $realisasiAnggaran;
                                $persentaseSisa = $paguAnggaran > 0 ? ($sisaAnggaran / $paguAnggaran) * 100 : 0;

                                $realisasiKinerja = DB::table('data_realisasi_sub_kegiatan')
                                    ->where('instance_id', $instance->id)
                                    ->where('year', $year)
                                    ->where('month', $month)
                                    ->where('status', 'verified')
                                    ->get()
                                    ->avg('realisasi_kinerja') ?? 0;

                                $persentaseKinerja = $paguAnggaran > 0 ? ($realisasiKinerja / 100) * 100 : 0;

                                DB::table('instance_summary')
                                    ->updateOrInsert([
                                        'periode_id' => $periode->id,
                                        'year' => $year,
                                        'month' => $month,
                                        'instance_id' => $instance->id,
                                    ], [
                                        'pagu_anggaran' => $paguAnggaran ?? 0,
                                        'realisasi_anggaran' => $realisasiAnggaran ?? 0,
                                        'persentase_realisasi' => $persentaseRealisasi ?? 0,
                                        'sisa_anggaran' => $sisaAnggaran ?? 0,
                                        'persentase_sisa' => $persentaseSisa ?? 0,
                                        'target_kinerja' => 100,
                                        'realisasi_kinerja' => $realisasiKinerja ?? 0,
                                        'persentase_kinerja' => $persentaseKinerja ?? 0,
                                        // 'tanggal_update' => now(),
                                    ]);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
