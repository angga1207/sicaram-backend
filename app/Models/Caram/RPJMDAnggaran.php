<?php

namespace App\Models\Caram;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RPJMDAnggaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_rpjmd_anggaran';

    protected $fillable = [
        'rpjmd_id',
        'year',
        'anggaran',
        'status',
        'created_by',
        'updated_by',
    ];


    function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
