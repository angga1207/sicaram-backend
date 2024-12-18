<?php

namespace App\Models\Data;

use App\Models\User;
use App\Models\Instance;
use App\Models\Ref\Bidang;
use App\Models\Ref\Urusan;
use App\Traits\Searchable;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Data\RealisasiSubKegiatanFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiSubKegiatan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi_sub_kegiatan';

    protected $fillable = [
        'periode_id',
        'year',
        'month',
        'instance_id',
        'urusan_id',
        'bidang_urusan_id',
        'program_id',
        'kegiatan_id',
        'sub_kegiatan_id',
        'realisasi_anggaran',
        'persentase_realisasi_anggaran',
        'realisasi_kinerja',
        'persentase_realisasi_kinerja',
        'status',
        'status_leader',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'realisasi_anggaran',
        'realisasi_kinerja',
    ];

    function Instance()
    {
        return $this->belongsTo(Instance::class, 'instance_id');
    }

    function Urusan()
    {
        return $this->belongsTo(Urusan::class, 'urusan_id');
    }

    function BidangUrusan()
    {
        return $this->belongsTo(Bidang::class, 'bidang_urusan_id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    function SubKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }

    public function Files()
    {
        return $this->hasMany(RealisasiSubKegiatanFiles::class, 'parent_id', 'id');
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
