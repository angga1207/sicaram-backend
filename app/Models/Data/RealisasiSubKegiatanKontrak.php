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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealisasiSubKegiatanKontrak extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi_sub_kegiatan_kontrak';

    protected $fillable = [
        'instance_id',
        'urusan_id',
        'bidang_urusan_id',
        'program_id',
        'kegiatan_id',
        'sub_kegiatan_id',
        'year',
        'month',
        'no_kontrak',
        'kd_tender',
        'data_spse',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    function Instance()
    {
        return $this->belongsTo(Instance::class);
    }

    function Urusan()
    {
        return $this->belongsTo(Urusan::class);
    }

    function Bidang()
    {
        return $this->belongsTo(Bidang::class);
    }

    function Program()
    {
        return $this->belongsTo(Program::class);
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class);
    }

    function SubKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class);
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
