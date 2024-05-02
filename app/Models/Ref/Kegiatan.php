<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Ref\Program;
use App\Models\Ref\SubKegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Kegiatan extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_kegiatan';
    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
        'program_id',
        'name',
        'code_1',
        'code_2',
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
        // 'code_1',
        // 'code_2',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($kegiatan) {
            if ($kegiatan->Program) {
                $kegiatan->fullcode = $kegiatan->Program->fullcode . '.' . $kegiatan->code_1 . '.' . $kegiatan->code_2;
                $kegiatan->saveQuietly();
                $subKegiatans = $kegiatan->SubKegiatans;
                foreach ($subKegiatans as $subKegiatan) {
                    $subKegiatan->fullcode = $kegiatan->fullcode . '.' . $subKegiatan->code;
                    $subKegiatan->saveQuietly();
                }
            }
        });
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    function SubKegiatans()
    {
        return $this->hasMany(SubKegiatan::class, 'kegiatan_id', 'id');
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
