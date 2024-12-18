<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Ref\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apbd extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_apbd';

    protected $fillable = [
        'rpjmd_id',
        'renstra_id',
        'renja_id',
        'periode_id',
        'instance_id',
        'program_id',
        'total_anggaran',
        'total_kinerja',
        'percent_anggaran',
        'percent_kinerja',
        'status_leader',
        'status',
        'notes_verificator',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apbd) {
            if (auth()->check()) {
                $newLogs = [];
                $oldLogs = DB::table('log_users')
                    ->where('date', date('Y-m-d'))
                    ->where('user_id', auth()->id())
                    ->first();
                if ($oldLogs) {
                    $newLogs = json_decode($oldLogs->logs);
                }
                $newLogs[] = [
                    'action' => 'apbd@create',
                    'id' => $apbd->id,
                    'description' => auth()->user()->fullname . ' menambahkan data apbd ' . $apbd->Program->name,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                DB::table('log_users')
                    ->updateOrInsert([
                        'date' => date('Y-m-d'),
                        'user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('User-Agent'),
                    ], [
                        'logs' => json_encode($newLogs),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        });

        static::updating(function ($apbd) {
            if (auth()->check()) {
                $newLogs = [];
                $oldLogs = DB::table('log_users')
                    ->where('date', date('Y-m-d'))
                    ->where('user_id', auth()->id())
                    ->first();
                if ($oldLogs) {
                    $newLogs = json_decode($oldLogs->logs);
                }
                $newLogs[] = [
                    'action' => 'apbd@update',
                    'id' => $apbd->id,
                    'description' => auth()->user()->fullname . ' memperbarui data apbd ' . $apbd->Program->name,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                DB::table('log_users')
                    ->updateOrInsert([
                        'date' => date('Y-m-d'),
                        'user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('User-Agent'),
                    ], [
                        'logs' => json_encode($newLogs),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        });


        static::deleting(function ($apbd) {
            if (auth()->check()) {
                $newLogs = [];
                $oldLogs = DB::table('log_users')
                    ->where('date', date('Y-m-d'))
                    ->where('user_id', auth()->id())
                    ->first();
                if ($oldLogs) {
                    $newLogs = json_decode($oldLogs->logs);
                }
                $newLogs[] = [
                    'action' => 'apbd@delete',
                    'id' => $apbd->id,
                    'description' => auth()->user()->fullname . ' menghapus data apbd ' . $apbd->Program->name,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                DB::table('log_users')
                    ->updateOrInsert([
                        'date' => date('Y-m-d'),
                        'user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('User-Agent'),
                    ], [
                        'logs' => json_encode($newLogs),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        });
    }

    function calculateTotalAnggaranFromSubKegiatans()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->total_anggaran;
        }
        return $total;
    }

    function calculateAnggaranModalFromSubKegiatans()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->anggaran_modal;
        }
        return $total;
    }

    function calculateAnggaranOperasiFromSubKegiatans()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->anggaran_operasi;
        }
        return $total;
    }

    function calculateAnggaranTransferFromSubKegiatans()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->anggaran_transfer;
        }
        return $total;
    }

    function calculateAnggaranTidakTerdugaFromSubKegiatans()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->anggaran_tidak_terduga;
        }
        return $total;
    }

    function calculateAllAnggaranFromSubKegiatans()
    {
        $data = [
            'total_anggaran' => $this->calculateTotalAnggaranFromSubKegiatans(),
            'anggaran_modal' => $this->calculateAnggaranModalFromSubKegiatans(),
            'anggaran_operasi' => $this->calculateAnggaranOperasiFromSubKegiatans(),
            'anggaran_transfer' => $this->calculateAnggaranTransferFromSubKegiatans(),
            'anggaran_tidak_terduga' => $this->calculateAnggaranTidakTerdugaFromSubKegiatans(),
        ];
        return $data;
    }

    function calculetePercentKinerja()
    {
        $total = 0;
        foreach ($this->detailKegiatan as $detail) {
            $total += $detail->percent_kinerja;
        }

        $average = $total / count($this->detailKegiatan);
        return $average;
    }


    function detailKegiatan()
    {
        return $this->hasMany(ApbdKegiatan::class, 'apbd_id', 'id');
    }

    function RPJMD()
    {
        return $this->belongsTo(RPJMD::class, 'rpjmd_id', 'id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
