<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KodeRekening2 extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_kode_rekening_2';

    protected $fillable = [
        'peride_id',
        'ref_kode_rekening_1',
        'code',
        'fullcode',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
}
