<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Caram\RPJMD;
use App\Models\Ref\Program;
use Illuminate\Support\Facades\DB;
use App\Models\Caram\RenjaKegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Renja extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_renja';

    protected $fillable = [
        'rpjmd_id',
        'renstra_id',
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

        static::creating(function ($renja) {
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
                    'action' => 'renja@create',
                    'id' => $renja->id,
                    'description' => auth()->user()->fullname . ' menambahkan data renstra perubahan ' . $renja->Program->name,
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

        // static::updating(function ($renja) {
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
        //             'action' => 'renja@update',
        //             'id' => $renja->id,
        //             'description' => auth()->user()->fullname . ' memperbarui data renstra perubahan ' . ($renja->Program->name ?? null),
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


        static::deleting(function ($renja) {
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
                    'action' => 'renja@delete',
                    'id' => $renja->id,
                    'description' => auth()->user()->fullname . ' menghapus data renstra perubahan ' . $renja->Program->name,
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

    function detailKegiatan()
    {
        return $this->hasMany(RenjaKegiatan::class, 'renja_id', 'id');
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
