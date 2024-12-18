<?php

namespace App\Models\Data;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Data\Realisasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\TargetKinerjaRincian;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiRincian extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi_rincian';

    protected $fillable = [
        'periode_id',
        'realisasi_id',
        'target_rincian_id',
        'title',
        'pagu_sipd',
        'anggaran',
        'kinerja',
        'persentase_kinerja',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'title',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($realisasiRincian) {
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
                    'action' => 'realisasi-rincian@create',
                    'id' => $realisasiRincian->id,
                    'description' => auth()->user()->fullname . ' menambahkan data realisasi rincian ' . $realisasiRincian->name,
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

        // static::updating(function ($realisasiRincian) {
        //     if (auth()->check()) {
        //         $newLogs = [];
        //         $oldLogs = DB::table('log_users')
        //             ->where('date', date('Y-m-d'))
        //             ->where('user_id', auth()->id())
        //             ->first();
        //         if ($oldLogs) {
        //             $newLogs = json_decode($oldLogs->logs);
        //         }
        //         $newLogs[] = [
        //             'action' => 'realisasi-rincian@update',
        //             'id' => $realisasiRincian->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data realisasi rincian ' . $realisasiRincian->name,
        //             'created_at' => date('Y-m-d H:i:s'),
        //             'updated_at' => date('Y-m-d H:i:s'),
        //         ];
        //         DB::table('log_users')
        //             ->updateOrInsert([
        //                 'date' => date('Y-m-d'),
        //                 'user_id' => auth()->id(),
        //                 'ip_address' => request()->ip(),
        //                 'user_agent' => request()->header('User-Agent'),
        //             ], [
        //                 'logs' => json_encode($newLogs),
        //                 'created_at' => date('Y-m-d H:i:s'),
        //                 'updated_at' => date('Y-m-d H:i:s'),
        //             ]);
        //     }
        // });


        static::deleting(function ($realisasiRincian) {
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
                    'action' => 'realisasi-rincian@delete',
                    'id' => $realisasiRincian->id,
                    'description' => auth()->user()->fullname . ' menghapus data realisasi rincian ' . $realisasiRincian->name,
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

    function Realisasi()
    {
        return $this->belongsTo(Realisasi::class, 'realisasi_id');
    }

    function TargetRincian()
    {
        return $this->belongsTo(TargetKinerjaRincian::class, 'target_rincian_id');
    }

    function Keterangan()
    {
        return $this->hasMany(RealisasiKeterangan::class, 'parent_id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
