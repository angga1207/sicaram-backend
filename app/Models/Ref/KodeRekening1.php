<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KodeRekening1 extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_kode_rekening_1';

    protected $fillable = [
        'peride_id',
        'code',
        'fullcode',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    // searchable
    protected $searchable = [
        'code',
        'fullcode',
        'name',
        'description'
    ];
}
