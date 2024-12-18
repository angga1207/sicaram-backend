<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Instance;
use App\Models\InstanceSubUnit;
use App\Models\Ref\Bidang;
use App\Traits\Searchable;
use App\Models\Ref\Kegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_program';
    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
        'name',
        'code',
        'fullcode',
        'description',
        'periode_id',
        'status',
        'created_by',
        'updated_by',
    ];
    protected $searchable = [
        'name',
        // 'code',
        'fullcode',
        'description',
    ];


    protected static function boot()
    {
        parent::boot();
        static::updating(function ($program) {
            $program->fullcode = $program->Bidang->fullcode . '.' . $program->code;
            $kegiatans = $program->Kegiatans;
            foreach ($kegiatans as $kegiatan) {
                $kegiatan->fullcode = $program->fullcode . '.' . $kegiatan->code_1 . '.' . $kegiatan->code_2;
                $kegiatan->saveQuietly();
                $subKegiatans = $kegiatan->SubKegiatans;
                foreach ($subKegiatans as $subKegiatan) {
                    $subKegiatan->fullcode = $kegiatan->fullcode . '.' . $subKegiatan->code;
                    $subKegiatan->saveQuietly();
                }
            }

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
                    'action' => 'program@update',
                    'id' => $program->id,
                    'description' => auth()->user()->fullname . ' memperbarui data program ' . $program->name,
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


        static::creating(function ($program) {
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
                    'action' => 'program@create',
                    'id' => $program->id,
                    'description' => auth()->user()->fullname . ' menambahkan data program ' . $program->name,
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


        static::deleting(function ($program) {
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
                    'action' => 'program@delete',
                    'id' => $program->id,
                    'description' => auth()->user()->fullname . ' menghapus data program ' . $program->name,
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

    function Bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    function Kegiatans()
    {
        return $this->hasMany(Kegiatan::class, 'program_id', 'id');
    }

    function SubKegiatans()
    {
        return $this->hasManyThrough(SubKegiatan::class, Kegiatan::class, 'program_id', 'kegiatan_id', 'id', 'id');
    }

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id', 'id');
    }

    function SubUnit()
    {
        return $this->belongsToMany(InstanceSubUnit::class, 'pivot_instance_sub_unit_program', 'program_id', 'instance_sub_unit_id', 'id');
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
