<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Instance;
use App\Traits\Searchable;
use App\Models\Ref\Program;
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Kegiatan extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_kegiatan';
    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
        'program_id',
        'name',
        'code_1',
        'code_2',
        'fullcode',
        'description',
        'periode_id',
        'status',
        'created_by',
        'updated_by',
    ];
    protected $searchable = [
        'name',
        'fullcode',
        // 'code_1',
        // 'code_2',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($kegiatan) {
            if ($kegiatan->Program) {
                $kegiatan->fullcode = $kegiatan->Program->fullcode . '.' . $kegiatan->code_1 . '.' . $kegiatan->code_2;
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
                    'action' => 'kegiatan@update',
                    'id' => $kegiatan->id,
                    'description' => auth()->user()->fullname . ' memperbarui data kegiatan ' . $kegiatan->name,
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

        static::creating(function ($kegiatan) {
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
                    'action' => 'kegiatan@create',
                    'id' => $kegiatan->id,
                    'description' => auth()->user()->fullname . ' menambahkan data kegiatan ' . $kegiatan->name,
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


        static::deleting(function ($kegiatan) {
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
                    'action' => 'kegiatan@delete',
                    'id' => $kegiatan->id,
                    'description' => auth()->user()->fullname . ' menghapus data kegiatan ' . $kegiatan->name,
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

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    function SubKegiatans()
    {
        return $this->hasMany(SubKegiatan::class, 'kegiatan_id', 'id');
    }

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
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
