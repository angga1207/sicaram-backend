<?php

namespace App\Models\Caram;

use App\Models\User;
use App\Models\Ref\Satuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RPJMDIndikator extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_rpjmd_indikator';

    protected $fillable = [
        'rpjmd_id',
        'year',
        'value',
        'satuan_id',
        'status',
        'created_by',
        'updated_by',
    ];

    function Satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
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
