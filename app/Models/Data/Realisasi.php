<?php

namespace App\Models\Data;

use App\Models\User;
use App\Models\Instance;
use App\Models\Ref\Bidang;
use App\Models\Ref\Urusan;
use App\Traits\Searchable;
use App\Models\Ref\Periode;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Models\Ref\SubKegiatan;
use App\Models\Ref\KodeRekening;
use App\Models\Ref\KodeSumberDana;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Realisasi extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_realisasi';

    protected $fillable = [
        'periode_id',
        'year',
        'month',
        'instance_id',
        'target_id',
        'urusan_id',
        'bidang_urusan_id',
        'program_id',
        'kegiatan_id',
        'sub_kegiatan_id',
        'kode_rekening_id',
        'sumber_dana_id',
        'anggaran',
        'anggaran_hingga_saat_ini',
        'persentase_anggaran',
        'persentase_anggaran_hingga_saat_ini',
        'kinerja',
        'kinerja_hingga_saat_ini',
        'persentase_kinerja',
        'persentase_kinerja_hingga_saat_ini',
        'status',
        'status_leader',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $searchable = [
        'year',
        'month',
    ];


    public function Periode()
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    function KodeRekening()
    {
        return $this->belongsTo(KodeRekening::class, 'kode_rekening_id');
    }

    function Target()
    {
        return $this->belongsTo(TargetKinerja::class, 'target_id');
    }

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

    function SumberDana()
    {
        return $this->belongsTo(KodeSumberDana::class, 'sumber_dana_id');
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
