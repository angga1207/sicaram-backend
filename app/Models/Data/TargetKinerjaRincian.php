<?php

namespace App\Models\Data;

use App\Traits\Searchable;
use App\Models\Ref\Periode;
use Illuminate\Database\Eloquent\Model;
use App\Models\Data\TargetKinerjaKeterangan;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetKinerjaRincian extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_target_kinerja_rincian';

    protected $fillable = [
        'periode_id',
        'target_kinerja_id',
        'title',
        'pagu_sebelum_pergeseran',
        'pagu_sesudah_pergeseran',
        'pagu_selisih',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'title',
    ];

    public function Periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function Parent()
    {
        return $this->belongsTo(TargetKinerja::class, 'target_kinerja_id');
    }

    function Keterangan()
    {
        return $this->hasMany(TargetKinerjaKeterangan::class, 'parent_id');
    }
}
