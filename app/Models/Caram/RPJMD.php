<?php

namespace App\Models\Caram;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RPJMDAnggaran;
use App\Models\Caram\RPJMDIndikator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RPJMD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_rpjmd';

    protected $fillable = [
        'periode_id',
        'instance_id',
        'program_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rpjmd) {
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
                    'action' => 'rpjmd@create',
                    'id' => $rpjmd->id,
                    'description' => auth()->user()->fullname . ' menambahkan data rpjmd ' . ($rpjmd->Program->name ?? '-'),
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

        // static::updating(function ($rpjmd) {
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
        //             'action' => 'rpjmd@update',
        //             'id' => $rpjmd->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data rpjmd ' . $rpjmd->Program->name,
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


        static::deleting(function ($rpjmd) {
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
                    'action' => 'rpjmd@delete',
                    'id' => $rpjmd->id,
                    'description' => auth()->user()->fullname . ' menghapus data rpjmd ' . $rpjmd->Program->name,
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

    function Indicators()
    {
        return $this->hasMany(RPJMDIndikator::class, 'rpjmd_id', 'id');
    }

    function Anggarans()
    {
        return $this->hasMany(RPJMDAnggaran::class, 'rpjmd_id', 'id');
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
