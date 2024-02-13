<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Searchable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
