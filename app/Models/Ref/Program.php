<?php

namespace App\Models\Ref;

use App\Models\Ref\Bidang;
use App\Traits\Searchable;
use App\Models\Ref\Kegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_program';
    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
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
        // 'code',
        'fullcode',
        'description',
    ];


    protected static function boot()
    {
        parent::boot();
        static::updating(function ($program) {
            $program->fullcode = $program->Bidang->fullcode . '.' . $program->code;
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
        });
    }

    function Bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    function Kegiatans()
    {
        return $this->hasMany(Kegiatan::class, 'program_id', 'id');
    }
}
