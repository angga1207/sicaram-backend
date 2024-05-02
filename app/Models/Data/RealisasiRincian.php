<?php

namespace App\Models\Data;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Data\Realisasi;
use Illuminate\Database\Eloquent\Model;
use App\Models\Data\RealisasiKeterangan;
use App\Models\Data\TargetKinerjaRincian;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiRincian extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi_rincian';

    protected $fillable = [
        'periode_id',
        'realisasi_id',
        'target_rincian_id',
        'title',
        'pagu_sipd',
        'anggaran',
        'kinerja',
        'persentase_kinerja',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'title',
    ];

    function Realisasi()
    {
        return $this->belongsTo(Realisasi::class, 'realisasi_id');
    }

    function TargetRincian()
    {
        return $this->belongsTo(TargetKinerjaRincian::class, 'target_rincian_id');
    }

    function Keterangan()
    {
        return $this->hasMany(RealisasiKeterangan::class, 'parent_id');
    }

    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    function DeletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
