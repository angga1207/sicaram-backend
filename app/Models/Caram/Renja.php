<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Traits\Searchable;
use App\Models\Caram\RPJMD;
use App\Models\Ref\Program;
use App\Models\Caram\RenjaKegiatan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Renja extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'data_renja';

    protected $fillable = [
        'rpjmd_id',
        'renstra_id',
        'periode_id',
        'instance_id',
        'program_id',
        'total_anggaran',
        'total_kinerja',
        'percent_anggaran',
        'percent_kinerja',
        'status_leader',
        'status',
        'notes_verificator',
        'created_by',
        'updated_by',
    ];

    function detailKegiatan()
    {
        return $this->hasMany(RenjaKegiatan::class, 'renja_id', 'id');
    }

    function RPJMD()
    {
        return $this->belongsTo(RPJMD::class, 'rpjmd_id', 'id');
    }

    function Program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
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
