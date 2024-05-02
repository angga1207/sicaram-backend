<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Caram\Renja;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Models\Caram\Renstra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApbdKegiatan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_apbd_detail_kegiatan';

    protected $fillable = [
        'renstra_id',
        'renja_id',
        'apbd_id',
        'program_id',
        'kegiatan_id',
        'anggaran_json',
        'kinerja_json',
        'satuan_json',
        'anggaran_modal',
        'anggaran_operasi',
        'anggaran_transfer',
        'anggaran_tidak_terduga',
        'total_anggaran',
        'total_kinerja',
        'percent_anggaran',
        'percent_kinerja',
        'status',
        'created_by',
        'updated_by',
    ];

    // cast
    protected $casts = [
        'anggaran_modal' => 'float',
        'anggaran_operasi' => 'float',
        'anggaran_transfer' => 'float',
        'anggaran_tidak_terduga' => 'float',
    ];

    function Apbd()
    {
        return $this->hasOne(Apbd::class, 'apbd_id');
    }

    function Renstra()
    {
        return $this->hasOne(Renstra::class, 'renstra_id');
    }

    function Renja()
    {
        return $this->hasOne(Renja::class, 'renja_id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id', 'id');
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
