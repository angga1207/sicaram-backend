<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use App\Models\Ref\Kegiatan;
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
        'description',
    ];

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }
}
