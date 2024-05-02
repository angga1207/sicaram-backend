<?php

namespace App\Models\Ref;

use App\Models\User;
use App\Models\Instance;
use App\Traits\Searchable;
use App\Models\Ref\Kegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubKegiatan extends Model
{
    use HasFactory, Searchable, SoftDeletes;
    protected $table = 'ref_sub_kegiatan';

    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_id',
        'program_id',
        'kegiatan_id',
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
        static::updating(function ($data) {
            if ($data->code) {
                $code = str()->squish($data->code);
                $data->fullcode = $data->Kegiatan->fullcode . '.' . $code;
                $data->saveQuietly();
            }
        });
    }


    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
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
