<?php

namespace App\Models\Data;

use App\Models\User;
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiSubKegiatanKeterangan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_realisasi_sub_kegiatan_keterangan';

    protected $fillable = [
        'periode_id',
        'instance_id',
        'year',
        'month',
        'urusan_id',
        'bidang_urusan_id',
        'program_id',
        'kegiatan_id',
        'sub_kegiatan_id',
        'notes',
        'link_map',
        'faktor_penghambat',
        'longitude',
        'latitude',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($data) {
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
                    'action' => 'realisasi-sub-kegiatan-keterangan@update',
                    'id' => $data->id,
                    'description' => 'Memperbarui Keterangan Realisasi ' . ($data->SubKegiatan->fullcode ?? '') . ' - ' . ($data->SubKegiatan->name ?? ''),
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

    function SubKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id', 'id');
    }

    public function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
