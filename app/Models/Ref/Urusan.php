<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Urusan extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_urusan';
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
        static::updating(function ($urusan) {
            $urusan->fullcode = $urusan->code;
            $bidangs = $urusan->Bidangs;
            foreach ($bidangs as $bidang) {
                $bidang->fullcode = $urusan->code . '.' . $bidang->code;
                $bidang->saveQuietly();
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
            }
        });
    }



    function Bidangs()
    {
        return $this->hasMany(Bidang::class, 'urusan_id', 'id');
    }

    function Programs()
    {
        return $this->hasManyThrough(Program::class, Bidang::class, 'urusan_id', 'bidang_id', 'id', 'id');
    }

    function Kegiatans()
    {
        return $this->hasManyThrough(Kegiatan::class, Program::class, 'urusan_id', 'program_id', 'id', 'id');
    }

    function SubKegiatans()
    {
        return $this->hasManyThrough(SubKegiatan::class, Kegiatan::class, 'urusan_id', 'kegiatan_id', 'id', 'id');
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
