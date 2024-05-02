<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Ref\Urusan;
use App\Traits\Searchable;
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
