<?php

namespace App\Models\Data;

use App\Models\User;
use App\Models\Ref\Satuan;
use App\Traits\Searchable;
use App\Models\Data\Realisasi;
use Illuminate\Support\Facades\DB;
use App\Models\Data\RealisasiRincian;
use Illuminate\Database\Eloquent\Model;
use App\Models\Data\TargetKinerjaKeterangan;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiKeterangan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi_keterangan';

    protected $fillable = [
        'periode_id',
        'realisasi_id',
        'target_keterangan_id',
        'parent_id',
        'title',
        'koefisien',
        'satuan_id',
        'satuan_name',
        'harga_satuan',
        'ppn',
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

        static::creating(function ($realisasiKeterangan) {
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
                    'action' => 'realisasi-keterangan@update',
                    'id' => $realisasiKeterangan->id,
                    'description' => auth()->user()->fullname . ' memperbarui data realisasi keterangan ' . $realisasiKeterangan->name,
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

        // static::updating(function ($realisasiKeterangan) {
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
        //             'action' => 'realisasi-keterangan@update',
        //             'id' => $realisasiKeterangan->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data realisasi keterangan ' . $realisasiKeterangan->name,
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


        static::deleting(function ($realisasiKeterangan) {
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
                    'action' => 'realisasi-keterangan@delete',
                    'id' => $realisasiKeterangan->id,
                    'description' => auth()->user()->fullname . ' menghapus data realisasi keterangan ' . $realisasiKeterangan->name,
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

    function TargetKeterangan()
    {
        return $this->belongsTo(TargetKinerjaKeterangan::class, 'target_keterangan_id');
    }

    function Parent()
    {
        return $this->belongsTo(RealisasiRincian::class, 'parent_id');
    }

    function Satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
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
