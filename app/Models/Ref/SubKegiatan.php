<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Instance;
use App\Traits\Searchable;
use App\Models\Ref\Kegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubKegiatan extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_sub_kegiatan';

    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
        'program_id',
        'kegiatan_id',
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
        'fullcode',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($data) {
            if ($data->code) {
                $code = str()->squish($data->code);
                $data->fullcode = $data->Kegiatan->fullcode . '.' . $code;
                $data->saveQuietly();
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
                    'action' => 'sub-kegiatan@update',
                    'id' => $data->id,
                    'description' => auth()->user()->fullname . ' memperbarui data sub kegiatan ' . $data->name,
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


        static::creating(function ($subKegiatan) {
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
                    'action' => 'sub-kegiatan@create',
                    'id' => $subKegiatan->id,
                    'description' => auth()->user()->fullname . ' menambahkan data sub kegiatan ' . $subKegiatan->name,
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


        static::deleting(function ($subKegiatan) {
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
                    'action' => 'sub-kegiatan@delete',
                    'id' => $subKegiatan->id,
                    'description' => auth()->user()->fullname . ' menghapus data sub kegiatan ' . $subKegiatan->name,
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


    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function Urusan()
    {
        return $this->belongsTo(Urusan::class, 'urusan_id');
    }

    function Bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
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
