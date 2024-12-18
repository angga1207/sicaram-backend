<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Models\Instance;
use App\Traits\Searchable;
use App\Models\Ref\RefSasaran;
use Illuminate\Support\Facades\DB;
use App\Models\Ref\IndikatorSasaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sasaran extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'master_sasaran';

    protected $fillable = [
        'id',
        'instance_id',
        'tujuan_id',
        'parent_id',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sasaran) {
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
                    'action' => 'sasaran@create',
                    'id' => $sasaran->id,
                    'description' => auth()->user()->fullname . ' menambahkan data sasaran',
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

        // static::updating(function ($sasaran) {
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
        //             'action' => 'sasaran@update',
        //             'id' => $sasaran->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data sasaran ',
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


        static::deleting(function ($sasaran) {
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
                    'action' => 'sasaran@delete',
                    'id' => $sasaran->id,
                    'description' => auth()->user()->fullname . ' menghapus data sasaran ',
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

    function RefSasaran()
    {
        return $this->hasOne(RefSasaran::class, 'id', 'ref_sasaran_id');
    }

    function RefIndikatorSasaran()
    {
        return $this->belongsToMany(IndikatorSasaran::class, 'pivot_master_sasaran_to_ref_sasaran', 'sasaran_id', 'ref_id');
    }

    function Parent()
    {
        return $this->belongsTo(Sasaran::class, 'parent_id');
    }

    function Tujuan()
    {
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
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
