<?php

namespace App\Models\Data;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetTujuan extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'target_tujuan';
    protected $fillable = [
        'periode_id',
        'instance_id',
        'tujuan_id',
        'ref_id',
        'year',
        'value',
        'last_value',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $searchable = [];
}
