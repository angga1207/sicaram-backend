<?php

namespace App\Models\Data;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiSubKegiatanFiles extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_realisasi_sub_kegiatan_files';

    protected $fillable = [
        'parent_id',
        'type',
        'save_to',
        'file',
        'filename',
        'path',
        'size',
        'extension',
        'mime_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'filename',
        'path',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($data) {
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
                    'action' => 'realisasi-sub-kegiatan-files@delete',
                    'id' => $data->id,
                    'description' => auth()->user()->fullname . ' menghapus berkas keterangan realisasi ' . $data->name,
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
