<?php

namespace App\Models\Data;

use App\Models\Ref\SubKegiatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetKinerjaStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_target_kinerja_status';

    protected $fillable = [
        'sub_kegiatan_id',
        'month',
        'year',
        'status',
        'status_leader',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function SubKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }
}
