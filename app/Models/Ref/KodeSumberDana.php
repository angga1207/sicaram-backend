<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KodeSumberDana extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'ref_kode_sumber_dana';
    protected $searchable = [
        'fullcode',
        'name',
    ];
    protected $fillable = [
        'parent_id',
        'periode_id',
        'year',
        'code_1',
        'code_2',
        'code_3',
        'code_4',
        'code_5',
        'code_6',
        'fullcode',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    function Parent()
    {
        return $this->belongsTo(KodeSumberDana::class, 'parent_id');
    }

    function Level()
    {
        $level = 0;
        if ($this->code_1) {
            $level = 1;
        }
        if ($this->code_2) {
            $level = 2;
        }
        if ($this->code_3) {
            $level = 3;
        }
        if ($this->code_4) {
            $level = 4;
        }
        if ($this->code_5) {
            $level = 5;
        }
        if ($this->code_6) {
            $level = 6;
        }
        return $level;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($data) {
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
                    'action' => 'kode-sumber-dana@create',
                    'id' => $data->id,
                    'description' => auth()->user()->fullname . ' menambahkan data kode sumber dana ' . $data->name,
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
                    'action' => 'kode-sumber-dana@update',
                    'id' => $data->id,
                    'description' => auth()->user()->fullname . ' memperbarui data kode sumber dana ' . $data->name,
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
                    'action' => 'kode-sumber-dana@delete',
                    'id' => $data->id,
                    'description' => auth()->user()->fullname . ' menghapus data kode sumber dana ' . $data->name,
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
}
