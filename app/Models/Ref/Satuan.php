<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Satuan extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_satuan';

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public $searchable = [
        'name',
    ];
}
