<?php

namespace App\Models\Caram;

use App\Traits\Searchable;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Models\Caram\Renstra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RenstraKegiatan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_renstra_detail_kegiatan';

    protected $fillable = [
        'renstra_id',
        'program_id',
        'kegiatan_id',
        'anggaran_json',
        'kinerja_json',
        'satuan_json',
        'total_anggaran',
        'total_kinerja',
        'percent_anggaran',
        'percent_kinerja',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'anggaran_modal' => 'float',
        'anggaran_operasi' => 'float',
        'anggaran_transfer' => 'float',
        'anggaran_tidak_terduga' => 'float',
    ];

    function Renstra()
    {
        return $this->hasOne(Renstra::class, 'renstra_id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    function Kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id', 'id');
    }

    function dataSubKegiatan()
    {
        return $this->hasMany(RenstraSubKegiatan::class, 'parent_id');
    }
}
