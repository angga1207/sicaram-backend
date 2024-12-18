<?php

namespace App\Models\Data;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetPerubahanTujuan extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = 'target_perubahan_tujuan';
    protected $fillable = [
        'periode_id',
        'instance_id',
        'tujuan_id',
        'ref_id',
        'parent_id',
        'year',
        'value_1',
        'value_2',
        'value_3',
        'value_4',
        'value_5',
        'value_6',
        'value_7',
        'value_8',
        'value_9',
        'value_10',
        'value_11',
        'value_12',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    protected $searchable = [];

    function Parent()
    {
        return $this->belongsTo(TargetTujuan::class, 'id', 'parent_id');
    }
}
