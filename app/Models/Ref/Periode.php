<?php

namespace App\Models\Ref;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Periode extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'ref_periode';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
    ];
}
