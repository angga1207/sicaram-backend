<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Models\Instance;
use App\Traits\Searchable;
use App\Models\Ref\RefTujuan;
use Illuminate\Support\Facades\DB;
use App\Models\Ref\IndikatorTujuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tujuan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'master_tujuan';

    protected $fillable = [
        'id',
        'instance_id',
        'parent_id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
    ];

    // searchable
    protected $searchable = [
        'Instance.name',
        // 'Parent.name',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tujuan) {
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
                    'action' => 'tujuan@create',
                    'id' => $tujuan->id,
                    'description' => auth()->user()->fullname . ' menambahkan data tujuan ',
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

        // static::updating(function ($tujuan) {
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
        //             'action' => 'tujuan@update',
        //             'id' => $tujuan->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data tujuan ',
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


        static::deleting(function ($tujuan) {
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
                    'action' => 'tujuan@delete',
                    'id' => $tujuan->id,
                    'description' => auth()->user()->fullname . ' menghapus data tujuan ',
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

    function RefTujuan()
    {
        return $this->hasOne(RefTujuan::class, 'id', 'ref_tujuan_id');
    }

    function RefIndikatorTujuan()
    {
        return $this->belongsToMany(IndikatorTujuan::class, 'pivot_master_tujuan_to_ref_tujuan', 'tujuan_id', 'ref_id');
    }

    function Parent()
    {
        return $this->belongsTo(Tujuan::class, 'parent_id');
    }

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
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
