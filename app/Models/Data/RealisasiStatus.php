<?php

namespace App\Models\Data;

use App\Models\Ref\SubKegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_realisasi_status';

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
