<?php

namespace App\Models\Caram;

use App\Models\Caram\RPJMDAnggaran;
use App\Models\Caram\RPJMDIndikator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RPJMD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_rpjmd';

    protected $fillable = [
        'periode_id',
        'instance_id',
        'program_id',
        'status',
        'created_by',
        'updated_by',
    ];

    function Indicators()
    {
        return $this->hasMany(RPJMDIndikator::class, 'rpjmd_id', 'id');
    }

    function Anggarans()
    {
        return $this->hasMany(RPJMDAnggaran::class, 'rpjmd_id', 'id');
    }
}
