<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Ref\Urusan;
use App\Traits\Searchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bidang extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_bidang_urusan';
    protected $fillable = [
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
        static::updating(function ($bidang) {
            $bidang->fullcode = $bidang->Urusan->fullcode . '.' . $bidang->code;
            // $bidang->save();
            $programs = $bidang->Programs;
            foreach ($programs as $program) {
                $program->fullcode = $bidang->fullcode . '.' . $program->code;
                $program->saveQuietly();
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
                    'action' => 'bidang@update',
                    'id' => $bidang->id,
                    'description' => auth()->user()->fullname . ' memperbarui data bidang ' . $bidang->name,
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

        static::creating(function ($bidang) {
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
                    'action' => 'bidang@create',
                    'id' => $bidang->id,
                    'description' => auth()->user()->fullname . ' menambahkan data bidang ' . $bidang->name,
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

        static::deleting(function ($bidang) {
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
                    'action' => 'bidang@delete',
                    'id' => $bidang->id,
                    'description' => auth()->user()->fullname . ' menghapus data bidang ' . $bidang->name,
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

    public function Urusan()
    {
        return $this->belongsTo(Urusan::class, 'urusan_id');
    }

    function Programs()
    {
        return $this->hasMany(Program::class, 'bidang_id', 'id');
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
