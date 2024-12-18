<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use App\Models\Instance;
use App\Traits\Searchable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Searchable, SoftDeletes;

    protected $fillable = [
        'fullname',
        'firstname',
        'lastname',
        'email',
        'username',
        'photo',
        'instance_id',
        'instance_type',
        'role_id',
        'fcm_token',
        'password',
    ];
    protected $searchable = [
        'fullname',
        'firstname',
        'lastname',
        'username',
        // 'roles.name',
        // 'PerangkatDaerah.name',
        // 'PerangkatDaerah.alias',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
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
                    'action' => 'user@create',
                    'id' => $user->id,
                    'description' => auth()->user()->fullname . ' menambahkan pengguna ' . $user->fullname,
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

        static::deleting(function ($user) {
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
                    'action' => 'user@delete',
                    'id' => $user->id,
                    'description' => auth()->user()->fullname . ' menghapus pengguna ' . $user->fullname,
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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    function Role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    function Instance()
    {
        return $this->hasOne(Instance::class, 'id', 'instance_id');
    }

    function MyPermissions($periode = null)
    {
        $myPermissions = [];
        $myPermissions = DB::table('pivot_user_sub_kegiatan_permissions')
            ->where('periode_id', $periode ?? 1)
            ->where('user_id', $this->id)
            ->get();
        return $myPermissions;
    }
}
